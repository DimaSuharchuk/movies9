<?php

namespace Drupal\tmdb;

use Drupal\Core\Cache\Cache;
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
        // I also add a bit of random here for JS sorting after render.
        // Because the same teaser in a few places broke the sorting.
        [$bundle->name, $tmdb_id, rand()],
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
        [$bundle->name, $tmdb_id],
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
   * Generate lazy builder placeholder for "IMDb Rating" field of an episode.
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
   * Generate lazy builder placeholder for movie/tv teaser.
   *
   * @param \Drupal\mvs\enum\NodeBundle $bundle
   * @param \Drupal\mvs\enum\Language $lang
   * @param array $teaser
   *
   * @return array
   * @see \Drupal\tmdb\TmdbFieldLazyBuilder::renderTeaser()
   */
  public function generateTeaserPlaceholder(NodeBundle $bundle, Language $lang, array $teaser): array {
    return [
      '#lazy_builder' => [
        'tmdb.tmdb_field_lazy_builder:renderTeaser',
        [
          $bundle->name,
          $lang->name,
          $teaser['id'],
          $teaser['title'],
          $teaser['poster_path'] ?: NULL,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * Build a renderable array for field "IMDb Rating" of node or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function renderNodeImdbRatingField(string $bundle, int $tmdb_id): array {
    // Render field if cache exists.
    if (
      $imdb_id = $this->tmdb_adapter->getImdbId(NodeBundle::from($bundle), $tmdb_id, TRUE)
    ) {
      return [
        '#theme' => 'field_with_label',
        '#label' => 'imdb',
        '#content' => $this->imdb_rating->getRating($imdb_id),
      ];
    }
    // Else prepare HTML for update IMDb rating later via JS.
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
   * Build a renderable array for field "Original title" of node or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return array
   */
  public function renderNodeOriginalTitleField(string $bundle, int $tmdb_id): array {
    // Render field if cache exists.
    if (
      ($common = $this->tmdb_adapter->getCommonFieldsByTmdbId(NodeBundle::tryFrom($bundle), $tmdb_id, Language::en, TRUE))
      && !empty($common['title'])
    ) {
      return [
        '#theme' => 'tmdb_field',
        '#content' => $common['title'],
        '#css_class' => 'original_title',
      ];
    }
    // Else prepare HTML for update IMDb rating later via JS.
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
   * Build a renderable array for field "Original title" of season or custom
   * placeholder like lazy builder for further processing via JS.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   *
   * @return array
   */
  public function renderSeasonOriginalTitleField(int $tv_tmdb_id, int $season_number): array {
    // Render field if cache exists.
    if (
      ($season = $this->tmdb_adapter->getSeason($tv_tmdb_id, $season_number, Language::en, TRUE))
      && !empty($season['title'])
    ) {
      return [
        '#theme' => 'tmdb_field',
        '#content' => $season['title'],
        '#css_class' => 'original_title',
      ];
    }
    // Else prepare HTML for update IMDb rating later via JS.
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
    if (
      $imdb_id = $this->tmdb_adapter->getEpisodeImdbId($tv_tmdb_id, $season_number, $episode_number, TRUE)
    ) {
      return [
        '#theme' => 'field_with_label',
        '#label' => 'imdb',
        '#content' => $this->imdb_rating->getRating($imdb_id),
      ];
    }
    // Else prepare HTML for update IMDb rating later via JS.
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
   * Build a renderable array for field "Original title" of episode or custom
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
    if (
      $season = $this->tmdb_adapter->getSeason($tv_tmdb_id, $season_number, Language::en, TRUE)
    ) {
      // Search for an episode.
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
    // Else prepare HTML for update IMDb rating later via JS.
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
   * Build movie/tv teaser with cache.
   *
   * @param string $bundle_name
   * @param string $lang_name
   * @param int $tmdb_id
   * @param string $teaser_title
   * @param string|null $poster_path
   *
   * @return array
   */
  public function renderTeaser(string $bundle_name, string $lang_name, int $tmdb_id, string $teaser_title, ?string $poster_path): array {
    $bundle = NodeBundle::from($bundle_name);
    $lang = Language::from($lang_name);

    return [
      '#theme' => 'tmdb_teaser',
      '#bundle' => $bundle_name,
      '#tmdb_id' => $tmdb_id,
      '#poster' => $poster_path,
      '#imdb_rating' => $this->generateNodeImdbRatingPlaceholder($bundle, $tmdb_id),
      '#title' => $teaser_title,
      '#original_title' => $lang !== Language::en ? $this->generateNodeOriginalTitlePlaceholder($bundle, $tmdb_id) : NULL,
      '#cache' => [
        'keys' => ['tmdb', 'teaser', $tmdb_id, $bundle_name, $lang_name],
        'contexts' => ['languages'],
        'max-age' => Cache::PERMANENT,
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
      'renderTeaser',
    ];
  }

}
