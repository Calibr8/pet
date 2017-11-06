<?php

namespace Drupal\pet;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\pet\Entity\PetInterface;

/**
 * Defines the storage handler class for Pet entities.
 *
 * This extends the base storage class, adding required special handling for
 * Pet entities.
 *
 * @ingroup pet
 */
class PetStorage extends SqlContentEntityStorage implements PetStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PetInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {pets_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {pets_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PetInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {pets_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('pets_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
