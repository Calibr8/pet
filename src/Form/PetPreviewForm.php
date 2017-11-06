<?php

namespace Drupal\pet\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pet\Entity\PetInterface;
use Drupal\pet\Utility\PetHelper;

/**
 * PetPreviewForm.
 *
 * @package Drupal\pet\Form
 *
 * @todo: review, check and test.
 */
class PetPreviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pet_preview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PetInterface $pet = NULL) {
    $storage = $form_state->getStorage();
    $storage['pet'] = $pet;

    if (!isset($storage['step'])) {
      $storage['step'] = 1;
    }

    if ($storage['step'] == 3) {
      drupal_set_message(t('Email(s) sent'));
      $form_state->setStorage([]);
    }

    $has_recipient_callback = isset($storage['recipient_callback']);

    switch ($storage['step']) {
      case 1:

        if ($has_recipient_callback) {
          $recipients = $this->t('Recipient list will be generated for preview.');
        }
        // In case of returning from step 2.
        elseif (isset($storage['recipients'])) {
          $recipients = $storage['recipients'];
        }
        else {
          $recipients = \Drupal::currentUser()->getEmail();
        }

        $form['recipients'] = [
          '#title' => $this->t('To'),
          '#type' => 'textfield',
          '#required' => TRUE,
          '#default_value' => $recipients,
          '#description' => $this->t('Enter the recipient(s) comma separated. A separate email will be sent to each, with user token substitution if the email corresponds to a site user.'),
          '#disabled' => $has_recipient_callback,
        ];

        $form['reply_to'] = [
          '#title' => $this->t('Reply-To'),
          '#type' => 'email',
          '#required' => FALSE,
          '#default_value' => isset($storage['reply_to']) ? $storage['reply_to'] : $pet->getReplyTo(),
          '#description' => $this->t('Reply-To email address. If empty, the site email address will be user.'),
        ];

        $form['copies'] = [
          '#title' => $this->t('Copies'),
          '#type' => 'details',
          '#open' => $pet->getCc() && $pet->getBcc(),
        ];

        $form['copies']['cc'] = [
          '#title' => $this->t('Cc'),
          '#type' => 'textfield',
          '#rows' => 3,
          '#default_value' => isset($storage['cc']) ? $storage['cc'] : $pet->getCc(),
          '#description' => $this->t('Enter any Cc recipients comma separated.'),
        ];

        $form['copies']['bcc'] = [
          '#title' => $this->t('Bcc'),
          '#type' => 'textfield',
          '#rows' => 3,
          '#default_value' => isset($storage['bcc']) ? $storage['bcc'] : $pet->getBcc(),
          '#description' => $this->t('Enter any Bcc recipients comma separated.'),
        ];

        $form['subject'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Subject'),
          '#maxlength' => 255,
          '#default_value' => isset($storage['subject']) ? $storage['subject'] : $pet->getSubject(),
          '#required' => TRUE,
        ];

        $body_description = $this->t('Review and edit standard template before previewing. This will not change the template for future emailings, just for this one. To change the template permanently, go to the template page. You may use the tokens below.');

        if (!(PetHelper::hasMimeMail() && $pet->getSendPlain())) {
          $form['body'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Body'),
            '#default_value' => isset($storage['body']) ? $storage['body'] : $pet->getBody(),
            '#rows' => 15,
            '#description' => $body_description,
          ];
        }

        if (PetHelper::hasMimeMail()) {
          $form['mimemail'] = [
            '#type' => 'details',
            '#title' => $this->t('Plain text body'),
            '#collapsible' => TRUE,
            '#collapsed' => !(PetHelper::hasMimeMail() && $pet->getSendPlain()),
          ];

          $form['mimemail']['body_plain'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Plain text body'),
            '#default_value' => isset($storage['body_plain']) ? $storage['body_plain'] : $pet->getBodyPlain(),
            '#rows' => 15,
            '#description' => $body_description,
          ];
        }

        $form['tokens'] = pet_token_help();

        $form['preview'] = [
          '#type' => 'submit',
          '#value' => $this->t('Preview'),
        ];
        break;

      case 2:
      case 3:
        $form['info'] = [
          '#markup' => '<p>' . $this->t("A preview of the email is shown below. If you're satisfied, click Send. If not, click Back to edit the email.") . '</p>',
        ];

        $form['recipients'] = [
          '#title' => $this->t('To'),
          '#type' => 'hidden',
          '#required' => TRUE,
          '#default_value' => $storage['recipients'],
          '#disabled' => $has_recipient_callback,
        ];

        $form['recipients_display'] = [
          '#type' => 'textarea',
          '#title' => $this->t('To'),
          '#rows' => 4,
          '#value' => $this->formatRecipients($storage['recipients_array']),
          '#disabled' => TRUE,
        ];

        if ($storage['reply_to']) {
          $form['reply_to'] = [
            '#title' => $this->t('Reply-To'),
            '#type' => 'email',
            '#value' => $storage['reply_to'],
            '#disabled' => TRUE,
          ];
        }

        if ($storage['cc']) {
          $form['cc'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Cc'),
            '#rows' => 4,
            '#value' => $storage['cc'],
            '#disabled' => TRUE,
          ];
        }

        if ($storage['bcc']) {
          $form['bcc'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Bcc'),
            '#rows' => 4,
            '#value' => $storage['bcc'],
            '#disabled' => TRUE,
          ];
        }

        $form['subject'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Subject'),
          '#size' => 80,
          '#value' => $storage['subject_preview'],
          '#disabled' => TRUE,
        ];

        $form['body'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Body'),
          '#rows' => 15,
          '#value' => $storage['body_preview'],
          '#disabled' => TRUE,
        ];

        // todo: review when testing html mails.
        if (PetHelper::hasMimeMail() && !$pet->getSendPlain()) {
          $form['body_label'] = [
            '#prefix' => '<div class="pet_body_label">',
            '#suffix' => '</div>',
            '#markup' => '<label>' . $this->t('Body as HTML') . '</label>',
          ];

          $form['body_preview'] = [
            '#prefix' => '<div class="pet_body_preview">',
            '#suffix' => '</div>',
            '#markup' => $storage['body_preview'],
          ];
        }

        // todo: review when testing html mails.
        if (PetHelper::hasMimeMail() && $pet->getSendPlain()) {
          $form['body_plain'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Plain text body'),
            '#rows' => 15,
            '#value' => $storage['body_preview_plain'],
            '#disabled' => TRUE,
          ];
        }

        $form['back'] = [
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#submit' => ['::stepBack'],
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Send email(s)'),
        ];
        break;
    }

    $form_state->setStorage($storage);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $recipients_array = [];

    // todo: Make this more generic, and also test CC and BCC input.
    $errors = $this->validateRecipients($form_state, $recipients_array);

    if (!empty($errors)) {
      foreach ($errors as $error) {
        $form_state->setErrorByName('recipients', $error);
      }
    }
    else {
      $form_state->setValue('recipients_array', $recipients_array);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();

    switch ($storage['step']) {
      case 1:
        $form_state->setRebuild(TRUE);

        $storage['recipients_array'] = $values['recipients_array'];
        $storage['recipients'] = $values['recipients'];
        $storage['subject'] = $values['subject'];
        $storage['body'] = isset($values['body']) ? $values['body'] : NULL;
        $storage['body_plain'] = isset($values['body_plain']) ? $values['body_plain'] : NULL;
        $storage['reply_to'] = $values['reply_to'];
        $storage['cc'] = $values['cc'];
        $storage['bcc'] = $values['bcc'];

        $form_state->setStorage($storage);
        $this->makePreview($form_state);
        $storage = $form_state->getStorage();
        break;

      case 2:
        $form_state->setRebuild(TRUE);

        $recipients = array_keys($storage['recipients_array']);

        /** @var \Drupal\pet\Entity\Pet $pet */
        $pet = $storage['pet'];

        $pet->setSubject($storage['subject']);
        $pet->setBody($storage['body']);
        $pet->setBodyPlain($storage['body_plain']);
        $pet->setReplyTo($storage['reply_to']);
        $pet->setCc($storage['cc']);
        $pet->setBcc($storage['bcc']);
        $pet->sendMail($recipients, []);

        break;
    }

    $storage['step']++;
    $form_state->setStorage($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function stepBack(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $form_state->set('step', 1);
  }

  /**
   * Validate existence of a non-empty recipient list free of email errors.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $recipients_array
   *   Array to hold the $recipients as array, incl uid.
   *
   * @return array
   *   Array of errors.
   *
   * @todo: make more generic, move specific parts to validateForm().
   * @todo: test recipient callback part.
   */
  protected function validateRecipients(FormStateInterface &$form_state, array &$recipients_array) {
    $errors = [];
    $recipients_array = [];

    if ($form_state->getValue('recipient_callback')) {
      // todo: test.
      $items = $this->callbackRecipients($form_state);
      if (!empty($mails)) {
        $errors[] = $this->t('There is no recipient callback defined for this template or it is not returning an array.');
        return $errors;
      }
    }
    else {
      // Get recipients from form field.
      $recipients = explode(',', $form_state->getValue('recipients'));
    }

    // Validate and build recipient array with uid on the fly.
    foreach ($recipients as $recipient) {
      $recipient = Xss::filter(trim($recipient));
      if (!\Drupal::service('email.validator')->isValid($recipient)) {
        $errors[] = $this->t('Invalid email address found: %mail.', ['%mail' => $recipient]);
      }
      else {
        $recipients_array[$recipient] = ['uid' => $this->getUidFromEmailAddress($recipient)];
      }
    }

    // Check for no recipients.
    if (empty($errors) && count($recipients_array) < 1) {
      $errors[] = $this->t('There are no recipients for this email.');
    }

    return $errors;
  }

  /**
   * Return an array of email recipients provided by a callback function.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Recipient email addresses.
   *
   * @todo: test
   */
  protected static function callbackRecipients(FormStateInterface $form_state) {
    $callback = $form_state->getValue('recipient_callback');

    if (!empty($callback)) {
      if (function_exists($callback)) {
        $recipients = $callback();

        // Remove uid for backward compatibility.
        if (isset($recipients) && is_array($recipients)) {
          return $recipients;
        }
      }
    }

    return [];
  }

  /**
   * Format recipients as "email (uid)".
   *
   * @param array $recipients
   *   List of recipient email addresses.
   *
   * @return array
   *   Formatted reciepents.
   */
  protected static function formatRecipients(array $recipients) {
    $build = ['#markup' => ''];
    $format = "%s (%s)\n";

    foreach ($recipients as $mail => $data) {
      $uid = $data['uid'] ? new TranslatableMarkup('uid: @uid', ['@uid' => $data['uid']]) : new TranslatableMarkup('no uid');
      $build['#markup'] .= sprintf($format, $mail, $uid);
    }

    return $build;
  }

  /**
   * Get User id by user email address.
   *
   * @param string $email
   *   The email address.
   *
   * @return int
   *   User id, or 0.
   */
  protected static function getUidFromEmailAddress($email) {
    $user = user_load_by_mail($email);
    return $user ? $user->id() : 0;
  }

  /**
   * Generate a preview of the tokenized email for the first in the list.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected static function makePreview(FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();

    $first = array_pop($storage['recipients_array']);
    $uid = isset($first['uid']) ? $first['uid'] : 0;
    $substitutions = PetHelper::getSubstitutions(['uid' => $uid]);

    $token = \Drupal::token();

    $storage['subject_preview'] = $token->replace($values['subject'], $substitutions);

    if (isset($values['body'])) {
      $storage['body_preview'] = $token->replace($values['body'], $substitutions);
    }
    else {
      $storage['body_preview'] = NULL;
    }

    if (isset($values['body_plain'])) {
      $storage['body_preview_plain'] = $token->replace($values['body_plain'], $substitutions);
    }
    else {
      $storage['body_preview_plain'] = NULL;
    }

    $form_state->setStorage($storage);
  }

}
