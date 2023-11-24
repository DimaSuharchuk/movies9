<?php

namespace Drupal\tmdb\enum;

enum TmdbSearchType: string {

  case multi = 'multi';
  case movies = 'movies';
  case tv = 'tv';
  case persons = 'persons';

}
