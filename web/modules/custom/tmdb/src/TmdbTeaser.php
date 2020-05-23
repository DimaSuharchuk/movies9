<?php

namespace Drupal\tmdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\ImdbRating;
use Drupal\tmdb\enum\TmdbLocalStorageType;

class TmdbTeaser {

  use StringTranslationTrait;

  private TmdbAdapter $adapter;

  private ImdbRating $imdb_rating;

  public function __construct(TmdbAdapter $adapter, ImdbRating $rating) {
    $this->adapter = $adapter;
    $this->imdb_rating = $rating;
  }


  /**
   * Wrap TMDb teasers for right AJAX calls.
   * Use the high-level method for fields: "Recommendations" and "Similar".
   *
   * @param TmdbLocalStorageType $storage_type
   * @param int $node_id
   * @param array $teasers
   *   Teasers are response from TMDb API.
   * @param NodeBundle $bundle
   * @param int $page
   * @param bool $more_button
   *
   * @return array
   */
  public function buildAttachableTmdbTeasersWithWrapper(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, int $page, $more_button = FALSE): array {
    return [
      '#theme' => 'tmdb_attachable_teasers_wrapper',
      '#tmdb_attachable_teasers' => $this->buildAttachableTmdbTeasers(
        $storage_type,
        $node_id,
        $teasers,
        $bundle,
        $page,
        $more_button
      ),
    ];
  }

  /**
   * Build TMDb teasers with ajax button for loading next "TMDb page".
   *
   * @param TmdbLocalStorageType $storage_type
   * @param int $node_id
   * @param array $teasers
   * @param NodeBundle $bundle
   * @param int $page
   * @param bool $more_button
   *
   * @return array
   */
  public function buildAttachableTmdbTeasers(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, int $page, $more_button = FALSE): array {
    $render = [
      '#theme' => 'tmdb_attachable_teasers',
      '#items' => $this->buildTmdbTeasers($teasers, $bundle),
      '#page' => $page,
    ];

    if ($more_button) {
      $route = $storage_type === TmdbLocalStorageType::recommendations() ? 'imdb.recommendations' : 'imdb.similar';
      $render['#more_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Load more'),
        '#url' => Url::fromRoute($route, [
          'nid' => $node_id,
          'page' => ++$page,
        ]),
        '#attributes' => [
          'class' => ['use-ajax'],
        ],
      ];
    }

    return $render;
  }

  /**
   * Build renderable array from TMDb teasers data.
   *
   * @param array $teasers
   * @param NodeBundle $bundle
   *
   * @return array
   */
  public function buildTmdbTeasers(array $teasers, NodeBundle $bundle): array {
    // Get IMDb IDs for collection items.
    $tmdb_ids = array_column($teasers, 'id');
    // @todo Slowest thing is here.
    $imdb_ids = $this->adapter->getImdbIdsByTmdbIds($tmdb_ids, $bundle);

    $render = [];
    foreach ($teasers as $teaser) {
      $render[] = [
        '#theme' => 'tmdb_teaser',
        '#bundle' => $bundle->value(),
        '#tmdb_id' => $teaser['id'],
        '#poster' => $teaser['poster_path'] ?: NULL,
        '#imdb_rating' => $this->imdb_rating->getRatingValue($imdb_ids[$teaser['id']]),
        '#title' => $teaser['title'] ?? $teaser['name'],
      ];
    }

    return $render;
  }

}
