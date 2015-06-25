<?php
/**
 * @file
 * PET preview form.
 */

namespace Drupal\pet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pet\PetInterface;
use Drupal\pet\Entity;

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
    $body_description = t('Review and edit standard template before previewing. This will not change the template for future emailings, just for this one. To change the template permanently, go to the template page. You may use the tokens below.');
    $form['to'] = array(
      '#type' => 'email',
      '#title' => t('To'),
      '#required' => TRUE,
      '#description' => t('Enter the recipient(s) separated by lines or commas. A separate email will be sent to each, with token substitution if the email corresponds to a site user.'),
    );
    $form['copies'] = array(
      '#type' => 'details',
      '#title' => t('Copies'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#open' => FALSE,
    );
    $form['copies']['cc'] = array(
      '#type' => 'email',
      '#title' => t('Cc'),
      '#description' => t('Enter any copied emails separated by lines or commas.'),
      '#default_values' => $pet->getCCDefault(),
    );
    $form['copies']['bcc'] = array(
      '#type' => 'email',
      '#title' => t('Bcc'),
      '#description' => t('Enter any blind copied emails separated by lines or commas.'),
      '#default_value' => $pet->getBCCDefault(),
    );
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#required' => TRUE,
      '#default_value' => $pet->getSubject(),
    );
    $form['body'] = array(
      '#type' => 'textarea',
      '#title' => t('Body'),
      '#default_value' => $pet->getMailBody(),
      '#description' => $body_description,
    );
    if (pet_has_mimemail()) {
      $form['mime_mail_options'] = array(
        '#type' => 'details',
        '#title' => t('Plain text body'),
      );
      $form['mime_mail_options']['plain_text_body'] = array(
        '#type' => 'textarea',
        '#title' => t('Plain text body'),
        '#description' => $body_description,
        '#default_value' => $pet->getMailbodyPlain(),
      );
    }

    $form['tokens'] = pet_token_help();

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Preview'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form['subject']['#disabled'] = TRUE;

    return $form;
  }
}
