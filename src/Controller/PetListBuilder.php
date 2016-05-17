<?php
namespace Drupal\pet\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\pet\Entity;

/**
 * Class for pet listing page.
 */
class PetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('PET ID');
    $header['title'] = $this->t('Title');
    $header['subject'] = $this->t('Subject');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
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
    $url = Url::fromRoute('pet.preview', array('pet' => $pid));
    $row['label']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->getTitle(),
      '#url' => $url,
    );
    $row['subject'] = $entity->getSubject();
    $row['status'] = $entity->getStatus() == 0 ? t('Custom') : $entity->getStatus();
    return $row + parent::buildRow($entity);
  }

}
