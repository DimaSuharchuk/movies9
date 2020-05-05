<?php

namespace Drupal\imdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Collect all "bundles" from "types" (look below in "see" tag).
 *
 * @method static movie()
 * @method static tv()
 * @method static genre()
 *
 * @see \Drupal\imdb\enum\EntityType
 * @see \Drupal\imdb\enum\NodeBundle
 * @see \Drupal\imdb\enum\TermBundle
 */
class EntityBundle extends AbstractEnumeration {

  // Node bundles:
  const movie = 'movie';

  const tv = 'tv';

  // Term vocabulary IDs:
  const genre = 'genre';

}
