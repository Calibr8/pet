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
   * Gets the Pet revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Pet revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Pet revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Pet revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\pet\Entity\PetInterface
   *   The called Pet entity.
   */
  public function setRevisionUserId($uid);

  public function getSubject();

  public function setSubject($subject);

  public function getBody();

  public function setBody($mail_body);

  public function getBodyPlain();

  public function setBodyPlain($mail_body_plain);

  public function getSendPlain();

  public function setSendPlain($send_plain);

  public function getRecipientCallback();

  public function setRecipientCallback($recipient_callback);

  public function getCc();

  public function setCc($cc);

  public function getBcc();

  public function setBcc($bcc);

  public function getReplyTo();

  public function setReplyTo($reply_to);

}
