<?php

namespace Drupal\pet;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface PetStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Pet revision IDs for a specific Pet.
   *
   * @param \Drupal\pet\Entity\PetInterface $entity
   *   The Pet entity.
   *
   * @return int[]
   *   Pet revision IDs (in ascending order).
   */
  public function revisionIds(PetInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Pet author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Pet revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\pet\Entity\PetInterface $entity
   *   The Pet entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PetInterface $entity);

  /**
   * Unsets the language for all Pet with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
