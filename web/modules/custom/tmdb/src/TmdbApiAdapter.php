<?php

namespace Drupal\tmdb;

use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\CacheableTmdbRequest\EpisodeImdbId;
use Drupal\tmdb\CacheableTmdbRequest\FindByImdbId;
use Drupal\tmdb\CacheableTmdbRequest\FullRequest;
use Drupal\tmdb\CacheableTmdbRequest\Genres;
use Drupal\tmdb\CacheableTmdbRequest\MovieCollection;
use Drupal\tmdb\CacheableTmdbRequest\Recommendations;
use Drupal\tmdb\CacheableTmdbRequest\Seasons;
use Drupal\tmdb\CacheableTmdbRequest\Similar;

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
    if ($common = $this->getCommonFieldsByTmdbId(NodeBundle::movie(), $movie_tmdb_id, $lang)) {
      // Get collection info from TMDb API.
      if (isset($common['collection_id'])) {
        return (new MovieCollection())
          ->setMovieTmdbId($common['collection_id'])
          ->setLanguage($lang)
          ->response();
      }

      return NULL;
    }

    return NULL;
  }

  /**
   * Get TouTube videos for movie or TV show.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   *
   * @return array|null
   */
  public function getVideos(NodeBundle $bundle, int $tmdb_id, Language $lang): ?array {
    if ($response = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang)) {
      return $response['videos'];
    }

    return NULL;
  }

  /**
   * Get movie or TV cast persons from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array|null
   */
  public function getCast(NodeBundle $bundle, int $tmdb_id): ?array {
    if ($response = $this->getFullInfoByTmdbId($bundle, $tmdb_id, Language::en())) {
      return $response['cast'];
    }

    return NULL;
  }

  /**
   * Get movie or TV crew persons from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array|null
   */
  public function getCrew(NodeBundle $bundle, int $tmdb_id): ?array {
    if ($response = $this->getFullInfoByTmdbId($bundle, $tmdb_id, Language::en())) {
      return $response['crew'];
    }

    return NULL;
  }

  /**
   * Fetch from TMDb API or TmdbLocalStorage recommendations for some movie or
   * TV by TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param int $page
   *   Recommendations responses have maximum 20 teasers on each page. $page is
   *   the page in TMDb API that we want to get.
   *
   * @return array|null
   */
  public function getRecommendations(NodeBundle $bundle, int $tmdb_id, Language $lang, int $page): ?array {
    return (new Recommendations())
      ->setBundle($bundle)
      ->setTmdbId($tmdb_id)
      ->setLanguage($lang)
      ->setPage($page)
      ->response();
  }

  /**
   * Fetch from TMDb API or TmdbLocalStorage similar for some movie or TV by
   * TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param int $page
   *   Similar responses have maximum 20 teasers on each page. $page is the
   *   page in TMDb API that we want to get.
   *
   * @return array|null
   */
  public function getSimilar(NodeBundle $bundle, int $tmdb_id, Language $lang, int $page): ?array {
    return (new Similar())
      ->setBundle($bundle)
      ->setTmdbId($tmdb_id)
      ->setLanguage($lang)
      ->setPage($page)
      ->response();
  }

  /**
   * Search movie or TV show in TMDb API by IMDb ID.
   *
   * @param string $imdb_id
   *
   * @return array[NodeBundle, int]|null
   */
  public function getTmdbIdByImdbId(string $imdb_id): ?array {
    return (new FindByImdbId())
      ->setImdbId($imdb_id)
      ->response();
  }

  /**
   * Get IMDb ID by TMDb ID.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param bool $only_cached
   *   If TRUE the method will return only cached result if exists and doesn't
   *   send request to TMDb API.
   *
   * @return string|null
   */
  public function getImdbId(NodeBundle $bundle, int $tmdb_id, bool $only_cached = FALSE): ?string {
    if ($common = $this->getCommonFieldsByTmdbId($bundle, $tmdb_id, Language::en(), $only_cached)) {
      return $common['imdb_id'];
    }
    return NULL;
  }

  /**
   * Get common info for movie or TV.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE the method will return only cached result if exists and doesn't
   *   send request to TMDb API.
   *
   * @return array|null
   */
  public function getCommonFieldsByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang, bool $only_cached = FALSE): ?array {
    if ($response = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang, $only_cached)) {
      return $response['common'];
    }
    return NULL;
  }

  /**
   * Get all Movie or Tv info from TMDb API.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE the method will return only cached result if exists and doesn't
   *   send request to TMDb API.
   *
   * @return array|null
   */
  public function getFullInfoByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang, bool $only_cached = FALSE): ?array {
    $query = (new FullRequest())
      ->setBundle($bundle)
      ->setTmdbId($tmdb_id)
      ->setLanguage($lang);

    if (!$only_cached || $query->hasCache()) {
      return $query->response();
    }

    return NULL;
  }

  /**
   * Get season and nested episodes info form TMDb API.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param Language $lang
   * @param bool $only_cached
   *   If TRUE the method will return only cached result if exists and doesn't
   *   send request to TMDb API.
   *
   * @return array|null
   */
  public function getSeason(int $tv_tmdb_id, int $season_number, Language $lang, bool $only_cached = FALSE): ?array {
    $query = (new Seasons())
      ->setTvTmdbId($tv_tmdb_id)
      ->setSeasonNumber($season_number)
      ->setLanguage($lang);

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
   *   If TRUE the method will return only cached result if exists and doesn't
   *   send request to TMDb API.
   *
   * @return string|null
   */
  public function getEpisodeImdbId(int $tv_tmdb_id, int $season_number, int $episode_number, bool $only_cached = FALSE): ?string {
    $query = (new EpisodeImdbId())
      ->setTvTmdbId($tv_tmdb_id)
      ->setSeasonNumber($season_number)
      ->setEpisodeNumber($episode_number);

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
    return (new Genres())
      ->setBundle(NodeBundle::movie())
      ->setLanguage($lang)
      ->response();
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
    return (new Genres())
      ->setBundle(NodeBundle::tv())
      ->setLanguage($lang)
      ->response();
  }

}
