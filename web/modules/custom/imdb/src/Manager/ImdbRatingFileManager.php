<?php

namespace Drupal\imdb\Manager;

use Exception;
use function array_flip;
use function explode;
use function implode;
use function reset;
use function shell_exec;

class ImdbRatingFileManager {

  /**
   * Same as Settings::get('file_private_path') + file name.
   *
   * @var string
   */
  private string $filepath = '../private/title.ratings.tsv';

  /**
   * Get IMDb rating by IMDb ID.
   *
   * @param string $imdb_id
   *   IMDb ID.
   *
   * @return float
   *   IMDb rating.
   */
  public function get(string $imdb_id): float {
    $ratings = $this->getMultiple([$imdb_id]);

    return reset($ratings);
  }

  /**
   * Get IMDb ratings by IMDb IDs.
   *
   * @param array $imdb_ids
   *   IMDb IDs.
   *
   * @return array
   *   IMDb ratings.
   */
  public function getMultiple(array $imdb_ids): array {
    $ratings = [];
    $ids = implode('\|', $imdb_ids);

    if ($grep = shell_exec("grep \"$ids\" $this->filepath")) {
      $lines = explode("\n", $grep);

      foreach ($lines as $line) {
        if ($line) {
          [$imdb_id, $rating] = explode("\t", $line);
          $ratings[$imdb_id] = $rating;
        }
      }
    }

    $imdb_ids = array_flip($imdb_ids);

    foreach ($imdb_ids as $imdb_id => $_) {
      $imdb_ids[$imdb_id] = $ratings[$imdb_id] ?? 0;
    }

    return $imdb_ids;
  }

  /**
   * Rewrite file with fresh ratings from IMDb site.
   *
   * @return void
   * @throws \Exception
   */
  public function refresh() {
    // Update "IMDB ratings" file in private directory.
    if ($zipped = @file_get_contents('https://datasets.imdbws.com/title.ratings.tsv.gz')) {
      if ($unzipped = @gzdecode($zipped)) {
        file_put_contents($this->filepath, $unzipped);
      }
      else {
        throw new Exception('File exists, but not a gzip.');
      }
    }
    else {
      throw new Exception('IMDB ratings have not been updated.');
    }
  }

}
