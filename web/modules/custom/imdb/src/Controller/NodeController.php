<?php

namespace Drupal\imdb\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\imdb\ImdbRating;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbApiAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeController implements ContainerInjectionInterface {

  private ?TmdbApiAdapter $adapter;

  private ?ImdbRating $imdb_rating;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): NodeController {
    $instance = new static();

    $instance->adapter = $container->get('tmdb.adapter');
    $instance->imdb_rating = $container->get('imdb.rating');

    return $instance;
  }

  /**
   * Returns IMDb rating of the node "movie" or "tv".
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return AjaxResponse
   */
  public function nodeImdbRating(string $bundle, int $tmdb_id): AjaxResponse {
    $node_bundle = NodeBundle::tryFrom($bundle);

    return ($node_bundle && ($imdb_id = $this->adapter->getImdbId($node_bundle, $tmdb_id)))
      ? new AjaxResponse($this->imdb_rating->getRating($imdb_id))
      : new AjaxResponse();
  }

  /**
   * Returns average IMDb rating of the season.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function seasonImdbRating(int $tv_tmdb_id, int $season_number): AjaxResponse {
    $season = $this->adapter->getSeason($tv_tmdb_id, $season_number, Language::en);
    $imdb_ids = [];

    foreach ($season['episodes'] as $episode) {
      $episode_imdb_id = $this->adapter->getEpisodeImdbId($tv_tmdb_id, $season_number, $episode['episode_number']);

      if (is_string($episode_imdb_id) && is_imdb_id($episode_imdb_id)) {
        $imdb_ids[] = $episode_imdb_id;
      }
    }

    $ratings = $this->imdb_rating->getRatingMultiple($imdb_ids);
    $count = count($ratings);

    return new AjaxResponse($count ? round(array_sum($ratings) / $count, 2) : 0);
  }

  /**
   * Returns IMDb rating of the episode.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return AjaxResponse
   */
  public function episodeImdbRating(int $tv_tmdb_id, int $season_number, int $episode_number): AjaxResponse {
    return ($imdb_id = $this->adapter->getEpisodeImdbId($tv_tmdb_id, $season_number, $episode_number))
      ? new AjaxResponse($this->imdb_rating->getRating($imdb_id))
      : new AjaxResponse();
  }

}
