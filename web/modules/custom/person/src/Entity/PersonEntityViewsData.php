<?php

namespace Drupal\person\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Person entities.
 */
class PersonEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    // Additional information for Views integration, such as table joins, can be
    // put here.
    return parent::getViewsData();
  }

}
