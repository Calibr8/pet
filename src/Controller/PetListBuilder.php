<?php
/**
 * @file
 * PetListController Class
 */

namespace Drupal\pet\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\pet\Entity;

class PetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('PET ID');
    $header['label'] = $this->t('Label');
    $header['subject'] = $this->t('Subject');
    return $header + parent::buildHeader();
  }

  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('You can manage the settings on the <a href="@adminlink">admin page</a>.', array(
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('pet.settings'),
      )),
    );
    $build['add_pet'] = array(
      '#markup' => t('<p><a href="@addpet">Add previewable email template<a/></p>', array(
        '@addpet' => \Drupal::url('pet.add'),
      )),

    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $pid = $entity->id();
    $row['id'] = $pid;
    //$url = \Drupal::url('pet.edit',array('pet'=>$pid));
    $row['label'] = $entity->getTitle().t(' (Machine name: ') . $entity->getName() .')';
    $row['subject'] = $entity->getSubject();
    return $row + parent::buildRow($entity);
  }

} 