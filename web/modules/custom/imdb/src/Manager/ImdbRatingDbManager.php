<?php

namespace Drupal\imdb\Manager;

use Drupal\imdb\Repository\ImdbRatingRepository;
use function count;
use function reset;

class ImdbRatingDbManager {

  /**
   * @var \Drupal\imdb\Repository\ImdbRatingRepository
   */
  private ImdbRatingRepository $repository;

  public function __construct(ImdbRatingRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * Get IMDb rating by IMDb ID from DB.
   *
   * @param string $imdb_id
   *   IMDb ID.
   *
   * @return float|null
   *   IMDb rating.
   */
  public function get(string $imdb_id): ?float {
    if ($ratings = $this->getMultiple([$imdb_id])) {
      return reset($ratings);
    }

    return NULL;
  }

  /**
   * Get IMDb ratings by IMDb IDs from DB.
   *
   * @param array $imdb_ids
   *   IMDb IDs.
   *
   * @return array
   *   Ratings from IMDb.
   */
  public function getMultiple(array $imdb_ids): array {
    return $this->repository->get($imdb_ids);
  }

  /**
   * Save the rating to DB.
   *
   * @param string $imdb_id
   *   IMDb ID.
   * @param float $rating
   *   IMDb rating.
   *
   * @return void
   */
  public function set(string $imdb_id, float $rating): void {
    $this->repository->create([
      'imdb_id' => $imdb_id,
      'rating' => $rating,
    ]);
  }

  /**
   * Clear table with IMDb ratings.
   *
   * @return void
   */
  public function clear(): void {
    $this->repository->truncate();
  }

  /**
   * Get count of all records in the table.
   *
   * @return int
   *   Count of IMDb ratings in the table.
   */
  public function getRatingsCount(): int {
    return count($this->repository->findBy([]));
  }

}
