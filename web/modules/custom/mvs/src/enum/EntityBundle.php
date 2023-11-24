<?php

namespace Drupal\mvs\enum;

/**
 * Collect all "bundles" from "types" (look below in "see" tag).
 */
enum EntityBundle: string {

  // Node bundles:
  case movie = 'movie';
  case tv = 'tv';
  // Term vocabulary IDs:
  case genre = 'genre';
  // Person bundle.
  case person = 'person';

}
