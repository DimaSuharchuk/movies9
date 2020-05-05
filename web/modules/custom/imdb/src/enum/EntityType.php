<?php

namespace Drupal\imdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static node()
 * @method static term()
 */
class EntityType extends AbstractEnumeration {

  const node = 'node';

  const term = 'taxonomy_term';

}
