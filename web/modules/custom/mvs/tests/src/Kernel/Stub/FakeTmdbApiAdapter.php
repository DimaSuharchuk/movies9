<?php

namespace Drupal\Tests\mvs\Kernel\Stub;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbApiAdapter;

class FakeTmdbApiAdapter extends TmdbApiAdapter {

  public function getCommonFieldsByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang, bool $only_cached = FALSE): ?array {
    return [
      'title' => $lang === Language::en ? 'Fake Title' : 'Fake title UA',
      'imdb_id' => 'tt1234567',
      'poster_path' => '/fake.jpg',
      'genres_ids' => [1, 2],
    ];
  }

}
