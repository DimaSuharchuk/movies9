<?php

namespace Drupal\imdb;

final class Constant {

  const IMDB_RATINGS_FILE_NAME = 'title.ratings.tsv';

  const TMDB_IMAGE_BASE_URL = '//image.tmdb.org/t/p/';

  const GENDER_WOMAN = 1;

  const GENDER_MAN = 2;

  const UNDEFINED_WOMAN_IMAGE = 'public://undefined-woman.png';

  const UNDEFINED_MAN_IMAGE = 'public://undefined-man.png';

  const UNDEFINED_PERSON_IMAGE = 'public://undefined-person.png';


  /**
   * Extra fields IDS:
   */
  /**
   * @see BelongsToCollection
   */
  const APPROVED = 'approved';

  /**
   * @see Approved
   */
  const BELONGS_TO_COLLECTION = 'belongs_to_collection';

  /**
   * @see Cast
   */
  const CAST = 'cast';

  /**
   * @see Crew
   */
  const CREW = 'crew';

  /**
   * @see ImdbRating
   */
  const IMDB_RATING = 'imdb_rating';

  /**
   * @see MovieReleaseDate
   */
  const MOVIE_RELEASE_DATE = 'movie_release_date';

  /**
   * @see OriginalTitle
   */
  const ORIGINAL_TITLE = 'original_title';

  /**
   * @see Overview
   */
  const OVERVIEW = 'overview';

  /**
   * @see ProductionCompanies
   */
  const PRODUCTION_COMPANIES = 'production_companies';

  /**
   * @see Recommendations
   */
  const RECOMMENDATIONS = 'recommendations';

  /**
   * @see Runtime
   */
  const RUNTIME = 'runtime';

  /**
   * @see Similar
   */
  const SIMILAR = 'similar';

  /**
   * @see Site
   */
  const SITE = 'site';

  /**
   * @see Title
   */
  const TITLE = 'title';

  /**
   * @see Videos
   */
  const VIDEOS = 'videos';


  /**
   * Queue Worker IDS:
   */

  /**
   * @see NodeCreatorQueueWorker
   */
  const NODE_SAVE_WORKER_ID = 'create_node_by_imdb_id_worker';


  private function __construct() {
  }

}
