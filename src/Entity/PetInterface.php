<?php

namespace Drupal\pet\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Pet entities.
 *
 * @package pet
 *
 * @todo: complete function documentation.
 */
interface PetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface {

  public function getBcc();

  public function setBcc($bcc);

  /**
   * Get plain body text.
   *
   * @return string
   *   Body text.
   */
  public function getBody();

  /**
   * Set the plain body text.
   *
   * @param string $mail_body
   *   Body text.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setBody($mail_body);

  /**
   * Get HTML body text.
   *
   * @return string
   *   HTML body text.
   */
  public function getBodyHtml();

  /**
   * Set the HTML body text.
   *
   * @param string $mail_body_html
   *   HTML body text.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setBodyHtml($mail_body_html);

  public function getCc();

  public function setCc($cc);

  /**
   * Gets the Pet creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Pet.
   */
  public function getCreatedTime();

  /**
   * Sets the Pet creation timestamp.
   *
   * @param int $timestamp
   *   The Pet creation timestamp.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get HTML body format id.
   *
   * @return string
   *   The format id.
   */
  public function getFormat();

  /**
   * Set the HTML body format.
   *
   * @param string $format
   *   The format id.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setFormat($format);

  public function getRecipientCallback();

  public function setRecipientCallback($recipient_callback);

  public function getReplyTo();

  public function setReplyTo($reply_to);

  public function getSendPlain();

  public function setSendPlain($send_plain);

  public function getSubject();

  public function setSubject($subject);

  /**
   * Get the Pet title.
   *
   * @return string
   *   Pet title.
   */
  public function getTitle();

  /**
   * Set the Pet title.
   *
   * @param string $title
   *   Title.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   Pet entity.
   */
  public function setTitle($title);

  public function isPublished();

  public function setPublished($published);

  /*
   * Class specific functions.
   */

  /**
   * Shortcut to get Pet configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Pet configuration.
   */
  public function getConfig();

  /**
   * Pet logging level.
   *
   * @return int
   *   Logging level, see PET_LOGGER_NONE, PET_LOGGER_ERRORS or PET_LOGGER_ALL.
   */
  public function getLoggingLevel();

  /**
   * Log message, taking Pet logging configuration into account.
   *
   * @param string $message
   *   Message to log, can include string replacement tokens.
   * @param array $replacements
   *   Replacements for the message.
   * @param string $type
   *   Message type: 'error' or 'debug'.
   */
  public function log($message, array $replacements = [], $type = 'debug');

  /**
   * Send PET to one or more recipients.
   *
   * @param array $recipients
   *   Recipient email addresses.
   * @param array $context
   *   Context items to be used for token substitutions.
   *   E.g.:
   *    [
   *     'uid' => NULL,
   *     'nid' => 4,
   *    ]
   *   'uid' will always be set when preparing data, either the user id matching
   *   recipient address, or '0' in case of no match.
   *   Modules providing tokens for PET should implement
   *   hook_pet_substitutions_alter(&$substitutions).
   *
   * @return array
   *   Result value of MailManager::mail() for each mail keyed by recipient
   *   email address.
   */
  public function sendMail(array $recipients, array $context);

}
