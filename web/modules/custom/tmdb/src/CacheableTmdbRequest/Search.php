<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal;
use Drupal\imdb\enum\Language;
use Drupal\tmdb\enum\TmdbSearchType;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Search extends CacheableTmdbRequest {

  private TmdbSearchType $search_type;

  private Language $lang;

  private string $query;

  private int $page = 1;

  private ?string $media_type = NULL;


  /**
   * @param string $search_query
   *   The string by which the search is performed.
   *
   * @return $this
   */
  public function setSearchQuery(string $search_query): self {
    $this->query = $search_query;
    return $this;
  }

  public function setSearchType(TmdbSearchType $search_type): self {
    $this->search_type = $search_type;
    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;
    return $this;
  }

  /**
   * The results are divided into pages of 20 items per page.
   *
   * @param int $page
   *   Number of the page.
   *
   * @return $this
   */
  public function setPage(int $page): self {
    $this->page = $page;
    return $this;
  }


  /**
   * @inheritDoc
   */
  protected function request(): array {
    $api = $this->connect->getSearchApi();

    switch ($this->search_type) {
      case TmdbSearchType::multi:
        $method = 'searchMulti';
        break;
      case TmdbSearchType::movies:
        $method = 'searchMovies';
        $this->media_type = 'movie';
        break;
      case TmdbSearchType::tv:
        $method = 'searchTv';
        $this->media_type = 'tv';
        break;
      case TmdbSearchType::persons:
        $method = 'searchPersons';
        $this->media_type = 'person';
        break;
      default:
        return [];
    }

    return $api->$method($this->query, [
      'language' => $this->lang->key(),
      'page' => $this->page,
      'include_adult' => TRUE,
    ]);
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'search',
      "{$this->query}_{$this->page}",
      [
        "{$this->lang->key()}",
        "{$this->search_type->key()}",
      ],
    );
  }

  /**
   * @inheritDoc
   */
  protected function massageBeforeSave(array $data): array {
    return [
      'total_pages' => $data['total_pages'],
      'total_results' => $data['total_results'],
      'results' => $this->filterResults($data['results']),
    ];
  }


  /**
   * Filter list of TMDb search results.
   *
   * @param array $results
   *   The list.
   *
   * @return array
   */
  private function filterResults(array $results): array {
    $filtered = [];

    foreach ($results as $result) {
      if ($x = $this->filterResult($result)) {
        $filtered[] = $x;
      }
    }

    return $filtered;
  }

  /**
   * Clean up and compress the data we received from the API.
   *
   * @param array $tmdb_entity
   *   Movie, TV or Person teaser data.
   *
   * @return array|null
   *   Cleaned and prepared data in case of success and NULL if some data
   *   didn't pass validation.
   */
  private function filterResult(array $tmdb_entity): ?array {
    $return = [];

    $label = NULL;
    if (isset($tmdb_entity['title'])) {
      $label = $tmdb_entity['title'];
    }
    if (isset($tmdb_entity['name'])) {
      $label = $tmdb_entity['name'];
    }

    if (!$tmdb_entity['id'] || !$label) {
      return NULL;
    }

    if (!isset($tmdb_entity['media_type'])) {
      $tmdb_entity['media_type'] = $this->media_type;
    }
    switch ($tmdb_entity['media_type']) {
      case 'movie':
        $return['t'] = 'm';
        break;

      case 'tv':
        $return['t'] = 't';
        break;

      case 'person':
        $return['t'] = 'p';
        break;

      default:
        return NULL;
    }

    $return['i'] = $tmdb_entity['id'];
    $return['l'] = $tmdb_entity['title'] ?? $tmdb_entity['name'];
    if (!empty($tmdb_entity['poster_path'])) {
      $return['p'] = $tmdb_entity['poster_path'];
    }
    if (!empty($tmdb_entity['profile_path'])) {
      $return['a'] = $tmdb_entity['profile_path'];
    }
    if (!empty($tmdb_entity['release_date'])) {
      $return['y'] = Drupal::service('date_helper')
        ->dateStringToYear($tmdb_entity['release_date']);
    }
    if (!empty($tmdb_entity['first_air_date'])) {
      $return['y'] = Drupal::service('date_helper')
        ->dateStringToYear($tmdb_entity['first_air_date']);
    }
    if (!empty($tmdb_entity['gender'])) {
      $return['g'] = $tmdb_entity['gender'];
    }
    if (!empty($tmdb_entity['known_for_department'])) {
      $return['k'] = $tmdb_entity['known_for_department'];
    }

    return $return;
  }

  /**
   * @inheritDoc
   */
  protected function massageAfterLoad(array &$data) {
    foreach ($data['results'] as $k => $result) {
      switch ($result['t']) {
        case 'm':
          $data['results'][$k] = [
            'id' => $result['i'],
            'type' => 'movie',
            'label' => $result['l'],
            'poster' => $result['p'] ?? NULL,
            'year' => $result['y'] ?? NULL,
          ];
          break;

        case 't':
          $data['results'][$k] = [
            'id' => $result['i'],
            'type' => 'tv',
            'label' => $result['l'],
            'poster' => $result['p'] ?? NULL,
            'year' => $result['y'] ?? NULL,
          ];
          break;

        case 'p':
          $data['results'][$k] = [
            'id' => $result['i'],
            'type' => 'person',
            'name' => $result['l'],
            'profile_path' => $result['a'] ?? NULL,
            'gender' => $result['g'] ?? 0,
            'known_for_department' => $result['k'] ?? NULL,
          ];
          break;
      }
    }
  }

}
