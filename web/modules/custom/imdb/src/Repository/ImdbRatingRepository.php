<?php

namespace Drupal\imdb\Repository;

use Drupal\mvs\Repository\BaseRepository;

class ImdbRatingRepository extends BaseRepository {

  const string IMDB_RATING_TABLE = 'imdb_rating';

  /**
   * {@inheritdoc}
   */
  public static function getTable(): string {
    return self::IMDB_RATING_TABLE;
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
    return $this->database->select(self::IMDB_RATING_TABLE, 't')
      ->fields('t', ['imdb_id', 'rating'])
      ->condition('imdb_id', $imdb_ids, 'IN')
      ->execute()
      ->fetchAllKeyed();
  }

  /**
   * Save ratings to DB multiple.
   *
   * @param array $ratings
   *
   * @return void
   */
  public function setMultiple(array $ratings): void {
    $data = [];

    foreach ($ratings as $imdb_id => $rating) {
      $data[] = [
        'imdb_id' => $imdb_id,
        'rating' => $rating,
      ];
    }

    $this->upsert('imdb_id', ['imdb_id', 'rating'], $data);
  }

}
