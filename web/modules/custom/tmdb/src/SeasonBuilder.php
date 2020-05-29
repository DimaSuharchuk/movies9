<?php

namespace Drupal\tmdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\imdb\DateHelper;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\ImdbRating;
use Drupal\node\Entity\Node;
use Drupal\tmdb\enum\TmdbImageFormat;

class SeasonBuilder {

  use StringTranslationTrait;
  use PersonAvatar;

  private TmdbApiAdapter $adapter;

  private DateHelper $date_helper;

  private ImdbRating $imdb_rating;

  public function __construct(TmdbApiAdapter $adapter, DateHelper $date_helper, ImdbRating $rating) {
    $this->adapter = $adapter;
    $this->date_helper = $date_helper;
    $this->imdb_rating = $rating;
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
      '#tabs' => $this->buildTabs($node->id(), $seasons_count),
      '#poster_path' => $season['poster_path'],
      '#original_title' => 'test',
      '#title' => $season['title'],
      '#episodes_count' => [
        '#theme' => 'field_with_label',
        '#label' => $this->t('number of episodes', [], ['context' => 'Field label']),
        '#content' => count($season['episodes']),
      ],
      '#overview' => $season['overview'],
      '#episodes' => $this->buildEpisodes($tmdb_id, $season_number, $season['episodes']),
    ];
  }


  /**
   * Build navigation menu for seasons.
   *
   * @param int $node_id
   *   Node "tv" ID.
   * @param int $seasons_count
   *   Number of seasons in TV.
   *
   * @return array
   */
  private function buildTabs(int $node_id, int $seasons_count): array {
    $tabs = [];

    for ($i = 1; $i <= $seasons_count; $i++) {
      $tabs[] = $this->buildAjaxLink(
        $node_id,
        $i,
        $this->t('@i season', ['@i' => $i], ['context' => 'Seasons tabs'])
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
   *
   * @return array
   *   Renderable array of ajax link.
   */
  private function buildAjaxLink(int $node_id, string $season, string $link_title): array {
    return [
      '#type' => 'link',
      '#title' => $this->t($link_title, [], ['context' => 'Extra tabs']),
      '#url' => Url::fromRoute('imdb.season_tabs_ajax_handler', [
        'node_id' => $node_id,
        'season' => $season,
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
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
   *
   * @return array
   */
  private function buildEpisodes(int $tv_tmdb_id, int $season_number, array $episodes_list): array {
    $episodes = [];
    foreach ($episodes_list as $episode) {
      $episodes[] = $this->buildEpisode($tv_tmdb_id, $season_number, $episode);
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
   *
   * @return array
   */
  private function buildEpisode(int $tv_tmdb_id, int $season_number, array $episode): array {
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

    // Get episode IMDb ID and get IMDb rating.
    $rating = NULL;
    if ($imdb_id = $this->adapter->getEpisodeImdbId(
      $tv_tmdb_id,
      $season_number,
      $episode['episode_number']
    )) {
      $rating = $this->imdb_rating->getRatingValue($imdb_id['imdb_id']);
    }

    // Build episode.
    return [
      '#theme' => 'episode',
      '#still_path' => $episode['still_path'],
      '#original_title' => '~~~placeholder~~~', // @todo
      '#title' => $episode['name'],
      '#episode_number' => $this->t('@i episode', ['@i' => $episode['episode_number']]),
      '#air_date' => $this->date_helper->dateStringToReleaseDateFormat($episode['air_date']),
      '#imdb_rating' => $rating,
      '#overview' => $episode['overview'],
      '#guest_stars' => $stars,
    ];
  }

}
