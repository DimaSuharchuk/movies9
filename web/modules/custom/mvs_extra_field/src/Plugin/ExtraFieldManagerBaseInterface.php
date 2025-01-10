<?php

namespace Drupal\mvs_extra_field\Plugin;

/**
 * Provides the Extra field plugin manager.
 */
interface ExtraFieldManagerBaseInterface {

  /**
   * The component id prefix for every extra_field.
   */
  const EXTRA_FIELD_PREFIX = 'extra_field_';

  /**
   * Exposes the ExtraField plugins to hook_entity_extra_field_info().
   *
   * @return array
   *   The array structure is identical to that of the return value of
   *   \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields().
   *
   * @see hook_entity_extra_field_info()
   */
  public function fieldInfo();

}
