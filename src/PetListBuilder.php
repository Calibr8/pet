<?php

namespace Drupal\pet;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Pet entities.
 *
 * @ingroup pet
 */
class PetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Pet Id');
    $header['title'] = $this->t('Title');
    $header['subject'] = $this->t('Subject');
    $header['published'] = $this->t('Published');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (\Drupal::currentUser()->hasPermission('administer pet settings')) {
      $build['description'] = [
        '#markup' => $this->t('You can manage the settings on the <a href="@url">admin page</a>.', [
          '@url' => \Drupal::urlGenerator()->generateFromRoute('pet.settings'),
        ]),
      ];
    }
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $pid = $entity->id();
    $row['id'] = $pid;

    $url = Url::fromRoute('entity.pet.canonical', ['pet' => $pid], ['attributes' => ['title' => $this->t('Preview')]]);
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $url,
    ];
    $row['subject'] = $entity->getSubject();
    $row['published'] = $entity->isPublished() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
