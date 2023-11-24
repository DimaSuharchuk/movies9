<?php

namespace Drupal\tmdb\enum;

enum TmdbImageFormat: string {

  case original = 'original';
  case w45 = '45';
  case w92 = '92';
  case w154 = '154';
  case w185 = '185';
  case w200 = '200';
  case w300 = '300';
  case w342 = '342';
  case w400 = '400';
  case w500 = '500';
  case w780 = '780';

  /**
   * Returns only formats that start with "w" letter.
   *
   * @return array
   */
  public static function getCompactFormats(): array {
    return array_filter(self::cases(), fn($format) => $format !== self::original);
  }

  public static function tryFromKey(int|string $key): ?static {
    foreach (self::cases() as $case) {
      if ($case->name === $key) {
        return $case;
      }
    }

    return NULL;
  }

}
