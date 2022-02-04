<?php

namespace Drupal\tmdb;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\imdb\ImdbRating;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;

class TmdbFieldLazyBuilder implements TrustedCallbackInterface {

  private TmdbApiAdapter $tmdb_adapter;

  private ImdbRating $imdb_rating;

  public function __construct(TmdbApiAdapter $adapter, ImdbRating $rating) {
    $this->tmdb_adapter = $adapter;
    $this->imdb_rating = $rating;
  }

  /**
   * Generate lazy builder placeholder for "IMDb Rating" field of node.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array
   * @see TmdbFieldLazyBuilder::renderNodeImdbRatingField()
   */
  public function generateNodeImdbRatingPlaceholder(NodeBundle $bundle, int $tmdb_id): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderNodeImdbRatingField',
        [$bundle->key(), $tmdb_id],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Generate lazy builder placeholder for "Original title" field of node.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   *
   * @return array
   * @see TmdbFieldLazyBuilder::renderNodeOriginalTitleField()
   */
  public function generateNodeOriginalTitlePlaceholder(NodeBundle $bundle, int $tmdb_id): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderNodeOriginalTitleField',
        [$bundle->key(), $tmdb_id],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Generate lazy builder placeholder for "Original title" field of season.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   *
   * @return array
   * @see TmdbFieldLazyBuilder::renderSeasonOriginalTitleField()
   */
  public function generateSeasonOriginalTitlePlaceholder(int $tv_tmdb_id, int $season_number): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderSeasonOriginalTitleField',
        [$tv_tmdb_id, $season_number],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Generate lazy builder placeholder for "IMDb Rating" field of episode.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return array
   * @see TmdbFieldLazyBuilder::renderEpisodeImdbRatingField()
   */
  public function generateEpisodeImdbRatingPlaceholder(int $tv_tmdb_id, int $season_number, int $episode_number): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderEpisodeImdbRatingField',
        [$tv_tmdb_id, $season_number, $episode_number],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Generate lazy builder placeholder for "Original title" field of episode.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return array
   * @see TmdbFieldLazyBuilder::renderEpisodeOriginalTitleField()
   */
  public function generateEpisodeOriginalTitlePlaceholder(int $tv_tmdb_id, int $season_number, int $episode_number): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderEpisodeOriginalTitleField',
        [$tv_tmdb_id, $season_number, $episode_number],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Build renderable array for field "IMDb Rating" of node or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function renderNodeImdbRatingField(string $bundle, int $tmdb_id): array {
    // Render field if cache exists.
    if ($imdb_id = $this->tmdb_adapter
      ->getImdbId(NodeBundle::memberByValue($bundle), $tmdb_id, TRUE)
    ) {
      return [
        '#theme' => 'field_with_label',
        '#label' => 'imdb',
        '#content' => $this->imdb_rating->getRatingValue($imdb_id),
      ];
    }
    // Else prepare html for update IMDb rating later via JS.
    return [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['js--node-imdb-rating-placeholder'],
            'data-bundle' => $bundle,
            'data-id' => $tmdb_id,
          ],
        ],
      ],
      '#attached' => [
        'library' => ['tmdb/tmdb_field_post_update'],
      ],
    ];
  }

  /**
   * Build renderable array for field "Original title" of node or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function renderNodeOriginalTitleField(string $bundle, int $tmdb_id): array {
    // Render field if cache exists.
    if ($common = $this->tmdb_adapter
      ->getCommonFieldsByTmdbId(NodeBundle::memberByValue($bundle), $tmdb_id, Language::en(), TRUE)
    ) {
      return [
        '#theme' => 'tmdb_field',
        '#content' => $common['title'],
        '#css_class' => 'original_title',
      ];
    }
    // Else prepare html for update IMDb rating later via JS.
    return [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['js--node-original-title-placeholder'],
            'data-bundle' => $bundle,
            'data-id' => $tmdb_id,
          ],
        ],
      ],
      '#attached' => [
        'library' => ['tmdb/tmdb_field_post_update'],
      ],
    ];
  }

  /**
   * Build renderable array for field "Original title" of season or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   *
   * @return array
   */
  public function renderSeasonOriginalTitleField(int $tv_tmdb_id, int $season_number): array {
    // Render field if cache exists.
    if ($season = $this->tmdb_adapter
      ->getSeason($tv_tmdb_id, $season_number, Language::en(), TRUE)
    ) {
      return [
        '#theme' => 'tmdb_field',
        '#content' => $season['title'],
        '#css_class' => 'original_title',
      ];
    }
    // Else prepare html for update IMDb rating later via JS.
    return [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['js--season-original-title-placeholder'],
            'data-tmdb_id' => $tv_tmdb_id,
            'data-season' => $season_number,
          ],
        ],
      ],
      '#attached' => [
        'library' => ['tmdb/tmdb_field_post_update'],
      ],
    ];
  }

  /**
   * Build renderable array for field "IMDb Rating" of episode or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return array
   */
  public function renderEpisodeImdbRatingField(int $tv_tmdb_id, int $season_number, int $episode_number): array {
    // Print only cached IMDb ratings.
    if ($imdb_id = $this->tmdb_adapter
      ->getEpisodeImdbId($tv_tmdb_id, $season_number, $episode_number, TRUE)
    ) {
      return [
        '#theme' => 'field_with_label',
        '#label' => 'imdb',
        '#content' => $this->imdb_rating->getRatingValue($imdb_id),
      ];
    }
    // Else prepare html for update IMDb rating later via JS.
    return [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['js--episode-imdb-rating-placeholder'],
            'data-tmdb_id' => $tv_tmdb_id,
            'data-season' => $season_number,
            'data-episode' => $episode_number,
          ],
        ],
      ],
      '#attached' => [
        'library' => ['tmdb/tmdb_field_post_update'],
      ],
    ];
  }

  /**
   * Build renderable array for field "Original title" of episode or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return array|null
   */
  public function renderEpisodeOriginalTitleField(int $tv_tmdb_id, int $season_number, int $episode_number): ?array {
    // Render field if cache exists.
    if ($season = $this->tmdb_adapter
      ->getSeason($tv_tmdb_id, $season_number, Language::en(), TRUE)
    ) {
      // Search for episode.
      foreach ($season['episodes'] as $episode) {
        if ($episode['episode_number'] == $episode_number) {
          return [
            '#theme' => 'tmdb_field',
            '#content' => $episode['name'],
            '#css_class' => 'original_title',
          ];
        }
      }
      return NULL;
    }
    // Else prepare html for update IMDb rating later via JS.
    return [
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['js--episode-original-title-placeholder'],
            'data-tmdb_id' => $tv_tmdb_id,
            'data-season' => $season_number,
            'data-episode' => $episode_number,
          ],
        ],
      ],
      '#attached' => [
        'library' => ['tmdb/tmdb_field_post_update'],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return [
      'renderNodeImdbRatingField',
      'renderNodeOriginalTitleField',
      'renderSeasonOriginalTitleField',
      'renderEpisodeImdbRatingField',
      'renderEpisodeOriginalTitleField',
    ];
  }

}
