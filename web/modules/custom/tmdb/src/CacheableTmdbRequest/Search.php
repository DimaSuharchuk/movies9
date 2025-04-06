<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal;
use Drupal\mvs\enum\Language;
use Drupal\tmdb\enum\TmdbSearchType;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Search extends CacheableTmdbRequest {

  /**
   * Text from search input.
   *
   * @var string
   */
  private string $query;

  /**
   * Constructs the Tmdb search object.
   *
   * @param TmdbSearchType $search_type
   * @param Language $lang
   * @param string $query
   * @param int $page
   */
  public function __construct(
    private readonly TmdbSearchType $search_type,
    private readonly Language $lang,
    string $query,
    private readonly int $page = 1,
  ) {
    $this->query = $this->filterSearchQuery($query);
  }

  /**
   * @inheritDoc
   */
  protected function request(): array {
    $api = $this->connect->getSearchApi();
    $method = $this->getRequestMethod();

    return $api->$method($this->query, [
      'language' => $this->lang->name,
      'page' => $this->page,
      'include_adult' => TRUE,
    ]);
  }

  /**
   * Normalizes the search query string by replacing unwanted characters.
   *
   * @param string $string
   *   The original search query string entered by the user.
   *
   * @return string
   *   The normalized and cleaned search query.
   */
  private function filterSearchQuery(string $string): string {
    return trim(str_replace(['.', '/'], ' ', $string));
  }

  /**
   * Determines the appropriate API method based on the search type.
   *
   * @return string
   *   The method name that should be called on the TMDb search API.
   */
  private function getRequestMethod(): string {
    return match ($this->search_type) {
      TmdbSearchType::multi => 'searchMulti',
      TmdbSearchType::movies => 'searchMovies',
      TmdbSearchType::tv => 'searchTv',
      TmdbSearchType::persons => 'searchPersons',
    };
  }

  /**
   * Maps the current search type to a corresponding media type string.
   *
   * @return string|null
   *   The media type string: 'movie', 'tv', or 'person', or NULL if unknown.
   */
  private function getMediaType(): ?string {
    return match ($this->search_type) {
      TmdbSearchType::movies => 'movie',
      TmdbSearchType::tv => 'tv',
      TmdbSearchType::persons => 'person',
      default => NULL,
    };
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'search',
      "{$this->query}_$this->page",
      [
        "{$this->lang->name}",
        "{$this->search_type->name}",
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
      $tmdb_entity['media_type'] = $this->getMediaType();
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
  protected function massageAfterLoad(array &$data): void {
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
