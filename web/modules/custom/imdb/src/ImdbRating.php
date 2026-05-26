<?php

namespace Drupal\imdb;

use Drupal\imdb\Manager\ImdbRatingDbManager;
use Drupal\imdb\Manager\ImdbRatingFileManager;

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
    if (!is_imdb_id($imdb_id)) {
      return 0;
    }

    return $this->getRatingMultiple([$imdb_id])[$imdb_id];
  }

  /**
   * Get array of ratings by IMDb IDs.
   *
   * @param string[] $imdb_ids
   *   IMDb IDs.
   *
   * @return array<string, float>
   *   IMDb ratings keyed by IMDb IDs.
   */
  public function getRatingMultiple(array $imdb_ids): array {
    if (!$imdb_ids) {
      return [];
    }

    $imdb_ids = array_combine($imdb_ids, $imdb_ids);
    $ratings = $this->db_manager->getMultiple($imdb_ids);

    if ($imdb_ids_to_load = array_diff_key($imdb_ids, $ratings)) {
      // Get rating from a file.
      // The file manager's getMultiple() method ensures that all requested IDs
      // are returned.
      $ratings_from_file = $this->file_manager->getMultiple($imdb_ids_to_load);
      // Save ratings from a file into DB.
      $this->db_manager->setMultiple($ratings_from_file);

      $ratings += $ratings_from_file;
    }

    return array_map('floatval', $ratings);
  }

}
