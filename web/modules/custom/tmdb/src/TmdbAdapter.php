<?php

namespace Drupal\tmdb;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbLocalStorageType;
use Tmdb\ApiToken;
use Tmdb\Client;

class TmdbAdapter {

  private Settings $settings;

  private MessengerInterface $messenger;

  private TmdbLocalStorage $tmdb_storage;


  public function __construct(Settings $settings, MessengerInterface $messenger, TmdbLocalStorage $tmdb_storage) {
    $this->settings = $settings;
    $this->messenger = $messenger;
    $this->tmdb_storage = $tmdb_storage;
  }


  /**
   * This method creates bridge between some Back-End like "extra fields" and
   * TMDb API or TMDb Local Storage.
   *
   * @param NodeBundle $bundle
   *   Node bundle like "Movie" or "TV".
   * @param int $tmdb_id
   *   TMDb ID.
   * @param Language $lang
   *   Language of site.
   * @param string $field_name
   *   Field name from TMDb API.
   *
   * @return array|false|float|mixed|null
   *   Field value.
   */
  public function getFieldValue(NodeBundle $bundle, int $tmdb_id, Language $lang, string $field_name) {
    $storage_type = $this->getStorageTypeByFieldName($field_name);

    $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);

    if (!$data = $this->tmdb_storage->load($file_path)) {
      switch ($bundle) {
        case NodeBundle::movie():
          $data = $this->connect()->getMoviesApi()->getMovie($tmdb_id, [
            'language' => $lang->value(),
            'append_to_response' => 'recommendations,similar,videos,credits',
          ]);
          break;

        case NodeBundle::tv():
          $data = $this->connect()->getTvApi()->getTvshow($tmdb_id, [
            'language' => $lang->value(),
            'append_to_response' => 'recommendations,similar,videos,credits,external_ids',
          ]);
          // Get average episode runtime.
          if ($time_arr = $data['episode_run_time']) {
            if (is_array($time_arr)) {
              $data['episode_run_time'] = round(array_sum($time_arr) / count($time_arr));
            }
          }
          // Move IMDb ID to common fields.
          if (isset($data['external_ids']['imdb_id'])) {
            $data['imdb_id'] = $data['external_ids']['imdb_id'];
          }
          break;
      }
      // If data fetched successfully from TMDb API - save it in local storage.
      if ($data) {
        $storage_type = TmdbLocalStorageType::recommendations();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id, 1);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data['recommendations']));

        $storage_type = TmdbLocalStorageType::similar();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id, 1);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data['similar']));

        $storage_type = TmdbLocalStorageType::videos();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data['videos']));

        $storage_type = TmdbLocalStorageType::cast();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data['credits']));

        $storage_type = TmdbLocalStorageType::crew();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data['credits']));

        // Save "common" storage last for saving in variable "$storage_type"
        // the value "common". It's a hack!
        $storage_type = TmdbLocalStorageType::common();
        $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);
        $this->tmdb_storage->save($file_path, $this->purge($storage_type, $data));
      }

    }

    return ($storage_type === TmdbLocalStorageType::common() ? $data[$field_name] : $data) ?: NULL;
  }

  /**
   * Get all genres for type "Movie" from TMDb API.
   *
   * @param Language $lang
   *   The language in which the genres will be returned.
   *
   * @return array
   */
  public function getMovieGenres(Language $lang): array {
    return $this->connect()
      ->getGenresApi()
      ->getMovieGenres(['language' => $lang->value()]);
  }

  /**
   * Get all genres for type "TV" from TMDb API.
   *
   * @param Language $lang
   *   The language in which the genres will be returned.
   *
   * @return array
   */
  public function getTvGenres(Language $lang): array {
    return $this->connect()
      ->getGenresApi()
      ->getTvGenres(['language' => $lang->value()]);
  }

  /**
   * Search movie or TV show in TMDb API and return a little information as a
   * "teaser".
   *
   * @param string $imdb_id
   * @param Language $lang
   *
   * @return array[NodeBundle, array]|null
   */
  public function findByImdbId(string $imdb_id, Language $lang): ?array {
    // Request to API.
    $response = $this->connect()->getFindApi()->findBy($imdb_id, [
        'external_source' => 'imdb_id',
        'language' => $lang->value(),
      ]
    );

    foreach ($response as $k => $v) {
      if ($v) {
        switch ($k) {
          case 'movie_results':
            return [
              'type' => NodeBundle::movie(),
              'data' => reset($v),
            ];
            break;

          case 'tv_results':
            return [
              'type' => NodeBundle::tv(),
              'data' => reset($v),
            ];
            break;

          default:
            $this->messenger->addWarning(t('Undefined type %t returned from API\'s method FIND. IMDb ID: %id.', [
              '%t' => $k,
              '%id' => $imdb_id,
            ]));
        }
        break; // No need to continue check other empty sub-arrays.
      }
    }

    return NULL;
  }


  /**
   * Create connection to TMDb API.
   *
   * @return \Tmdb\Client
   */
  private function connect(): Client {
    $apiKey = $this->settings::get('tmdb_api_key');
    $token = new ApiToken($apiKey);
    return new Client($token);
  }


  /**
   * Clear data comparing with allowed fields before saving to file (json).
   *
   * @param TmdbLocalStorageType $storage_type
   *   TMDb Local Storage bin name.
   *   All bins here: self::getStorageTypeByFieldName().
   * @param array $data
   *   Data for purging.
   *
   * @return array
   *   Purged data.
   *
   * @see TmdbAdapter::getStorageTypeByFieldName()
   */
  private function purge(TmdbLocalStorageType $storage_type, array $data): array {
    $new_data = [];

    $allowed_fields = $this->getStorageAllowedFields($storage_type);
    foreach ($data as $field => $value) {
      if (in_array($field, $allowed_fields)) {
        if (is_array($value) && isset($value[0]) && is_array($value[0])) {
          $allowed_sub_fields = $this->getAllowedSubFields($field, $storage_type);
          $new_data[$field] = [];
          foreach ($value as $key => $item) {
            foreach ($item as $sub_field_name => $sub_field_value) {
              if (in_array($sub_field_name, $allowed_sub_fields)) {
                $new_data[$field][$key][$sub_field_name] = $sub_field_value;
              }
            }
          }
        }
        else {
          $new_data[$field] = $value;
        }
      }
    }

    return $new_data;
  }

  /**
   * Get all TMDb Local Storage bins.
   *
   * @param string $field_name
   *   The name of the field by which it is determined which type of storage it
   *   refers to.
   *
   * @return TmdbLocalStorageType
   */
  private function getStorageTypeByFieldName(string $field_name): TmdbLocalStorageType {
    switch ($field_name) {
      case 'recommendations':
      case 'similar':
      case 'videos':
      case 'cast':
      case 'crew':
        return TmdbLocalStorageType::$field_name();

      default:
        return TmdbLocalStorageType::common();
    }
  }

  /**
   * Get a list of allowed field for current storage type.
   *
   * @param TmdbLocalStorageType $storage_type
   *
   * @return string[]
   */
  private function getStorageAllowedFields(TmdbLocalStorageType $storage_type): array {
    $fields = [];

    switch ($storage_type) {
      case TmdbLocalStorageType::common():
        $fields = [
          // movie fields
          'id',
          'imdb_id',
          'belongs_to_collection', // array
          'homepage',
          'overview',
          'production_companies', // array
          'release_date',
          'runtime',
          // tv fields
          'created_by', // array
          'episode_run_time', // array should be converted to int
          'first_air_date',
          'in_production',
          'last_air_date',
          'networks', // array
          'number_of_episodes',
          'number_of_seasons',
          'seasons', // array
        ];
        break;

      case TmdbLocalStorageType::recommendations():
      case TmdbLocalStorageType::similar():
        $fields = [
          'results',
          'total_pages',
          'total_results',
        ];
        break;

      case TmdbLocalStorageType::videos():
        $fields = ['results'];
        break;

      case TmdbLocalStorageType::cast():
        $fields = ['cast'];
        break;

      case TmdbLocalStorageType::crew():
        $fields = ['crew'];
        break;
    }

    return $fields;
  }

  /**
   * Get a list of allowed fields for some iterable TMDb fields.
   *
   * @param string $field_name
   *   Name of field from TMDb API.
   * @param TmdbLocalStorageType $storage_type
   *   TMDb Local Storage.
   *
   * @return string[]
   */
  private function getAllowedSubFields(string $field_name, TmdbLocalStorageType $storage_type): array {
    $fields = [];

    switch ($field_name) {
      case 'production_companies':
      case 'networks':
        $fields = [
          'id',
          'name',
          'logo_path',
        ];
        break;

      case 'created_by':
        $fields = [
          'id',
          'name',
          'profile_path',
        ];
        break;

      case 'seasons':
        $fields = [
          'id',
          'name',
          'overview',
          'air_date',
          'poster_path',
          'season_number',
          'episode_count',
        ];
        break;

      case 'cast':
        $fields = [
          'character',
          'id',
          'name',
          'profile_path',
        ];
        break;

      case 'crew':
        $fields = [
          'department',
          'id',
          'name',
          'job',
          'profile_path',
        ];
        break;

      case 'results':
        switch ($storage_type) {
          case TmdbLocalStorageType::recommendations():
          case TmdbLocalStorageType::similar():
            $fields = [
              // movie fields
              'id',
              'title',
              'genre_ids',
              'poster_path',
              // tv fields
              'name',
            ];
            break;

          case TmdbLocalStorageType::videos():
            $fields = [
              'key',
              'name',
              'site',
              'size',
            ];
            break;
        }
        break;
    }

    return $fields;
  }

}
