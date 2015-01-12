<?php
/**
 * @file
 *
 */

namespace Drupal\pet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\pet\PetInterface;

/**
 * Defines pet entity class.
 *
 * @ingroup pet
 *
 * @ContentEntityType(
 *   id = "pets",
 *   label = @Translation("Pet Entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pet\Entity\Controller\PetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pet\Form\PetForm",
 *       "edit" = "Drupal\pet\Form\PetForm",
 *       "delete" = "Drupal\pet\Form\PetDeleteForm",
 *     },
 *     "access" = "Drupal\pet\PetAccessControlHandler",
 *   },
 *   base_table = "pets",
 *   admin_persmission = "administer previewable email templates",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "pets.edit",
 *     "delete-form" = "pets.delete",
 *   },
 * )
 *
 */
class Pet extends ContentEntityBase implements PetInterface {

  /** Fields
   *
   * protected $id;
   * protected $title;
   * protected $subject;
   * protected $mail_body;
   * protected $mail_body_plain;
   * protected $cc_default;
   * protected $bcc_default;
   */

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The internal identifier for any templates.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'machine_name',
        'machine_name' => array(
          'source' => array('title'),
        ),
        'weight' => -10,
      ));

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Pets entity.'))
      ->setReadOnly(TRUE);

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module'))
      ->setDescription(t('The name of the providing module if the entity has been defined in code.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('The machine name of the PET.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -11,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The human readable name of the template.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -11,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -11,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The exportable status of the entity.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'weight' => -12,
      ));

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The template subject.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mail Body'))
      ->setDescription(t('The template body.'))
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_string',
        'weight' => -8,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -8,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body_plain'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mail Body Plain'))
      ->setDescription(t('The template body in plain text form.'))
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['send_plain'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Send Plain'))
      ->setDescription(t('If true send email as plain text.'))
      ->setSettings(array(
        'allowed_values' => array(
          '0' => 'No',
          '1' => 'Yes',
        ),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'integer',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['receipient_callback'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Receipient Callback'))
      ->setDescription(t('A recipient callback function, if any.'))
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['form_override'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Form Override'))
      ->setDescription(t('Email to override system from address.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cc_default'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CC Default'))
      ->setDescription(t('Optional cc emails.'))
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bcc_default'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('BCC Default'))
      ->setDescription(t('Optional bcc emails.'))
      ->setSettings(array(
        'default_value' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the pets.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of PET entity.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

} 