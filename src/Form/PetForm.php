<?php

namespace Drupal\pet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pet\Utility\PetHelper;

/**
 * Form controller for Pet edit forms.
 *
 * @ingroup pet
 */
class PetForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\pet\Entity\Pet */
    $form = parent::buildForm($form, $form_state);

    $form['mimemail'] = [
      '#type' => 'details',
      '#title' => $this->t('Mime Mail options'),
      '#description' => $this->t('HTML email support is most easily provided by the <a href="@url">Mime Mail</a> module, which must be installed and enabled.', ['@url' => 'http://drupal.org/project/mimemail']),
      '#open' => TRUE,
      '#weight' => 18,
    ];

    if (PetHelper::hasMimeMail()) {
      $form['send_plain']['#group'] = 'mimemail';
      $form['mail_body_plain']['#group'] = 'mimemail';
    }
    else {
      unset($form['send_plain'], $form['mail_body_plain']);
    }

    $form['tokens'] = pet_token_help();
    $form['tokens']['#weight'] = 19;

    // Group advanced options.
    $has_administer = \Drupal::currentUser()->hasPermission('administer pet entities');
    $form['administer'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional options'),
      '#open' => $has_administer,
      '#access' => $has_administer,
      '#weight' => 20,
    ];
    $form['cc']['#group'] = 'administer';
    $form['bcc']['#group'] = 'administer';
    $form['reply_to']['#group'] = 'administer';
    $form['recipient_callback']['#group'] = 'administer';

    // Group author info.
    $form['author_information'] = [
      '#type' => "details",
      '#title' => $this->t('Authoring information'),
      '#open' => TRUE,
      '#group' => 'advanced',
      '#weight' => 10,
    ];
    $form['user_id']['#group'] = "author_information";
    unset($form['user_id']['#parents']);

    // Move revision_log_message to revision tab.
    $form['revision_log_message']['#group'] = "revision_information";
    unset($form['revision_log_message']['#parents']);

    $form['actions']['submit']['#value'] = $this->t('Save Template');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionAuthorId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('The email template %label has been added.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('The email template %label has been updated.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.pet.collection');
  }

}
