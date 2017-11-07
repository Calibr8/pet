<?php

namespace Drupal\pet\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Pet revision.
 *
 * @ingroup pet
 */
class PetRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Pet revision.
   *
   * @var \Drupal\pet\Entity\PetInterface
   */
  protected $revision;

  /**
   * The Pet storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $petStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new PetRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->petStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('pet'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pet_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $revision_date = \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime(), 'short');
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => $revision_date]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.pet.version_history', ['pet' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pet_revision = NULL) {
    $this->revision = $this->petStorage->loadRevision($pet_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->petStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Pet: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    drupal_set_message(
        t('Revision from %revision-date of Pet %title has been deleted.', [
          '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime(), 'short'),
          '%title' => $this->revision->label(),
        ])
    );
    $form_state->setRedirect(
      'entity.pet.canonical',
       ['pet' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {pets_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.pet.version_history',
         ['pet' => $this->revision->id()]
      );
    }
  }

}
