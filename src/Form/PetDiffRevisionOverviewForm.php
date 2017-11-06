<?php

namespace Drupal\pet\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\diff\Form\RevisionOverviewForm;

/**
 * Provides a form for Pet revision overview page.
 */
class PetDiffRevisionOverviewForm extends RevisionOverviewForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pet = NULL) {
    $account = $this->currentUser;
    /** @var \Drupal\pet\Entity\PetInterface $pet */
    $langcode = $pet->language()->getId();
    $langname = $pet->language()->getName();
    $languages = $pet->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $pet_storage = $this->entityTypeManager->getStorage('pet');

    $pagerLimit = $this->config->get('general_settings.revision_pager_limit');

    $query = $this->entityQuery->get('pet')
      ->condition($pet->getEntityType()->getKey('id'), $pet->id())
      ->pager($pagerLimit)
      ->allRevisions()
      ->sort($pet->getEntityType()->getKey('revision'), 'DESC')
      ->execute();
    $vids = array_keys($query);

    $revision_count = count($vids);

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $pet->label(),
    ]) : $this->t('Revisions for %title', [
      '%title' => $pet->label(),
    ]);
    $build['pid'] = array(
      '#type' => 'hidden',
      '#value' => $pet->id(),
    );

    $table_header = [];
    $table_header['revision'] = $this->t('Revision');

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $table_header += array(
        'select_column_one' => '',
        'select_column_two' => '',
      );
    }
    $table_header['operations'] = $this->t('Operations');

    $rev_revert_perm = $account->hasPermission('revert pet revisions') ||
      $account->hasPermission('administer pet entities');
    $rev_delete_perm = $account->hasPermission('delete pet revisions') ||
      $account->hasPermission('administer pet entities');
    $revert_permission = $rev_revert_perm && $pet->access('update');
    $delete_permission = $rev_delete_perm && $pet->access('delete');

    // Contains the table listing the revisions.
    $build['node_revisions_table'] = array(
      '#type' => 'table',
      '#header' => $table_header,
      '#attributes' => array('class' => array('diff-revisions')),
    );

    $build['node_revisions_table']['#attached']['library'][] = 'diff/diff.general';
    $build['node_revisions_table']['#attached']['drupalSettings']['diffRevisionRadios'] = $this->config->get('general_settings.radio_behavior');

    $default_revision = $pet->getRevisionId();
    // Add rows to the table.
    foreach ($vids as $key => $vid) {
      $previous_revision = NULL;
      if (isset($vids[$key + 1])) {
        $previous_revision = $pet_storage->loadRevision($vids[$key + 1]);
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      if ($revision = $pet_storage->loadRevision($vid)) {
        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $username = array(
            '#theme' => 'username',
            '#account' => $revision->getRevisionUser(),
          );
          $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
          // Use revision link to link to revisions that are not active.
          if ($vid != $pet->getRevisionId()) {
            $link = Link::fromTextAndUrl($revision_date, new Url('entity.pet.revision', ['pet' => $pet->id(), 'pet_revision' => $vid]));
          }
          else {
            $link = $pet->toLink($revision_date);
          }

          if ($vid == $default_revision) {
            $row = [
              'revision' => $this->buildRevision($link, $username, $revision, $previous_revision),
            ];

            // Allow comparisons only if there are 2 or more revisions.
            if ($revision_count > 1) {
              $row += [
                'select_column_one' => $this->buildSelectColumn('radios_left', $vid, FALSE),
                'select_column_two' => $this->buildSelectColumn('radios_right', $vid, $vid),
              ];
            }
            $row['operations'] = array(
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
              '#attributes' => array(
                'class' => array('revision-current'),
              ),
            );
            $row['#attributes'] = [
              'class' => ['revision-current'],
            ];
          }
          else {
            $route_params = array(
              'pet' => $pet->id(),
              'pet_revision' => $vid,
              'langcode' => $langcode,
            );
            $links = array();
            if ($revert_permission) {
              $links['revert'] = [
                'title' => $vid < $pet->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
                'url' => $has_translations ?
                Url::fromRoute('entity.pet.revision_revert_translation_confirm', ['pet' => $pet->id(), 'pet_revision' => $vid, 'langcode' => $langcode]) :
                Url::fromRoute('entity.pet.revision_revert_confirm', ['pet' => $pet->id(), 'pet_revision' => $vid]),
              ];
            }
            if ($delete_permission) {
              $links['delete'] = array(
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('entity.pet.revision_delete_confirm', $route_params),
              );
            }

            // Here we don't have to deal with 'only one revision' case because
            // if there's only one revision it will also be the default one,
            // entering on the first branch of this if else statement.
            $row = [
              'revision' => $this->buildRevision($link, $username, $revision, $previous_revision),
              'select_column_one' => $this->buildSelectColumn('radios_left', $vid,
                isset($vids[1]) ? $vids[1] : FALSE),
              'select_column_two' => $this->buildSelectColumn('radios_right', $vid, FALSE),
              'operations' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ];
          }
          // Add the row to the table.
          $build['node_revisions_table'][] = $row;
        }
      }
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $build['submit'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Compare selected revisions'),
        '#attributes' => array(
          'class' => array(
            'diff-button',
          ),
        ),
      );
    }
    $build['pager'] = array(
      '#type' => 'pager',
    );
    $build['#attached']['library'][] = 'node/drupal.node.admin';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $pid = $input['pid'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }
    // Builds the redirect Url.
    $redirect_url = Url::fromRoute(
      'entity.pet.revisions_diff',
      array(
        'pet' => $pid,
        'left_revision' => $vid_left,
        'right_revision' => $vid_right,
        'filter' => $this->diffLayoutManager->getDefaultLayout(),
      )
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}
