<?php

namespace Drupal\pet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PetSettingsForm.
 *
 * @ingroup pet
 */
class PetSettingsForm extends FormBase {

  /**
   * Logging levels.
   */
  const PET_LOGGER_NONE = 0;
  const PET_LOGGER_ERRORS = 1;
  const PET_LOGGER_ALL = 2;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pet_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()
      ->getEditable('pet.settings')
      ->set('pet_logging', $form_state->getValue('pet_logging'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pet_logging = \Drupal::config('pet.settings')->get('pet_logging');

    $form['logging'] = [
      '#type' => 'details',
      '#title' => $this->t('Pet log settings'),
      '#open' => TRUE,
    ];

    $options = [
      static::PET_LOGGER_ALL => $this->t('Log everything.'),
      static::PET_LOGGER_ERRORS => $this->t('Log errors only.'),
      static::PET_LOGGER_NONE => $this->t('No logging, display error on screen.'),
    ];

    $form['logging']['pet_logging'] = [
      '#type' => 'radios',
      '#title' => $this->t('Log setting'),
      '#options' => $options,
      '#default_value' => $pet_logging,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

}
