<?php

namespace Drupal\tmdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbLocalStorageType;

class TmdbTeaser {

  use StringTranslationTrait;

  private TmdbApiAdapter $adapter;

  private TmdbFieldLazyBuilder $tmdb_lazy;

  public function __construct(TmdbApiAdapter $adapter, TmdbFieldLazyBuilder $tmdb_lazy) {
    $this->adapter = $adapter;
    $this->tmdb_lazy = $tmdb_lazy;
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
   * @param Language $lang
   * @param int $page
   * @param bool $more_button
   *
   * @return array
   */
  public function buildAttachableTmdbTeasersWithWrapper(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, Language $lang, int $page, $more_button = FALSE): array {
    return [
      '#theme' => 'tmdb_attachable_teasers_wrapper',
      '#tmdb_attachable_teasers' => $this->buildAttachableTmdbTeasers(
        $storage_type,
        $node_id,
        $teasers,
        $bundle,
        $lang,
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
   * @param Language $lang
   * @param int $page
   * @param bool $more_button
   *
   * @return array
   */
  public function buildAttachableTmdbTeasers(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, Language $lang, int $page, $more_button = FALSE): array {
    $render = [
      '#theme' => 'tmdb_attachable_teasers',
      '#items' => $this->buildTmdbTeasers($teasers, $bundle, $lang),
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
   * @param Language $lang
   *
   * @return array
   */
  public function buildTmdbTeasers(array $teasers, NodeBundle $bundle, Language $lang): array {
    $render = [];
    foreach ($teasers as $teaser) {
      $render[] = [
        '#theme' => 'tmdb_teaser',
        '#bundle' => $bundle->value(),
        '#tmdb_id' => $teaser['id'],
        '#poster' => $teaser['poster_path'] ?: NULL,
        '#imdb_rating' => $this->tmdb_lazy->generateNodeImdbRatingPlaceholder($bundle, $teaser['id']),
        '#title' => $teaser['title'],
        '#original_title' => $lang !== Language::en() ? $this->tmdb_lazy->generateNodeOriginalTitlePlaceholder($bundle, $teaser['id']) : NULL,
      ];
    }

    return $render;
  }

}
