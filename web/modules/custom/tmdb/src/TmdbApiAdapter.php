<?php

namespace Drupal\tmdb;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\node\NodeInterface;
use Drupal\tmdb\CacheableTmdbRequest\EpisodeImdbId;
use Drupal\tmdb\CacheableTmdbRequest\FindByImdbId;
use Drupal\tmdb\CacheableTmdbRequest\FullRequest;
use Drupal\tmdb\CacheableTmdbRequest\Genres;
use Drupal\tmdb\CacheableTmdbRequest\MovieCollection;
use Drupal\tmdb\CacheableTmdbRequest\Person;
use Drupal\tmdb\CacheableTmdbRequest\Recommendations;
use Drupal\tmdb\CacheableTmdbRequest\Search;
use Drupal\tmdb\CacheableTmdbRequest\Seasons;
use Drupal\tmdb\CacheableTmdbRequest\Similar;
use Drupal\tmdb\enum\TmdbSearchType;

class TmdbApiAdapter {

  /**
   * Get movie collection and teasers info from TMDb API.
   *
   * @param int $movie_tmdb_id
   *   Field TMDb ID of node movie.
   * @param Language $lang
   *
   * @return array|null
   */
  public function getMovieCollection(int $movie_tmdb_id, Language $lang): ?array {
    // Fetch movie common fields first.
    if (
      ($common = $this->getCommonFieldsByTmdbId(NodeBundle::movie, $movie_tmdb_id, $lang))
      && isset($common['collection_id'])
    ) {
      return new MovieCollection($common['collection_id'], $lang)->response();
    }

    return NULL;
  }

  /**
   * Get YouTube videos for movie or TV show.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   *
   * @return array
   */
  public function getVideos(NodeBundle $bundle, int $tmdb_id, Language $lang): array {
    return $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang)['videos'] ?? [];
  }

  /**
   * Get movie or TV cast persons from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function getCast(NodeBundle $bundle, int $tmdb_id): array {
    return $this->getFullInfoByTmdbId($bundle, $tmdb_id, Language::en)['cast'] ?? [];
  }

  /**
   * Get movie or TV crew persons from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function getCrew(NodeBundle $bundle, int $tmdb_id): array {
    return $this->getFullInfoByTmdbId($bundle, $tmdb_id, Language::en)['crew'] ?? [];
  }

  /**
   * Fetch from TMDb API or TmdbLocalStorage recommendations for some movie or
   * TV by TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param int $page
   *   Recommendations responses have a maximum of 20 teasers on each page.
   *   $page is the page in TMDb API that we want to get.
   *
   * @return array|null
   */
  public function getRecommendations(NodeBundle $bundle, int $tmdb_id, Language $lang, int $page): ?array {
    if (
      $page === 1
      && $full_info = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang, TRUE)
    ) {
      return $full_info['recommendations'] ?? [];
    }

    return new Recommendations($bundle, $tmdb_id, $lang, $page)->response();
  }

  /**
   * Fetch from TMDb API or TmdbLocalStorage similar for some movie or TV by
   * TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param int $page
   *   Similar responses have a maximum of 20 teasers on each page.
   *   $page is the page in TMDb API that we want to get.
   *
   * @return array|null
   */
  public function getSimilar(NodeBundle $bundle, int $tmdb_id, Language $lang, int $page): ?array {
    if (
      $page === 1
      && $full_info = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang, TRUE)
    ) {
      return $full_info['similar'] ?? [];
    }

    return new Similar($bundle, $tmdb_id, $lang, $page)->response();
  }

  /**
   * Search movie or TV show in TMDb API by IMDb ID.
   *
   * @param string $imdb_id
   *
   * @return array|null
   *   [NodeBundle, int].
   */
  public function getTmdbIdByImdbId(string $imdb_id): ?array {
    return new FindByImdbId($imdb_id)->response();
  }

  /**
   * Get IMDb ID by TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param bool $only_cached
   *   If TRUE, the method will return only a cached result if it exists and
   *   doesn't send request to TMDb API.
   *
   * @return string
   */
  public function getImdbId(NodeBundle $bundle, int $tmdb_id, bool $only_cached = FALSE): string {
    return $this->getCommonFieldsByTmdbId($bundle, $tmdb_id, Language::en, $only_cached)['imdb_id'] ?? '';
  }

  /**
   * Get common info for movie or TV.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE, the method will return only a cached result if it exists and
   *   doesn't send request to TMDb API.
   *
   * @return array|null
   */
  public function getCommonFieldsByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang, bool $only_cached = FALSE): ?array {
    $name = "{$bundle->name}_{$tmdb_id}_{$lang->name}_$only_cached";
    $data = &drupal_static(__METHOD__ . $name);

    if (is_null($data)) {
      $data = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang, $only_cached)['common'] ?? [];
    }

    return $data;
  }

  /**
   * Get the value of some common movie or TV field from TMDb API or cached
   * file.
   *
   * @param $field_name
   *   Name of common field in TMDbLocalStorage (some fields key rewrote with
   *   more logical name).
   *
   * @return mixed|null
   *   Field value.
   */
  public function getCommonFieldValue(NodeInterface $node, string $field_name): mixed {
    $bundle = NodeBundle::from($node->bundle());
    $tmdb_id = $node->{'field_tmdb_id'}->value;
    $lang = Language::from($node->language()->getId());
    $common = $this->getCommonFieldsByTmdbId($bundle, $tmdb_id, $lang);

    return $common[$field_name] ?? NULL;
  }

  /**
   * Get all Movie or TV info from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE, the method will return only a cached result if it exists and
   *   doesn't send request to TMDb API.
   *
   * @return array
   */
  public function getFullInfoByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang, bool $only_cached = FALSE): array {
    $name = "{$bundle->name}_{$tmdb_id}_{$lang->name}_$only_cached";
    $data = &drupal_static(__METHOD__ . $name);

    if (is_null($data)) {
      $data = [];
      $query = new FullRequest($bundle, $tmdb_id, $lang);

      if (!$only_cached || $query->hasCache()) {
        $data = $query->response();
      }
    }

    return $data;
  }

  /**
   * Get season and nested episodes info form TMDb API.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE, the method will return only a cached result if it exists and
   *   doesn't send request to TMDb API.
   *
   * @return array|null
   */
  public function getSeason(int $tv_tmdb_id, int $season_number, Language $lang, bool $only_cached = FALSE): ?array {
    $query = new Seasons($tv_tmdb_id, $season_number, $lang);

    if (!$only_cached || $query->hasCache()) {
      return $query->response();
    }

    return NULL;
  }

  /**
   * Get episode IMDb ID from TMDb API.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   * @param bool $only_cached
   *   If TRUE, the method will return only a cached result if it exists and
   *   doesn't send request to TMDb API.
   *
   * @return string|null
   */
  public function getEpisodeImdbId(int $tv_tmdb_id, int $season_number, int $episode_number, bool $only_cached = FALSE): ?string {
    $query = new EpisodeImdbId($tv_tmdb_id, $season_number, $episode_number);

    if (!$only_cached || $query->hasCache()) {
      return $query->response()['imdb_id'];
    }

    return NULL;
  }

  /**
   * Get all genres for type "Movie" from TMDb API.
   *
   * @param Language $lang
   *   The language in which the genres will be returned.
   *
   * @return array
   */
  public function getMovieGenres(Language $lang): array {
    return new Genres(NodeBundle::movie, $lang)->response();
  }

  /**
   * Get all genres for type "TV" from TMDb API.
   *
   * @param Language $lang
   *   The language in which the genres will be returned.
   *
   * @return array
   */
  public function getTvGenres(Language $lang): array {
    return new Genres(NodeBundle::tv, $lang)->response();
  }

  /**
   * Get person info.
   *
   * @param int $tmdb_id
   *   Person TMDb ID.
   * @param Language $lang
   *
   * @return array|null
   */
  public function getPerson(int $tmdb_id, Language $lang): ?array {
    return new Person($tmdb_id, $lang)->response();
  }

  /**
   * Perform a search.
   *
   * @param string $search_string
   *   A string of search in movies, TV series, or persons.
   * @param TmdbSearchType $search_type
   * @param Language $lang
   * @param int $page
   *
   * @return array|null
   */
  public function search(string $search_string, TmdbSearchType $search_type, Language $lang, int $page = 1): ?array {
    return new Search($search_type, $lang, $search_string, $page)->response();
  }

}
