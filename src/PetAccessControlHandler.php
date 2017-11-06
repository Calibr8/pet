<?php

namespace Drupal\pet;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Pet entity.
 *
 * @see \Drupal\pet\Entity\Pet.
 */
class PetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\pet\Entity\PetInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view pet entity');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit pet entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete pet entity');
    }
    // Unknown operation, deny.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add pet entity');
  }

}
