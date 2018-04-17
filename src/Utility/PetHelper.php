<?php

namespace Drupal\pet\Utility;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * PetHelper.
 *
 * @package Drupal\pet\Utility
 */
class PetHelper {

  /**
   * Get token substitutions for given user or node context.
   *
   * @param array $context
   *   Context items to be used for token substitutions.
   *   E.g.:
   *    [
   *     'uid' => NULL,
   *     'nid' => 4,
   *    ]
   *
   *   'uid' will always be set when preparing data, either the user id matching
   *   recipient address, or '0' in case of no match.
   *
   * @return array
   *   List of substitutions.
   */
  public static function getSubstitutions(array $context) {

    // Standard substitutions.
    $substitutions['global'] = NULL;

    if (isset($context['uid'])) {
      $user = User::load($context['uid']);
      $substitutions['user'] = $user;
    }

    if (isset($context['nid'])) {
      $node = Node::load($context['nid']);
      $substitutions['node'] = $node;
    }

    // Give modules the opportunity to add their own token types/objects.
    \Drupal::moduleHandler()->alter('pet_substitutions', $context, $substitutions);

    return $substitutions;
  }

  /**
   * Check if Mime Mail is installed and enabled.
   *
   * @return bool
   *   TRUE if enabled.
   */
  public static function hasMimeMail() {
    return \Drupal::moduleHandler()->moduleExists('mimemail');
  }

}
