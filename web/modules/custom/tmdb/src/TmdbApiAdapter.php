<?php

namespace Drupal\tmdb;

use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\CacheableTmdbRequest\FindByImdbId;
use Drupal\tmdb\CacheableTmdbRequest\FullRequest;
use Drupal\tmdb\CacheableTmdbRequest\Genres;
use Drupal\tmdb\CacheableTmdbRequest\MovieCollection;
use Drupal\tmdb\CacheableTmdbRequest\Recommendations;
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
   * Get IMDb IDs by TMDb IDs.
   *
   * @param NodeBundle $bundle
   * @param int[] $tmdb_ids
   *
   * @return string[]
   */
  public function getImdbIdsByTmdbIds(NodeBundle $bundle, array $tmdb_ids): array {
    $lang = Language::en(); // Define dummy lang for this task.

    $imdb_ids = [];
    foreach ($tmdb_ids as $tmdb_id) {
      if ($common = $this->getCommonFieldsByTmdbId($bundle, $tmdb_id, $lang)) {
        $imdb_ids[$tmdb_id] = $common['imdb_id'];
      }
    }

    return $imdb_ids;
  }

  /**
   * Get common info for movie or TV.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   *
   * @return array|null
   */
  public function getCommonFieldsByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang): ?array {
    if ($response = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang)) {
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
   *
   * @return array|null
   */
  public function getFullInfoByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang): ?array {
    return (new FullRequest())
      ->setBundle($bundle)
      ->setTmdbId($tmdb_id)
      ->setLanguage($lang)
      ->response();
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
