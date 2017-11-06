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
      '#open' => TRUE,
      '#description' => $this->t('HTML email support is most easily provided by the <a href="@url">Mime Mail</a> module, which must be installed and enabled.', ['@url' => 'http://drupal.org/project/mimemail']),
    ];

    if (PetHelper::hasMimeMail()) {
      $form['send_plain']['#group'] = 'mimemail';
      $form['mail_body_plain']['#group'] = 'mimemail';
    }
    else {
      unset($form['send_plain'], $form['mail_body_plain']);
    }

    $form['tokens'] = pet_token_help();

    $has_administer = \Drupal::currentUser()->hasPermission('administer pet entities');
    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional options'),
      '#open' => $has_administer,
      '#access' => $has_administer,
    ];

    $form['cc']['#group'] = 'admin';
    $form['bcc']['#group'] = 'admin';
    $form['reply_to']['#group'] = 'admin';
    $form['recipient_callback']['#group'] = 'admin';

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
