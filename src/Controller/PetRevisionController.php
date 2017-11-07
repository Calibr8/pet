<?php

namespace Drupal\pet\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\pet\Entity\PetInterface;

/**
 * Class PetRevisionController.
 *
 *  Returns responses for Pet revision routes.
 */
class PetRevisionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a PetRevisionController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Displays a Pet revision.
   *
   * @param int $pet_revision
   *   The Pet revision id.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($pet_revision) {
    $pet = static::entityTypeManager()->getStorage('pet')->loadRevision($pet_revision);
    $view_builder = static::entityTypeManager()->getViewBuilder('pet');

    return $view_builder->view($pet);
  }

  /**
   * Page title callback for a Pet revision.
   *
   * @param int $pet_revision
   *   The Pet revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($pet_revision) {
    /* @var \Drupal\pet\Entity\PetInterface */
    $pet = static::entityTypeManager()->getStorage('pet')->loadRevision($pet_revision);
    $date = $this->dateFormatter->format($pet->getRevisionCreationTime(), 'short');
    return $this->t('Revision of %title from %date', ['%title' => $pet->label(), '%date' => $date]);
  }

  /**
   * Generates an overview table of older revisions of a Pet.
   *
   * @param \Drupal\pet\Entity\PetInterface $pet
   *   A Pet object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PetInterface $pet) {
    $account = $this->currentUser();
    $langcode = $pet->language()->getId();
    $langname = $pet->language()->getName();
    $languages = $pet->getTranslationLanguages();
    $has_translations = (count($languages) > 1);

    /* @var \Drupal\pet\PetStorageInterface */
    $pet_storage = static::entityTypeManager()->getStorage('pet');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $pet->label()]) : $this->t('Revisions for %title', ['%title' => $pet->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert pet revisions") || $account->hasPermission('administer pet entities')));
    $delete_permission = (($account->hasPermission("delete pet revisions") || $account->hasPermission('administer pet entities')));

    $rows = [];

    $vids = $pet_storage->revisionIds($pet);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\pet\Entity\PetInterface $revision */
      $revision = $pet_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $pet->getRevisionId()) {
          $link = \Drupal::service('link_generator')->generate($date, new Url('entity.pet.revision', ['pet' => $pet->id(), 'pet_revision' => $vid]));
        }
        else {
          $link = $pet->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {

            $params = ['pet' => $pet->id(), 'pet_revision' => $vid];
            if ($has_translations) {
              $params += ['langcode' => $langcode];
            }

            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.pet.revision_revert_translation_confirm', $params) :
              Url::fromRoute('entity.pet.revision_revert_confirm', $params),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.pet.revision_delete_confirm', ['pet' => $pet->id(), 'pet_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['pet_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
