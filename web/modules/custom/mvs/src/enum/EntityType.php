<?php

namespace Drupal\mvs\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static node()
 * @method static term()
 * @method static person()
 */
class EntityType extends AbstractEnumeration {

  const node = 'node';

  const term = 'taxonomy_term';

  const person = 'person';

}
