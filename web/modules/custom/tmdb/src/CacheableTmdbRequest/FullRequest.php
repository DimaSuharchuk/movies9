<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;
use Tmdb\Exception\TmdbApiException;

class FullRequest extends CacheableTmdbRequest {

  private NodeBundle $bundle;

  private int $tmdb_id;

  private Language $lang;

  public function setBundle(NodeBundle $bundle): self {
    $this->bundle = $bundle;

    return $this;
  }

  public function setTmdbId(int $tmdb_id): self {
    $this->tmdb_id = $tmdb_id;

    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;

    return $this;
  }

  /**
   * @inheritDoc
   */
  protected function request(): array {
    switch ($this->bundle) {
      case NodeBundle::movie:
        return $this->nodeApi($this->bundle)->getMovie($this->tmdb_id, [
          'language' => $this->lang->name,
          'append_to_response' => 'recommendations,similar,videos,credits',
        ]);

      case NodeBundle::tv:
        return $this->nodeApi($this->bundle)->getTvshow($this->tmdb_id, [
          'language' => $this->lang->name,
          'append_to_response' => 'recommendations,similar,videos,credits,external_ids',
        ]);
    }

    throw new TmdbApiException(
      TmdbApiException::STATUS_RESOURCE_NOT_FOUND,
      'Wrong bundle used.'
    );
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      $this->bundle->name,
      "{$this->tmdb_id}_{$this->lang->name}"
    );
  }

  /**
   * @inheritDoc
   */
  protected function massageBeforeSave(array $data): array {
    $common = NodeBundle::movie === $this->bundle ? $this->purgeMovieCommonFields($data) : $this->purgeTvCommonFields($data);
    $cast = $this->purgeCastFields($data);
    $crew = $this->purgeCrewFields($data);
    $videos = $this->purgeVideosFields($data);
    $recommendations = $data['recommendations'] ? $this->purgeRecommendationsFields($data['recommendations']) : ['results' => []];
    $similar = $data['similar'] ? $this->purgeRecommendationsFields($data['similar']) : ['results' => []];

    return [
      'common' => $common,
      'cast' => $cast,
      'crew' => $crew,
      'videos' => $videos,
      'recommendations' => $recommendations,
      'similar' => $similar,
    ];
  }

  /**
   * Purge common fields of the movie.
   *
   * @param array $data
   *
   * @return array
   */
  private function purgeMovieCommonFields(array $data): array {
    // Add simple fields.
    $filtered = [
      'site' => $data['homepage'],
      'imdb_id' => $data['imdb_id'],
      'overview' => $data['overview'],
      'poster_path' => $data['poster_path'],
      'production_companies' => $this->allowedFieldsFilter($data['production_companies'], [
        'logo_path',
        'name',
      ]),
      'release_date' => $data['release_date'],
      'runtime' => $data['runtime'],
      'title' => $data['title'],
    ];
    // Check and add collection ID if movie belongs to some.
    if ($data['belongs_to_collection']) {
      $filtered['collection_id'] = $data['belongs_to_collection']['id'];
    }
    // Collect only genres' IDs.
    $filtered['genres_ids'] = array_column($data['genres'], 'id');

    return $filtered;
  }

  /**
   * Purge common fields of the TV show.
   *
   * @param array[] $data
   *
   * @return array[]
   */
  private function purgeTvCommonFields(array $data): array {
    // Add simple fields.
    $filtered = [
      'created_by' => $this->allowedFieldsFilter($data['created_by'], [
        'id',
        'name',
        'gender',
        'profile_path',
      ]),
      'first_air_date' => $data['first_air_date'],
      'site' => $data['homepage'],
      'in_production' => $data['in_production'],
      'last_air_date' => $data['last_air_date'],
      'title' => $data['name'],
      'networks' => $this->allowedFieldsFilter($data['networks'], [
        'name',
        'logo_path',
      ]),
      'number_of_episodes' => $data['number_of_episodes'],
      'number_of_seasons' => $data['number_of_seasons'],
      'overview' => $data['overview'],
      'poster_path' => $data['poster_path'],
      'production_companies' => $this->allowedFieldsFilter($data['production_companies'], [
        'logo_path',
        'name',
      ]),
      'imdb_id' => $data['external_ids']['imdb_id'],
    ];
    // Collect only genres' IDs.
    $filtered['genres_ids'] = array_column($data['genres'], 'id');
    // Get average episode runtime.
    if ($time_arr = $data['episode_run_time']) {
      if (is_array($time_arr)) {
        $filtered['episode_run_time'] = round(array_sum($time_arr) / count($time_arr));
      }
    }

    return $filtered;
  }

  /**
   * Purge fields of cast arrays.
   *
   * @param array[] $data
   *
   * @return array[]
   */
  private function purgeCastFields(array $data): array {
    $allowed_fields = [
      'character',
      'gender',
      'id',
      'name',
      'profile_path',
    ];

    return $this->allowedFieldsFilter($data['credits']['cast'], $allowed_fields);
  }

  /**
   * Purge fields of crew arrays.
   *
   * @param array[] $data
   *
   * @return array[]
   */
  private function purgeCrewFields(array $data): array {
    $allowed_fields = [
      'department',
      'gender',
      'id',
      'job',
      'name',
      'profile_path',
    ];

    return $this->allowedFieldsFilter($data['credits']['crew'], $allowed_fields);
  }

  /**
   * Purge fields of videos' arrays.
   *
   * @param array[] $data
   *
   * @return array[]|array
   */
  private function purgeVideosFields(array $data): array {
    if ($videos = $data['videos']['results']) {
      // Remove non-YouTube videos from TMDb API response.
      foreach ($videos as $key => $video) {
        if ($video['site'] !== 'YouTube') {
          unset($videos[$key]);
        }
      }

      $allowed_fields = [
        'key',
        'name',
        'size',
      ];

      return $this->allowedFieldsFilter($videos, $allowed_fields);
    }

    return [];
  }

}
