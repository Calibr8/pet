<?php
/**
 * @file
 * PetListController Class
 */

namespace Drupal\pet\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class PetListBuilder extends EntityListBuilder{

  /**
   * {@inheritdoc}
   */
  public function buildHeader(){
    $header['id'] = $this->t('PET ID');
    $header['name'] = $this->t('Name');
    $header['subject']= $this->t('Subject');
    return $header + parent::buildHeader();
  }

  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('You can manage the settings on the <a href="@adminlink">admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()->generateFromRoute('pet.settings'),
      )),
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

} 