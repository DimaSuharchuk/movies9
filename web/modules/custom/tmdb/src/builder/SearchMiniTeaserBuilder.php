<?php

namespace Drupal\tmdb\builder;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\person\Avatar;
use Drupal\tmdb\enum\TmdbImageFormat;

class SearchMiniTeaserBuilder {

  use StringTranslationTrait;

  protected Avatar $avatar;

  public function __construct(Avatar $avatar) {
    $this->avatar = $avatar;
  }

  /**
   * Build a list of renderable arrays with mini teaser for Movie, TV or Person.
   *
   * @param array $teasers_data
   *   Prepared data from "cacheable search".
   *
   * @return array
   *   List of renderable arrays.
   */
  public function build(array $teasers_data): array {
    $build = [];

    foreach ($teasers_data as $item) {
      switch ($item['type']) {
        case 'movie':
          $build[] = $this->buildMovie($item);
          break;

        case 'tv':
          $build[] = $this->buildTv($item);
          break;

        case 'person':
          $build[] = $this->buildPerson($item);
          break;
      }
    }

    return $build;
  }

  /**
   * Build mini teaser for Movie.
   *
   * @param array $movie_data
   *
   * @return array
   */
  public function buildMovie(array $movie_data): array {
    return [
      '#theme' => 'movie_mini_teaser',
      '#tmdb_id' => $movie_data['id'],
      '#title' => $movie_data['label'],
      '#poster' => $movie_data['poster'],
      '#year' => $movie_data['year'],
    ];
  }

  /**
   * Build mini teaser for TV series.
   *
   * @param array $tv_data
   *
   * @return array
   */
  public function buildTv(array $tv_data): array {
    return [
      '#theme' => 'tv_mini_teaser',
      '#tmdb_id' => $tv_data['id'],
      '#title' => $tv_data['label'],
      '#poster' => $tv_data['poster'],
      '#year' => $tv_data['year'],
    ];
  }

  /**
   * Build mini teaser for Person.
   *
   * @param array $person_data
   *
   * @return array
   */
  public function buildPerson(array $person_data): array {
    return [
      '#theme' => 'person_mini_teaser',
      '#tmdb_id' => $person_data['id'],
      '#name' => $person_data['name'],
      '#avatar' => $this->avatar->build($person_data, TmdbImageFormat::w92),
      '#department' => !empty($person_data['known_for_department']) ? $this->t($person_data['known_for_department'], [], ['context' => 'known for']) : '',
    ];
  }

}
