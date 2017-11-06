<?php

namespace Drupal\pet\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Pet entities.
 *
 * @ingroup pet
 */
class PetDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->logger('pet')
      ->notice('@type: deleted %title.', [
        '@type' => $this->entity->bundle(),
        '%title' => $this->entity->label(),
      ]);
    drupal_set_message(t('@type %title has been deleted.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.pet.collection');
  }

}
