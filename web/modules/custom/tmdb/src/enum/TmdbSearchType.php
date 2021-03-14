<?php

namespace Drupal\tmdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static multi()
 * @method static movies()
 * @method static tv()
 * @method static persons()
 */
class TmdbSearchType extends AbstractEnumeration {

  const multi = 'multi';

  const movies = 'movies';

  const tv = 'tv';

  const persons = 'persons';

}
