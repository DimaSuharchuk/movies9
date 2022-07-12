<?php

namespace Drupal\imdb\Repository;

use Drupal\mvs\Repository\BaseRepository;

class ImdbRatingRepository extends BaseRepository {

  /**
   * {@inheritdoc}
   */
  public static function getTable(): string {
    return 'imdb_rating';
  }

  /**
   * {@inheritdoc}
   */
  public static function getLoggerName(): string {
    return 'imdb rating repository';
  }

  /**
   * Get ratings by IMDb IDs.
   *
   * @param array $imdb_ids
   *   IMDb IDs.
   *
   * @return array
   *   Ratings if ID exists in table.
   */
  public function get(array $imdb_ids): array {
    return $this->database->select($this::getTable(), 't')
      ->fields('t', ['imdb_id', 'rating'])
      ->condition('imdb_id', $imdb_ids, 'IN')
      ->execute()
      ->fetchAllKeyed();
  }

}
