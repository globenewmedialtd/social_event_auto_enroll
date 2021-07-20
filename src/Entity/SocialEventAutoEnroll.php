<?php

namespace Drupal\social_event_auto_enroll\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;


/**
 * Defines the Social Event Auto Enroll entity.
 *
 * @ConfigEntityType(
 *   id = "social_event_auto_enroll",
 *   label = @Translation("Social Event Auto Enroll Setting"),
 *   config_prefix = "social_event_auto_enroll",
 *   entity_keys = {
 *     "id" = "id",
 *     "auto_enroll" = "auto_enroll",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class SocialEventAutoEnroll extends ConfigEntityBase implements SocialEventAutoEnrollInterface {

  /**
   * The ID of the setting.
   *
   * @var string
   */
  protected $id;

  /**
   * The Auto enroll field.
   *
   * @var string
   */
  protected $auto_enroll;

  /**
   * The id of the event node.
   *
   * @var string
   */
  protected $node;


  /**
   * {@inheritdoc}
   */
  public function getAutoEnroll() {
    return $this->auto_enroll;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoEnroll(bool $auto_enroll) {
    $this->auto_enroll = $auto_enroll;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * {@inheritdoc}
   */
  public function setNode(string $node) {
    $this->node = $node;
    return $this;
  }

}
