<?php

namespace Drupal\imdb\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\imdb\ImdbRating;
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
    if ($imdb_id = $this->adapter->getImdbId(NodeBundle::memberByValue($bundle), $tmdb_id)) {
      return new AjaxResponse($this->imdb_rating->getRating($imdb_id));
    }

    return new AjaxResponse();
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
    if ($imdb_id = $this->adapter->getEpisodeImdbId($tv_tmdb_id, $season_number, $episode_number)) {
      return new AjaxResponse($this->imdb_rating->getRating($imdb_id));
    }

    return new AjaxResponse();
  }

}
