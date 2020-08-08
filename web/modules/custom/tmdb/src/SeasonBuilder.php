<?php

namespace Drupal\tmdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\imdb\DateHelper;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\node\Entity\Node;
use Drupal\tmdb\enum\TmdbImageFormat;

class SeasonBuilder {

  use StringTranslationTrait;
  use PersonAvatar;

  private TmdbApiAdapter $adapter;

  private DateHelper $date_helper;

  private TmdbFieldLazyBuilder $tmdb_lazy;

  public function __construct(TmdbApiAdapter $adapter, DateHelper $date_helper, TmdbFieldLazyBuilder $tmdb_lazy) {
    $this->adapter = $adapter;
    $this->date_helper = $date_helper;
    $this->tmdb_lazy = $tmdb_lazy;
  }


  /**
   * Build all season content + nested episodes list.
   *
   * @param Node $node
   *   Node "tv".
   * @param int $season_number
   *   Number of season.
   * @param Language $lang
   *
   * @return array
   */
  public function buildSeason(Node $node, int $season_number, Language $lang): array {
    $tmdb_id = $node->{'field_tmdb_id'}->value;

    $seasons_count = $this->adapter
      ->getCommonFieldsByTmdbId(NodeBundle::tv(), $tmdb_id, $lang)['number_of_seasons'];

    $season = $this->adapter->getSeason($tmdb_id, $season_number, $lang);

    return [
      '#theme' => 'season',
      '#tabs' => $this->buildTabs($node->id(), $seasons_count, $season_number),
      '#poster_path' => $season['poster_path'],
      '#original_title' => $lang !== Language::en() ? $this->tmdb_lazy->generateSeasonOriginalTitlePlaceholder($tmdb_id, $season_number) : NULL,
      '#title' => $season['title'],
      '#episodes_count' => [
        '#theme' => 'field_with_label',
        '#label' => $this->t('number of episodes', [], ['context' => 'Field label']),
        '#content' => count($season['episodes']),
      ],
      '#overview' => $season['overview'],
      '#episodes' => $this->buildEpisodes($tmdb_id, $season_number, $season['episodes'], $lang),
    ];
  }


  /**
   * Build navigation menu for seasons.
   *
   * @param int $node_id
   *   Node "tv" ID.
   * @param int $seasons_count
   *   Number of seasons in TV.
   * @param int $current_tab
   *   Number of current tab for add css class "active" for it.
   *
   * @return array
   */
  private function buildTabs(int $node_id, int $seasons_count, int $current_tab): array {
    $tabs = [];

    for ($i = 1; $i <= $seasons_count; $i++) {
      $tabs[] = $this->buildAjaxLink(
        $node_id,
        $i,
        $this->t('@i season', ['@i' => $i], ['context' => 'Seasons tabs']),
        $current_tab == $i
      );
    }

    return $tabs;
  }

  /**
   * Create ajax link for season tabs nav.
   *
   * @param int $node_id
   *   Node "tv" ID.
   * @param string $season
   *   Season number.
   * @param string $link_title
   *   Title of tab shown on page.
   * @param bool $is_active
   *   Is current tab an active tab.
   *
   * @return array
   *   Renderable array of ajax link.
   */
  private function buildAjaxLink(int $node_id, string $season, string $link_title, bool $is_active = FALSE): array {
    return [
      '#type' => 'link',
      '#title' => $this->t($link_title, [], ['context' => 'Extra tabs']),
      '#url' => Url::fromRoute('imdb.season_tabs_ajax_handler', [
        'node_id' => $node_id,
        'season' => $season,
      ]),
      '#attributes' => [
        'class' => ['use-ajax', $is_active ? 'active' : ''],
      ],
    ];
  }

  /**
   * Build episodes list.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param array $episodes_list
   *   List of episodes from TMDb API.
   * @param Language $lang
   *
   * @return array
   */
  private function buildEpisodes(int $tv_tmdb_id, int $season_number, array $episodes_list, Language $lang): array {
    $episodes = [];
    foreach ($episodes_list as $episode) {
      $episodes[] = $this->buildEpisode($tv_tmdb_id, $season_number, $episode, $lang);
    }

    return [
      '#theme' => 'episodes',
      '#content' => $episodes,
    ];
  }

  /**
   * Build themed episode.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param array $episode
   *   Fields data from TMDb API.
   * @param Language $lang
   *
   * @return array
   */
  private function buildEpisode(int $tv_tmdb_id, int $season_number, array $episode, Language $lang): array {
    // Prepare guest stars.
    $stars = [];
    foreach ($episode['guest_stars'] as $star) {
      $stars[] = [
        '#theme' => 'person',
        '#avatar' => $this->getThemedAvatar($star, TmdbImageFormat::w185()),
        '#name' => $star['name'],
        '#role' => $star['character'],
      ];
    }
    $stars_wrapper = NULL;
    if ($stars) {
      $stars_wrapper = [
        '#theme' => 'tmdb_items_list',
        '#items' => $stars,
        '#use_container' => FALSE,
      ];
    }

    // Build episode.
    return [
      '#theme' => 'episode',
      '#still_path' => $episode['still_path'],
      '#original_title' => $lang !== Language::en() ? $this->tmdb_lazy->generateEpisodeOriginalTitlePlaceholder(
        $tv_tmdb_id,
        $season_number,
        $episode['episode_number']
      ) : NULL,
      '#title' => $episode['name'],
      '#episode_number' => $this->t('episode @i', ['@i' => $episode['episode_number']]),
      '#air_date' => $this->date_helper->dateStringToReleaseDateFormat($episode['air_date']),
      '#imdb_rating' => $this->tmdb_lazy->generateEpisodeImdbRatingPlaceholder(
        $tv_tmdb_id,
        $season_number,
        $episode['episode_number']
      ),
      '#overview' => $episode['overview'],
      '#guest_stars' => $stars_wrapper,
    ];
  }

}
