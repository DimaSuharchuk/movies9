<?php

namespace Drupal\person;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Person entity.
 *
 * @see \Drupal\person\Entity\PersonEntity.
 */
class PersonEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\person\Entity\PersonEntityInterface $entity */

    switch ($operation) {
      case 'view':
        $permission = 'view published person entities';
        if (!$entity->isPublished()) {
          $permission = 'view unpublished person entities';
        }
        break;

      case 'update':
        $permission = 'edit person entities';
        break;

      case 'delete':
        $permission = 'delete person entities';
        break;

      default:
        // Unknown operation, no opinion.
        return AccessResult::neutral();
    }

    return AccessResult::allowedIfHasPermission($account, $permission);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add person entities');
  }

}
