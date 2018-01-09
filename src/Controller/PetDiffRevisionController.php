<?php

namespace Drupal\pet\Controller;

use Drupal\diff\Controller\PluginRevisionController;
use Drupal\pet\Entity\PetInterface;

/**
 * Class PetDiffRevisionController.
 *
 * Returns responses for Pet revision routes.
 * Based on \Drupal\pet\Controller\PetRevisionController and
 * \Drupal\diff\Controller\NodeRevisionController.
 */
class PetDiffRevisionController extends PluginRevisionController {

  /**
   * Returns a form for revision overview page.
   *
   * @todo This might be changed to a view when the issue at this link is
   *   resolved: https://drupal.org/node/1863906
   *
   * @param \Drupal\pet\Entity\PetInterface $pet
   *   The pet whose revisions are inspected.
   *
   * @return array
   *   Render array containing the revisions table for $pet.
   */
  public function revisionOverview(PetInterface $pet) {
    return $this->formBuilder()->getForm('Drupal\pet\Form\PetDiffRevisionOverviewForm', NULL, $pet);
  }

  /**
   * Returns a table which shows the differences between two node revisions.
   *
   * @param \Drupal\pet\Entity\PetInterface $pet
   *   The node whose revisions are compared.
   * @param int $left_revision
   *   Vid of the node revision from the left.
   * @param int $right_revision
   *   Vid of the node revision from the right.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including HTML tags)
   *   If $filter == 'raw-plain' markdown function is applied to the text
   *   before comparison.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function comparePetRevisions(PetInterface $pet, $left_revision, $right_revision, $filter) {
    $storage = $this->entityTypeManager()->getStorage('pet');
    $route_match = \Drupal::routeMatch();
    /** @var \Drupal\pet\Entity\PetInterface $left_revision */
    $left_revision = $storage->loadRevision($left_revision);
    /** @var \Drupal\pet\Entity\PetInterface $right_revision */
    $right_revision = $storage->loadRevision($right_revision);
    $build = $this->compareEntityRevisions($route_match, $left_revision, $right_revision, $filter);
    return $build;
  }

}
