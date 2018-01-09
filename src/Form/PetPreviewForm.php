<?php

namespace Drupal\pet\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pet\Entity\PetInterface;
use Drupal\pet\Utility\PetHelper;

/**
 * PetPreviewForm.
 *
 * @package Drupal\pet\Form
 *
 * @todo: review, check and test recipient_callback part.
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
          '#description' => $this->t('The subject line of the email template. May include tokens of any token type specified below.'),
        ];

        $form['body'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Body'),
          '#default_value' => isset($storage['body']) ? $storage['body'] : $pet->getBody(),
          '#rows' => 15,
          '#description' => $this->t('The body of the email template. May include tokens of any token type specified below.'),
        ];

        if (PetHelper::hasMimeMail()) {
          $form['mimemail'] = [
            '#type' => 'details',
            '#title' => $this->t('Mime Mail Options'),
            '#open' => TRUE,
          ];

          $form['mimemail']['body_html'] = [
            '#type' => 'text_format',
            '#title' => $this->t('HTML Body'),
            '#default_value' => isset($storage['body_html']['value']) ? $storage['body_html']['value'] : $pet->getBodyHtml(),
            '#rows' => 15,
            '#format' => isset($storage['body_html']['format']) ? $storage['body_html']['format'] : $pet->getFormat(),
            '#description' => $this->t('The plain text body of the email template. May include tokens of any token type specified below. If left empty Mime Mail will create a plain text version of the email.'),
          ];

          $form['mimemail']['send_plain'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Send only plain text'),
            '#default_value' => isset($storage['send_plain']) ? $storage['send_plain'] : $pet->getSendPlain(),
            '#description' => $this->t('Send email as plain text only. If checked, only the plain text here will be sent. If unchecked both will be sent as multipart mime.'),
          ];
        }

        $form['tokens'] = pet_token_help();

        $form['note'] = [
          '#markup' => '<p>' . $this->t('Review and edit the template before previewing. This will not change the template for future emails, just for this preview.') . '</p>',
        ];

        $form['preview'] = [
          '#type' => 'submit',
          '#value' => $this->t('Preview'),
        ];
        break;

      case 2:

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

        if (PetHelper::hasMimeMail()) {
          $form['body_html'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Raw HTML body'),
            '#rows' => 15,
            '#value' => $storage['body_html']['value'],
            '#disabled' => TRUE,
          ];

          if (isset($storage['send_plain']) && !$storage['send_plain']) {
            $form['body_preview_html'] = [
              '#prefix' => '<div class="form-item form-disabled form-type-pet">',
              '#suffix' => '</div>',
              '#type' => 'inline_template',
              '#template' => '<label>{% trans %}Processed HTML Body{% endtrans %}</label><div class="preview">{{ preview|raw }}</div>',
              '#context' => [
                'preview' => $storage['body_preview_html'],
              ],
            ];

            $form['#attached'] = [
              'library' => ['pet/pet.preview'],
            ];
          }


        }

        $form['body'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Body'),
          '#rows' => 15,
          '#value' => $storage['body_preview'],
          '#disabled' => TRUE,
        ];

        $form['back'] = [
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#submit' => ['::stepBack'],
        ];

        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Send email'),
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

    if ($form_state->getStorage()['step'] == 1) {
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
        $storage['body_html'] = isset($values['body_html']) ? $values['body_html'] : NULL;
        $storage['send_plain'] = isset($values['send_plain']) ? $values['send_plain'] : NULL;
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

        /** @var \Drupal\pet\Entity\PetInterface $pet */
        $pet = $storage['pet'];

        $pet->setSubject($storage['subject']);
        if (PetHelper::hasMimeMail() && !empty($storage['body'])) {
          // Let Mime Mail create final plain text version.
          $pet->setBody($storage['body']);
        }
        $pet->setBodyHtml($storage['body_html']['value']);
        $pet->setSendPlain($storage['send_plain']);
        $pet->setReplyTo($storage['reply_to']);
        $pet->setCc($storage['cc']);
        $pet->setBcc($storage['bcc']);
        $pet->sendMail($recipients, []);

        $storage['step'] = 1;
        drupal_set_message($this->t('Email(s) sent'));

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

    $storage = $form_state->getStorage();
    $storage['step'] = 1;
    $form_state->setStorage($storage);
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

    $first = reset($storage['recipients_array']);
    $uid = isset($first['uid']) ? $first['uid'] : 0;
    $substitutions = PetHelper::getSubstitutions(['uid' => $uid]);

    $token = \Drupal::token();

    $storage['subject_preview'] = $token->replace($values['subject'], $substitutions);
    $storage['body_preview'] = $token->replace($values['body'], $substitutions);

    if (PetHelper::hasMimeMail()) {
      $storage['body_preview_html'] = $token->replace($values['body_html']['value'], $substitutions);
      $storage['body_preview_html'] = check_markup($storage['body_preview_html'], $values['body_html']['format']);

      // @see MimeMailFormatHelper::mimeMailHtmlBody()
      if (empty($storage['body_preview'])) {
        // @todo Remove once filter_xss() can handle direct descendant selectors in inline CSS.
        // @see http://drupal.org/node/1116930
        // @see http://drupal.org/node/370903
        $storage['body_preview'] = MailFormatHelper::htmlToText($storage['body_preview_html']);
      }
    }

    $form_state->setStorage($storage);
  }

}
