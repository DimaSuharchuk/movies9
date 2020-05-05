<?php

namespace Drupal\imdb;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Site\Settings;

class ImdbRating {

  const RATINGS_FILE_NAME = 'title.ratings.tsv';

  private Settings $settings;

  private IMDbHelper $imdb_helper;

  private SerializationInterface $json;


  /**
   * ImdbRating constructor.
   *
   * @param Settings $settings
   * @param SerializationInterface $json
   * @param IMDbHelper $helper
   */
  public function __construct(Settings $settings, SerializationInterface $json, IMDbHelper $helper) {
    $this->settings = $settings;
    $this->json = $json;
    $this->imdb_helper = $helper;
  }


  /**
   * Get average rating by IMDb ID.
   *
   * @param string $imdb_id
   *
   * @return float
   */
  public function getRatingValue(string $imdb_id): float {
    if ($rating = $this->getRating($imdb_id)) {
      return $rating['rating'];
    }
    return 0;
  }

  /**
   * Get count of votes by IMDb ID.
   *
   * @param string $imdb_id
   *
   * @return int
   */
  public function getNumVotes(string $imdb_id): int {
    if ($rating = $this->getRating($imdb_id)) {
      return $rating['num_votes'];
    }
    return 0;
  }

  /**
   * Get rating array by IMDb ID.
   *
   * @param string $imdb_id
   *   IMDb ID.
   *
   * @return array|null
   */
  public function getRating(string $imdb_id): ?array {
    // Validate IMDb ID.
    if (!$this->imdb_helper->isImdbId($imdb_id)) {
      return NULL;
    }

    // Try to get rating from fastest method to slowest.
    if ($rating = $this->getRatingFromFile($imdb_id)) {
      return $rating;
    }
    if ($rating = $this->getOmdbRating($imdb_id)) {
      return $rating;
    }
    if ($rating = $this->getImdbRating($imdb_id)) {
      return $rating;
    }

    return NULL;
  }


  /**
   * Fastest method. Read from file.
   *
   * @param string $imdb_id
   *
   * @return array
   */
  private function getRatingFromFile(string $imdb_id): ?array {
    $private_dir = $this->settings::get('file_private_path');
    $f = self::RATINGS_FILE_NAME;
    // Read file.
    $content = file_get_contents("{$private_dir}/{$f}");
    // Try to find line by IMDb ID.
    if ($pos_id = strpos($content, $imdb_id)) {
      $content_from_pos = substr($content, $pos_id);
      $pos_break = strpos($content_from_pos, "\n");
      $line = substr($content_from_pos, 0, $pos_break);

      [, $rating, $num_votes] = explode("\t", $line);

      return $this->buildResultArray($rating, $num_votes);
    }

    return NULL;
  }

  /**
   * Fast method.
   * Returns slightly outdated information about IMDb rating and votes count by
   * IMDb ID.
   *
   * @param string $imdb_id
   *
   * @return array|null
   */
  private function getOmdbRating(string $imdb_id): ?array {
    $omdb_api_key = $this->settings::get('omdb_api_key');
    if ($omdb_response = file_get_contents("http://www.omdbapi.com/?apikey={$omdb_api_key}&i={$imdb_id}")) {
      if ($array = $this->json::decode($omdb_response)) {
        if (isset($array['imdbRating'])) {
          return $this->buildResultArray(
            $array['imdbRating'],
            filter_var($array['imdbVotes'], FILTER_SANITIZE_NUMBER_INT)
          );
        }
      }
    }

    return NULL;
  }

  /**
   * Slow method.
   * Parse IMDb page and return exact actual data.
   *
   * @param string $imdb_id
   *
   * @return array|null
   */
  private function getImdbRating(string $imdb_id): ?array {
    if ($imdb_page = @file_get_contents("https://www.imdb.com/title/{$imdb_id}")) {
      preg_match('/"aggregateRating": ({\n?(.*\n\s*)+?})/', $imdb_page, $matches);
      if ($aggregate_rating = @$this->json::decode($matches[1])) {
        return $this->buildResultArray($aggregate_rating['ratingValue'], $aggregate_rating['ratingCount']);
      }
    }

    return NULL;
  }

  /**
   * Interface of response from this service.
   *
   * @param $rating_value
   *   Average rating from IMDb site.
   * @param $number_of_votes
   *   The number of people who voted for a given film, TV or episode.
   *
   * @return array
   */
  private function buildResultArray($rating_value, $number_of_votes): array {
    return [
      'rating' => (float) $rating_value,
      'num_votes' => (int) $number_of_votes,
    ];
  }

}
