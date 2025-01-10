<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Provides the Extra field Display plugin manager.
 */
interface ExtraFieldDisplayManagerInterface {

  /**
   * Exposes the ExtraFieldDisplay plugins to hook_entity_extra_field_info().
   *
   * @return array
   *   The array structure is identical to that of the return value of
   *   \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields().
   *
   * @see hook_entity_extra_field_info()
   *
   * @deprecated in extra_field:8.x-2.0 and is removed from extra_field:3.0.0.
   *   Use ExtraFieldManagerBaseInterface::fieldInfo instead.
   * @see https://www.drupal.org/node/3071507
   */
  public function fieldInfo();

  /**
   * Clears locally cached data.
   */
  public function clearCache();

  /**
   * Appends the renderable data from ExtraField plugins to hook_entity_view().
   *
   * @param array &$build
   *   A renderable array representing the entity content. The module may add
   *   elements to $build prior to rendering. The structure of $build is a
   *   renderable array as expected by drupal_render().
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $viewMode
   *   The view mode the entity is rendered in.
   */
  public function entityView(array &$build, ContentEntityInterface $entity, EntityViewDisplayInterface $display, $viewMode);

}
