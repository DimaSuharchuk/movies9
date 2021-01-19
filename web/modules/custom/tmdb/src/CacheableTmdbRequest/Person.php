<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\imdb\enum\Language;
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
      'append_to_response' => 'combined_credits,images',
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
      'title',
      'original_title',
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
      'combined_credits' => [
        'cast' => $this->allowedFieldsFilter($data['combined_credits']['cast'], $allowed_teaser_fields),
        'crew' => $this->allowedFieldsFilter($data['combined_credits']['crew'], $allowed_teaser_fields),
      ],
    ];

    if ($images = $this->allowedFieldsFilter($data['images']['profiles'], ['file_path'])) {
      $filtered['images'] = array_column($images, 'file_path');
    }

    return $filtered;
  }

}
