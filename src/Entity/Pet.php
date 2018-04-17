<?php

namespace Drupal\pet\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\user\UserInterface;

/**
 * Defines the pet entity.
 *
 * @ingroup pet
 *
 * @ContentEntityType(
 *   id = "pet",
 *   label = @Translation("Previewable Email Template"),
 *   handlers = {
 *     "storage" = "Drupal\pet\PetStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pet\PetListBuilder",
 *     "views_data" = "Drupal\pet\Entity\PetViewsData",
 *     "translation" = "Drupal\pet\PetTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\pet\Form\PetForm",
 *       "add" = "Drupal\pet\Form\PetForm",
 *       "edit" = "Drupal\pet\Form\PetForm",
 *       "delete" = "Drupal\pet\Form\PetDeleteForm",
 *     },
 *     "access" = "Drupal\pet\PetAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\pet\PetHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pets",
 *   data_table = "pets_field_data",
 *   revision_table = "pets_revision",
 *   revision_data_table = "pets_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer pet entities",
 *   fieldable = FALSE,
 *   field_ui_base_route = "pet.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/pets/{pet}",
 *     "add-form" = "/admin/structure/pets/add",
 *     "edit-form" = "/admin/structure/pets/{pet}/edit",
 *     "delete-form" = "/admin/structure/pets/{pet}/delete",
 *     "version-history" = "/admin/structure/pets/{pet}/revisions",
 *     "preview-form" = "/admin/structure/pets/{pet}/preview",
 *     "revision" = "/admin/structure/pets/{pet}/revisions/{pet_revision}/view",
 *     "revisions_diff" = "/admin/structure/pets/{pet}/revisions/view/{left_revision}/{right_revision}/{filter}",
 *     "revision_revert" = "/admin/structure/pets/{pet}/revisions/{pet_revision}/revert",
 *     "revision_delete" = "/admin/structure/pets/{pet}/revisions/{pet_revision}/delete",
 *     "translation_revert" = "/admin/structure/pets/{pet}/revisions/{pet_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/pets",
 *   },
 * )
 */
class Pet extends RevisionableContentEntityBase implements PetInterface {

  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * Logging levels.
   */
  const PET_LOGGER_NONE = 0;
  const PET_LOGGER_ERRORS = 1;
  const PET_LOGGER_ALL = 2;

  /**
   * Holds pet configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Holds current logging level.
   *
   * @var int
   */
  protected $loggingLevel;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);

    $this->config = \Drupal::config('pet.settings');

    $loggingLevel = $this->config->get('pet_logging');
    $this->loggingLevel = ($loggingLevel) ?: static::PET_LOGGER_NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the pet owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the pet entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('A short, descriptive title for this email template. It will be used in administrative interfaces, and in page titles and menu items.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The subject line of the email template. May include tokens of any token type specified below.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Body'))
      ->setDescription(t('The plain text body of the email template. May include tokens of any token type specified below.'))
      ->setDefaultValue(NULL)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body_html'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('HTML body'))
      ->setDescription(t('The HTML body of the email template. May include tokens of any token type specified below.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Format'))
      ->setDescription(t('HTML format.'))
      ->setDefaultValue(NULL)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ]);

    $fields['send_plain'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Send only plain text'))
      ->setDescription(t('If checked, only the plain text will be sent. If unchecked both will be sent as multipart mime.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient_callback'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient Callback'))
      ->setDescription(t('The name of a function which will be called to retrieve a list of recipients. This function will be called if the query parameter uid=0 is in the URL. It will be called with one argument, the loaded node (if the PET takes one) or NULL if not. This function should return an array of recipients in the form uid|email, as in 136|bob@example.com. If the recipient has no uid, leave it blank but leave the pipe in. Providing the uid allows token substitution for the user.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reply_to'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Reply-To'))
      ->setDescription(t('Reply-To email address.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cc'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cc'))
      ->setDescription(t('Cc recipients, comma separated.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bcc'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bcc'))
      ->setDescription(t('Bcc recipients, comma separated.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the entity is published.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setTranslatable(TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /*
   * Pet specific fields.
   */

  /**
   * {@inheritdoc}
   */
  public function getBcc() {
    return $this->get('bcc')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBcc($bcc) {
    $this->set('bcc', $bcc);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->get('mail_body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($mail_body) {
    $this->set('mail_body', $mail_body);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyHtml() {
    return $this->get('mail_body_html')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBodyHtml($mail_body_html) {
    $this->set('mail_body_html', $mail_body_html);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCc() {
    return $this->get('cc')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCc($cc) {
    $this->set('cc', $cc);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->get('format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormat($format) {
    $this->set('format', $format);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientCallback() {
    return $this->get('recipient_callback')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientCallback($recipient_callback) {
    $this->set('recipient_callback', $recipient_callback);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplyTo() {
    return $this->get('reply_to')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setReplyTo($reply_to) {
    $this->set('reply_to', $reply_to);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSendPlain() {
    return $this->get('send_plain')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendPlain($send_plain) {
    $this->set('send_plain', $send_plain);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /*
   * Class specific functions.
   */

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoggingLevel() {
    return $this->loggingLevel;
  }

  /**
   * {@inheritdoc}
   */
  public function log($message, array $replacements = [], $type = 'debug') {

    if ($type == 'error' && $this->loggingLevel > static::PET_LOGGER_NONE) {
      \Drupal::logger('pet')->error($message, $replacements);
    }
    elseif ($this->loggingLevel == static::PET_LOGGER_ALL) {
      \Drupal::logger('pet')->debug($message, $replacements);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(array $recipients, array $context) {

    $results = [];

    foreach ($recipients as $recipient) {
      $results[$recipient] = $this->sendSingleMail($recipient, $context);
    }

    return $results;
  }

  /**
   * Send PET to single recipient.
   *
   * @param string $recipient
   *   Recipient email address.
   * @param array $context
   *   Context items to be used for token substitutions.
   *
   * @return array
   *   Result value of MailManager::mail().
   */
  protected function sendSingleMail($recipient, array $context) {
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    $params = [];
    $params['context'] = $context;

    $user = user_load_by_mail($recipient);
    if ($user) {
      // Get user preferred language.
      $preferred_admin_langcode = $user->getPreferredLangcode();
      $langcode = $preferred_admin_langcode ?: $langcode;
    }

    if ($this->hasTranslation($langcode)) {
      $params['pet'] = $this->getTranslation($langcode);
    }
    else {
      // @todo: see if we can get default translation explicitly.
      $params['pet'] = $this;
    }

    $reply = $this->getReplyTo() ?: \Drupal::config('system.site')->get('mail');

    // @see MailManager::mail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE)
    $message = \Drupal::service('plugin.manager.mail')->mail('pet', $this->id(), $recipient, $langcode, $params, $reply);

    if ($message['result']) {
      $this->log("'@title' send to %recipient", ['@title' => $this->getTitle(), '%recipient' => $recipient]);
    }
    else {
      $this->log("Could not send '@title' to %recipient", ['@title' => $this->getTitle(), '%recipient' => $recipient]);
    }

    return $message;
  }

}
