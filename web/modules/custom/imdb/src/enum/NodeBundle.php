<?php

namespace Drupal\imdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static movie()
 * @method static tv()
 */
class NodeBundle extends AbstractEnumeration {

  const movie = 'movie';

  const tv = 'tv';

}
