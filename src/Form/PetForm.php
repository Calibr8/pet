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
    $form = parent::buildForm($form, $form_state);

    $form['mimemail'] = [
      '#type' => 'details',
      '#title' => $this->t('Mime Mail options'),
      '#open' => TRUE,
      '#weight' => 18,
    ];

    if (PetHelper::hasMimeMail()) {
      $form['mail_body']['widget'][0]['value']['#description'] = $form['mail_body']['widget'][0]['value']['#description']->__toString() . '<br>' . $this->t('When empty, Mime Mail will create a plain text version from "HTML body".');

      $form['send_plain']['#group'] = 'mimemail';
      $form['mail_body_html']['#group'] = 'mimemail';
      $form['mail_body_html']['widget'][0]['value']['#base_type'] = $form['mail_body_html']['widget'][0]['value']['#type'];
      $form['mail_body_html']['widget'][0]['value']['#type'] = 'text_format';

      // Get a valid format.
      $format = $form['format']['widget'][0]['value']['#default_value'];
      if (!$format) {
        $format_mime = Drupal::config('mimemail.settings')->get('format');
        $format = $format_mime ? $format_mime : filter_fallback_format();
      }
      $form['mail_body_html']['widget'][0]['value']['#format'] = $format;
    }
    else {
      $form['mimemail']['#description'] = $this->t('HTML email support is most easily provided by the <a href="@url" target="_blank">Mime Mail</a> module, which must be installed and enabled.', ['@url' => 'http://drupal.org/project/mimemail']);
      unset($form['send_plain'], $form['mail_body_html']);
    }

    // For some reason, if basefield definition uses 'hidden', the field won't
    // show up at all, so we use 'string_textfield' and hide it here.
    $form['format']['widget'][0]['value']['#type'] = 'hidden';

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
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Split up body and its format.
    $values = $form_state->getValues();
    $values['format'][0]['value'] = $values['mail_body_html'][0]['value']['format'];
    $values['mail_body_html'][0]['value'] = $values['mail_body_html'][0]['value']['value'];
    $form_state->setValues($values);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\pet\Entity\PetInterface $entity */
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
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
