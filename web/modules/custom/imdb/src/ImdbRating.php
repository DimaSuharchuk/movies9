<?php

namespace Drupal\imdb;

use Drupal\imdb\Manager\ImdbRatingDbManager;
use Drupal\imdb\Manager\ImdbRatingFileManager;
use function is_null;

class ImdbRating {

  private ?ImdbRatingDbManager $db_manager;

  private ?ImdbRatingFileManager $file_manager;

  /**
   * ImdbRating constructor.
   *
   * @param \Drupal\imdb\Manager\ImdbRatingDbManager $db_manager
   * @param \Drupal\imdb\Manager\ImdbRatingFileManager $file_manager
   */
  public function __construct(ImdbRatingDbManager $db_manager, ImdbRatingFileManager $file_manager) {
    $this->db_manager = $db_manager;
    $this->file_manager = $file_manager;
  }

  /**
   * Get rating array by IMDb ID.
   *
   * @param string $imdb_id
   *   IMDb ID.
   *
   * @return float
   */
  public function getRating(string $imdb_id): float {
    $rating = $this->db_manager->get($imdb_id);

    if (!is_null($rating)) {
      return $rating;
    }

    // Get rating from a file.
    $rating = $this->file_manager->get($imdb_id);
    // Save rating from a file into DB.
    $this->db_manager->set($imdb_id, $rating);

    return $rating;
  }

}
