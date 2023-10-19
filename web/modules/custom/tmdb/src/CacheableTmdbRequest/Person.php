<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Person extends CacheableTmdbRequest {

  private int $tmdb_id;

  private Language $lang;

  public function setTmdbId(int $tmdb_id): self {
    $this->tmdb_id = $tmdb_id;
    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;
    return $this;
  }

  /**
   * @inheritDoc
   */
  protected function request(): array {
    $params = [
      'language' => $this->lang->key(),
      'append_to_response' => 'movie_credits,tv_credits,images',
    ];

    return $this->connect->getPeopleApi()->getPerson($this->tmdb_id, $params);
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'person',
      "{$this->tmdb_id}_{$this->lang->key()}",
    );
  }

  /**
   * @inheritDoc
   */
  protected function massageBeforeSave(array $data): array {
    // Filter nested teasers.
    $allowed_teaser_fields = [
      'id',
      'media_type',
      'title',
      'original_title',
      'name',
      'original_name',
      'poster_path',
    ];

    $filtered = [
      'id' => $data['id'],
      'name' => $data['name'],
      'profile_path' => $data['profile_path'],
      'biography' => $data['biography'],
      'also_known_as' => $data['also_known_as'],
      'birthday' => $data['birthday'],
      'deathday' => $data['deathday'],
      'gender' => $data['gender'],
      'known_for_department' => $data['known_for_department'],
      'place_of_birth' => $data['place_of_birth'],
      'movie_credits' => [
        'cast' => $this->massageTeasers($this->allowedFieldsFilter($data['movie_credits']['cast'], $allowed_teaser_fields)),
        'crew' => $this->massageTeasers($this->allowedFieldsFilter($data['movie_credits']['crew'], $allowed_teaser_fields)),
      ],
      'tv_credits' => [
        'cast' => $this->massageTeasers($this->allowedFieldsFilter($data['tv_credits']['cast'], $allowed_teaser_fields)),
        'crew' => $this->massageTeasers($this->allowedFieldsFilter($data['tv_credits']['crew'], $allowed_teaser_fields)),
      ],
    ];

    if ($images = $this->allowedFieldsFilter($data['images']['profiles'], ['file_path'])) {
      $filtered['images'] = array_column($images, 'file_path');
    }

    $filtered['combined_credits'] = $this->mergeCredits($filtered['movie_credits'], $filtered['tv_credits']);
    unset($filtered['movie_credits'], $filtered['tv_credits']);

    return $filtered;
  }

  /**
   * A wrapper for the "$this->massageTeaserFields()" method, which processes 1
   * teaser, but here we process an array of teasers.
   *
   * @param array $a
   *   Array of Movies/TVs teasers.
   *
   * @return array
   *   Processed array.
   *
   * @see Person::massageTeaserFields()
   */
  private function massageTeasers(array $a): array {
    foreach ($a as &$teaser) {
      $this->massageTeaserFields($teaser);
    }
    return $a;
  }

  /**
   * Let's set the TV keys to the same form as Movie uses. Also rename field
   * "media_type" to "bundle".
   *
   * @param array $teaser
   *   Array of Movie/TV fields raw data from TMDb API.
   */
  private function massageTeaserFields(array &$teaser): void {
    // Replace key "media_type" with "bundle".
    $teaser['bundle'] = $teaser['media_type'];
    unset($teaser['media_type']);
    // Bundle "TV" use keys "name". We reduce everything to one form, i.e. "title".
    $teaser['title'] = $teaser['title'] ?: $teaser['name'];
    unset($teaser['name']);
    $teaser['original_title'] = $teaser['original_title'] ?: $teaser['original_name'];
    unset($teaser['original_name']);
  }

  /**
   * Merges movie and TV credits into a combined array.
   *
   * This method takes separate movie and TV credits arrays and merges them
   * into a single array, preserving unique credits based on their ID. It also
   * sets the "bundle" key to distinguish between movie and TV credits.
   *
   * @param array $movie_credits
   *   An array of credits for movies. Expected to contain "cast" and "crew"
   *   sub-arrays.
   * @param array $tv_credits
   *   An array of credits for TV shows. Expected to contain "cast" and "crew"
   *   sub-arrays.
   *
   * @return array
   *   An associative array containing "cast" and "crew" sub-arrays with
   *   combined credits. The keys are the credit IDs, and the values are the
   *   credit data, with an additional "bundle" key to distinguish between
   *   movie and TV credits.
   */
  private function mergeCredits(array $movie_credits, array $tv_credits): array {
    $cast = $crew = [];

    // Cast.
    foreach ($movie_credits['cast'] as $credit) {
      $credit['bundle'] = NodeBundle::movie;
      $cast[$credit['id']] = $credit;
    }
    foreach ($tv_credits['cast'] as $credit) {
      $credit['bundle'] = NodeBundle::tv;
      $cast[$credit['id']] = $credit;
    }

    // Crew.
    foreach ($movie_credits['crew'] as $credit) {
      $credit['bundle'] = NodeBundle::movie;
      $crew[$credit['id']] = $credit;
    }
    foreach ($tv_credits['crew'] as $credit) {
      $credit['bundle'] = NodeBundle::tv;
      $crew[$credit['id']] = $credit;
    }

    return [
      'cast' => $cast,
      'crew' => $crew,
    ];
  }

}
