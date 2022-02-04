<?php

namespace Drupal\mvs\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * Collect all "bundles" from "types" (look below in "see" tag).
 *
 * @method static movie()
 * @method static tv()
 * @method static genre()
 * @method static person()
 *
 * @see \Drupal\mvs\enum\EntityType
 * @see \Drupal\mvs\enum\NodeBundle
 * @see \Drupal\mvs\enum\TermBundle
 * @see \Drupal\mvs\enum\PersonBundle
 */
class EntityBundle extends AbstractEnumeration {

  // Node bundles:
  const movie = 'movie';

  const tv = 'tv';

  // Term vocabulary IDs:
  const genre = 'genre';

  // Person bundle.
  const person = 'person';

}
