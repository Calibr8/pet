<?php
/**
 * @file
 * PetForm class
 */

namespace Drupal\pet\Form;

use Drupal\pet\Entity;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

class PetForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    parent::buildForm($form, $form_state);

    $pet = $this->entity;

    $form['title'] = array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#description' => t('A short, descriptive title for this email template. It will be used in administrative interfaces, and in page titles and menu items.'),
      '#required' => TRUE,
      '#default_value' => $pet->getTitle(),
    );

    $form['name'] = array(
      '#type' => 'machine_name',
      '#title' => t('machine name'),
      '#default_value' => $pet->getName(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        //'exists' => 'pet_load',
        'source' => array('title'),
      ),
      '#description' => t('A unique machine-readable name for this pet. It must only contain lowercase letters, numbers, and underscores.'),
      '#required' => TRUE,
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#description' => t('The subject line of the email template. May include tokens of any token type specified below.'),
      '#required' => TRUE,
      '#default_value' => $pet->getSubject(),
    );
    $form['mail_body'] = array(
      '#type' => 'textarea',
      '#title' => t('Body'),
      '#default_value' => $pet->getMailbody(),
      '#description' => t('The body of the email template. May include tokens of any token type specified below.'),
    );
    $form['mimemail'] = array(
      '#type' => 'fieldset',
      '#title' => t('Mime Mail options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    // @todo : #2366853 - Mime mail integration
    if (pet_has_mimemail()) {
      $form['mimemail']['mail_body_plain'] = array(
        '#type' => 'textarea',
        '#title' => t('Plain text body'),
        '#default_value' => $pet->getMailbodyPlain(),
        '#description' => t('The plain text body of the email template. May include tokens of any token type specified below. If left empty Mime Mail will use <a href="@url">drupal_html_to_text()</a> to create a plain text version of the email.', array('@url' => 'http://api.drupal.org/api/drupal/includes%21mail.inc/function/drupal_html_to_text/7')),
      );
      $form['mimemail']['send_plain'] = array(
        '#type' => 'checkbox',
        '#title' => t('Send only plain text'),
        '#default_value' => $pet->getCheckPlain(),
        '#description' => t('Send email as plain text only. If checked, only the plain text here will be sent. If unchecked both will be sent as multipart mime.'),
      );
    }
    else {
      $form['mimemail']['#description'] = t('HTML email support is most easily provided by the <a href="@url">Mime Mail</a> module, which must be installed and enabled.', array('@url' => 'http://drupal.org/project/mimemail'));
    }
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Additional options'),
      '#open' => FALSE,
      '#access' => \Drupal::currentUser()
        ->hasPermission('administer previewable email templates'),
    );
    $config = \Drupal::config('system.site');
    $form['advanced']['from_override'] = array(
      '#type' => 'textfield',
      '#title' => t('From override'),
      '#default_value' => $pet->getFromOverride(),
      '#description' => t('By default, the From: address is the site address, which is %site_mail and which is configurable on the core <a href="@site_url">site information page</a>. You may specify a different From: address here, which will override the system default for this PET.', array(
          '%site_mail' => $config->get('mail', ini_get('sendmail_from')),
          '@site_url' => \Drupal::url('system.site_information_settings')
        )),
      '#maxlength' => 255,
      '#required' => FALSE,
    );
    $form['advanced']['cc_default'] = array(
      '#type' => 'textarea',
      '#title' => t('CC default'),
      '#rows' => 3,
      '#default_value' => $pet->getCCDefault(),
      '#description' => t('Emails to be copied by default for each mail sent to recipient. Enter emails separated by lines or commas.'),
      '#required' => FALSE,
    );
    $form['advanced']['bcc_default'] = array(
      '#type' => 'textarea',
      '#title' => t('BCC default'),
      '#rows' => 3,
      '#default_value' => $pet->getBCCDefault(),
      '#description' => t('Emails to be blind copied by default for each mail sent to recipient. Enter emails separated by lines or commas.'),
      '#required' => FALSE,
    );
    $form['advanced']['recipient_callback'] = array(
      '#type' => 'textfield',
      '#title' => t('Recipient callback'),
      '#default_value' => $pet->getReceipientCallback(),
      '#description' => t('The name of a function which will be called to retrieve a list of recipients. This function will be called if the query parameter uid=0 is in the URL. It will be called with one argument, the loaded node (if the PET takes one) or NULL if not. This function should return an array of recipients in the form uid|email, as in 136|bob@example.com. If the recipient has no uid, leave it blank but leave the pipe in. Providing the uid allows token substitution for the user.'),
      '#maxlength' => 255,
    );

    // @todo : #2366851 token integration
    //$form['tokens'] = pet_token_help();

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save template'),
    );

    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => 'admin/structure/pets',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pet = $this->entity;
    $status = $pet->save();
    $t_args = array('%name' => $pet->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The email template %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The email template %name has been added.', $t_args));
    }
    $form_state->setRedirect('pet.list');
  }
}
