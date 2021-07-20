<?php

namespace Drupal\social_event_auto_enroll;

use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\social_event_auto_enroll\Entity\SocialEventAutoEnrollInterface;
use Drupal\social_event_auto_enroll\Entity\SocialEventAutoEnroll;
use Drupal\Core\Datetime\DrupalDateTime;


/**
 * Defines Social Event Auto Enroll Service.
 */
class SocialEventAutoEnrollService {

  /** Entity type manager.
    *
    * @var \Drupal\Core\Entity\EntityTypeManagerInterface
    */
  protected $entityTypeManager;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;  

  /**
   * SocialVirtualEventBBBCommonService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Get group content and look for events with auto enroll enabled
   *
   * @return []
   *   Array of allowed recording access options.
   */
  public function newGroupMemberAutoEnroll($group_id, $uid) {

    //$group->getEntitiesOfType()

    if ($group_id !== NULL) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager
        ->getStorage('group')
        ->load($group_id);
    }
    
    if ($group instanceOf GroupInterface) {
      $plugin_id = 'group_node:event';
      $events = $group->getContentEntities($plugin_id);
      foreach ($events as $event) {
        if ($event instanceof NodeInterface) {         
          $nid = $event->id();
          if ($this->isEventWithAutoEnrollEnabled($nid)) {      
            $this->autoEnroll($nid, $uid);
          }

        }
      }
    }
   
  }

  /**
   * Get group content and look for events with auto enroll enabled
   *
   * @return 
   *   TRUE or FALSE
   */
  protected function isEventWithAutoEnrollEnabled($nid) {
 
    $event = $this->entityTypeManager->getStorage('node')->load($nid);
    $auto_enroll = FALSE;

    $social_event_auto_enroll = \Drupal::service('social_event_auto_enroll'); 
    if ($config = $social_event_auto_enroll->getSocialEventAutoEnrollConfig($nid)) {
      $auto_enroll = $config->getAutoEnroll();  
    }

    $end_date = $event->field_event_date_end->date;
    $timestamp_end_date = $end_date->getTimestamp();
    $timestamnp_current = \Drupal::time()->getCurrentTime();  
    $event_terminated = FALSE;
    if ($timestamnp_current > $timestamp_end_date) {
      $event_terminated = TRUE;
    }

    if ($auto_enroll && !$event_terminated) {
      return TRUE;
    }

    return FALSE;

  }

  /**
   * Delete enrollments
   *
   * @return 
   *   TRUE or FALSE
   */
  public function removeAutoEnrollEnabled($group_id, $uid) {

    if ($group_id !== NULL) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager
        ->getStorage('group')
        ->load($group_id);
    }
    
    if ($group instanceOf GroupInterface) {
      $plugin_id = 'group_node:event';
      $events = $group->getContentEntities($plugin_id);
      foreach ($events as $event) {
        if ($event instanceof NodeInterface) {
          $nid = $event->id();
          if ($this->isEventWithAutoEnrollEnabled($nid)) {         
            $this->deleteEnrollments($nid, $uid);
          }
        }
      }
    }   

  }

  /**
   * Delete enrollments
   */
  protected function deleteEnrollments($nid, $uid) {
    $event_enrollment = $this->entityTypeManager->getStorage('event_enrollment');
    $event = $this->entityTypeManager->getStorage('node');
    
    // Check if user has enrolled the event
    $enrolled = $event_enrollment->loadByProperties([
      'field_account' => $uid,
      'field_event' => $nid,
      'field_enrollment_status' => 1,
    ]);
  
    foreach($enrolled as $key => $record) {
      if ($record instanceof EventEnrollmentInterface) {
        if ($this->isEventWithAutoEnrollEnabled($record->field_event->target_id)) {
          $delete_enrolled[$key] = $key; 
        }
      }
    }
  
    $itemsToDelete = $event_enrollment->loadMultiple($delete_enrolled);
  
    // Loop through our entities and deleting them by calling by delete method.
    foreach ($itemsToDelete as $item) {
      $item->delete();
    }
  }

  /**
   * Create enrollments
   */
  protected function autoEnroll($nid, $uid) {

    $event_enrollment = $this->entityTypeManager->getStorage('event_enrollment');

    // For security reason we check first for existing enrollment
    // to avoid duplicates
    // Check if user has enrolled the event
    $enrolled = $event_enrollment->loadByProperties([
      'field_account' => $uid,
      'field_enrollment_status' => 1,
      'field_event' => $nid,
    ]);
    
    if (!$enrolled) {

      $enrollment = EventEnrollment::create([
        'user_id' => $uid,
        'field_event' => $nid,
        'field_enrollment_status' => '1',
        'field_account' => $uid,
      ]);
      $enrollment->save(); 
    }  

  }

  /*
   * Get config
   */
  public function getSocialEventAutoEnrollConfig($nid) {

    $social_event_auto_enroll = $this->entityTypeManager
      ->getStorage('social_event_auto_enroll')
      ->load($nid);
    
    if ($social_event_auto_enroll instanceof SocialEventAutoEnrollInterface) {
      return $social_event_auto_enroll;
    }

    return FALSE;

  }

  /*
   * Create config
   */
  public function createSocialEventAutoEnrollConfig($nid, $auto_enroll) {

    $config = SocialEventAutoEnroll::create([
      'id' => $nid,
    ]);
    $config->setAutoEnroll($auto_enroll);
    $config->setNode($nid);
    $config->save();

  }
  
  /*
   * Create config
   */
  public function updateSocialEventAutoEnrollConfig($nid, $auto_enroll) {

    $social_event_auto_enroll = $this->entityTypeManager
      ->getStorage('social_event_auto_enroll')
      ->load($nid);
    
    if ($social_event_auto_enroll instanceof SocialEventAutoEnrollInterface) {
      $social_event_auto_enroll->setAutoEnroll($auto_enroll);
      $social_event_auto_enroll->save();
    }

  }  

}
