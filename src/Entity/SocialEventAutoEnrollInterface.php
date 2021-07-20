<?php

namespace Drupal\social_event_auto_enroll\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Welcome Message entities.
 */
interface SocialEventAutoEnrollInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.
  public function getAutoEnroll();

  public function setAutoEnroll(bool $auto_enroll);

  public function getNode();

  public function setNode(string $node);


}
