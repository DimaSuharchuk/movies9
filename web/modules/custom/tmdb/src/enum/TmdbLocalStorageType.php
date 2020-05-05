<?php

namespace Drupal\tmdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static common()
 * @method static recommendations()
 * @method static similar()
 * @method static videos()
 * @method static cast()
 * @method static crew()
 */
class TmdbLocalStorageType extends AbstractEnumeration {

  const common = 'common';

  const recommendations = 'recommendations';

  const similar = 'similar';

  const videos = 'videos';

  const cast = 'cast';

  const crew = 'crew';

}
