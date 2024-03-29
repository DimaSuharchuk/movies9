<?php

namespace Drupal\mvs;

final class Constant {

  const TMDB_IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/';

  const GENDER_WOMAN = 1;

  const GENDER_MAN = 2;

  const UNDEFINED_WOMAN_IMAGE = '/themes/custom/movies/assets/undefined-woman.svg';

  const UNDEFINED_MAN_IMAGE = '/themes/custom/movies/assets/undefined-man.svg';

  const UNDEFINED_PERSON_IMAGE = '/themes/custom/movies/assets/undefined-person.svg';

  const EXCLUDED_GENRES_TMDB_IDS = [
    10763, // News
    10764, // Reality
    10767, // Talk
  ];

  /**
   * Extra fields IDS:
   */
  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Approved
   */
  const APPROVED = 'approved';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\BelongsToCollection
   */
  const BELONGS_TO_COLLECTION = 'belongs_to_collection';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Cast
   */
  const CAST = 'cast';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Crew
   */
  const CREW = 'crew';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\EpisodeRuntime
   */
  const EPISODE_RUNTIME = 'episode_runtime';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\ImdbRating
   */
  const IMDB_RATING = 'imdb_rating';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\MovieReleaseDate
   */
  const MOVIE_RELEASE_DATE = 'movie_release_date';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Networks
   */
  const networks = 'networks';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\NonameClub
   */
  const NONAME_CLUB = 'noname_club';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\NumberOfEpisodes
   */
  const number_of_episodes = 'number_of_episodes';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\NumberOfSeasons
   */
  const number_of_seasons = 'number_of_seasons';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\OriginalTitle
   */
  const ORIGINAL_TITLE = 'original_title';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Overview
   */
  const OVERVIEW = 'overview';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\ProductionCompanies
   */
  const PRODUCTION_COMPANIES = 'production_companies';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Recommendations
   */
  const RECOMMENDATIONS = 'recommendations';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Runtime
   */
  const RUNTIME = 'runtime';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Seasons
   */
  const SEASONS = 'seasons';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Similar
   */
  const SIMILAR = 'similar';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Site
   */
  const SITE = 'site';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Tabs
   */
  const TABS = 'tabs';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Title
   */
  const TITLE = 'title';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\TvReleaseYears
   */
  const TV_RELEASE_YEARS = 'tv_release_years';

  /**
   * @see \Drupal\tmdb\Plugin\ExtraField\Display\Videos
   */
  const VIDEOS = 'videos';

  final private function __construct() {
  }

}
