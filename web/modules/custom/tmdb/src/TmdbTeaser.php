<?php

namespace Drupal\tmdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbLocalStorageType;

class TmdbTeaser {

  use StringTranslationTrait;

  public function __construct(
    private readonly TmdbApiAdapter $adapter,
    private readonly TmdbFieldLazyBuilder $tmdb_lazy,
  ) {
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
  public function buildAttachableTmdbTeasersWithWrapper(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, Language $lang, int $page, bool $more_button = FALSE): array {
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
  public function buildAttachableTmdbTeasers(TmdbLocalStorageType $storage_type, int $node_id, array $teasers, NodeBundle $bundle, Language $lang, int $page, bool $more_button = FALSE): array {
    $render = [
      '#theme' => 'tmdb_attachable_teasers',
      '#items' => $this->buildTmdbTeasers($teasers, $bundle, $lang, TRUE),
      '#page' => $page,
    ];

    if ($more_button) {
      $route = $storage_type === TmdbLocalStorageType::recommendations ? 'mvs.recommendations' : 'mvs.similar';
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
   * Build a renderable array from TMDb teasers data.
   *
   * @param array $teasers
   * @param NodeBundle $bundle
   * @param Language $lang
   * @param bool $sort_by_rating
   *
   * @return array
   */
  public function buildTmdbTeasers(array $teasers, NodeBundle $bundle, Language $lang, bool $sort_by_rating): array {
    $render = [];

    foreach ($teasers as $key => $teaser) {
      $render[$key] = $this->tmdb_lazy->generateTeaserPlaceholder($bundle, $lang, $teaser);

      if ($sort_by_rating) {
        $render[$key]['#rating'] = $teaser['vote_average'];
      }
    }

    if ($sort_by_rating) {
      usort($render, fn($a, $b) => $b['#rating'] <=> $a['#rating']);

      $render = array_map(fn($item) => array_diff_key($item, ['#rating' => TRUE]), $render);
    }

    return $render;
  }

}
