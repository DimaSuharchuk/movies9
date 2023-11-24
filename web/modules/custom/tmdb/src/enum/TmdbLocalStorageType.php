<?php

namespace Drupal\tmdb\enum;

enum TmdbLocalStorageType: string {

  case common = 'common';
  case recommendations = 'recommendations';
  case similar = 'similar';
  case videos = 'videos';
  case cast = 'cast';
  case crew = 'crew';

}
