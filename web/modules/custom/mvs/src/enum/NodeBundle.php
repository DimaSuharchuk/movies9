<?php

namespace Drupal\mvs\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static movie()
 * @method static tv()
 */
class NodeBundle extends AbstractEnumeration {

  const movie = 'movie';

  const tv = 'tv';

}
