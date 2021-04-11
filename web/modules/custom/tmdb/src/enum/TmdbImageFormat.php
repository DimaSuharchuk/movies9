<?php

namespace Drupal\tmdb\enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static original()
 * @method static w45()
 * @method static w92()
 * @method static w154()
 * @method static w185()
 * @method static w200()
 * @method static w300()
 * @method static w342()
 * @method static w400()
 * @method static w500()
 * @method static w780()
 */
class TmdbImageFormat extends AbstractEnumeration {

  const original = 'original';

  const w45 = '45';

  const w92 = '92';

  const w154 = '154';

  const w185 = '185';

  const w200 = '200';

  const w300 = '300';

  const w342 = '342';

  const w400 = '400';

  const w500 = '500';

  const w780 = '780';

  /**
   * Returns only formats that start with "w" letter.
   *
   * @return array
   */
  public static function getCompactFormats(): array {
    $formats = self::members();
    unset($formats[self::original]);
    return $formats;
  }

}
