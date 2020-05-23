<?php

namespace Drupal\tmdb;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\imdb\EntityCreator;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\node\Entity\Node;
use Drupal\tmdb\enum\TmdbLocalStorageType;
use Tmdb\ApiToken;
use Tmdb\Client;

class TmdbAdapter {

  private Settings $settings;

  private MessengerInterface $messenger;

  private TmdbLocalStorage $tmdb_storage;

  private EntityCreator $creator;


  public function __construct(Settings $settings, MessengerInterface $messenger, TmdbLocalStorage $tmdb_storage, EntityCreator $creator) {
    $this->settings = $settings;
    $this->messenger = $messenger;
    $this->tmdb_storage = $tmdb_storage;
    $this->creator = $creator;
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
    // @todo Needs refactoring: Multiple fields.
    $black_list = [
      'recommendations',
      'similar',
    ];
    if (in_array($field_name, $black_list)) {
      // Use special methods for these fields.
      return FALSE;
    }

    $storage_type = $this->getStorageTypeByFieldName($field_name);

    $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id);

    if (!$data = $this->tmdb_storage->load($file_path)) {
      // Get data from TMDb API.
      // If data fetched successfully from TMDb API - save it in local storage.
      if ($data = $this->getFullInfoByTmdbId($bundle, $tmdb_id, $lang)) {
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
   * Fetch from TMDb API movie collection and create nodes for them.
   *
   * @param int $movie_tmdb_id
   * @param Language $lang
   *
   * @return Node[]|null
   */
  public function getMovieCollectionItems(int $movie_tmdb_id, Language $lang): ?array {
    if ($collection = $this->getFieldValue(NodeBundle::movie(), $movie_tmdb_id, $lang, 'belongs_to_collection')) {
      // Get collection info from TMDb API.
      return $this->connect()
        ->getCollectionsApi()
        ->getCollection($collection['id'], [
          'language' => $lang->value(),
        ]);
    }

    return NULL;
  }

  /**
   * Fetch from TMDb API or TmdbLocalStorage recommendations or similar teasers
   * for some movie or TV by TMDb ID.
   *
   * @param TmdbLocalStorageType $storage_type
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   * @param int $page
   *   Recommendations and similar responses have maximum 20 teasers on each
   *   page. $page is the page in TMDb API that we want to get.
   *
   * @return array|null
   */
  public function getRecommendationsOrSimilar(TmdbLocalStorageType $storage_type, NodeBundle $bundle, int $tmdb_id, Language $lang, int $page): ?array {
    $file_path = new TmdbLocalStorageFilePath($bundle, $storage_type, $lang, $tmdb_id, $page);

    if (!$data = $this->tmdb_storage->load($file_path)) {
      $method = $storage_type === TmdbLocalStorageType::recommendations() ? 'getRecommendations' : 'getSimilar';

      if ($data = $this->api($bundle)->$method($tmdb_id, [
        'language' => $lang->value(),
        'page' => $page,
      ])) {
        // Not necessary to purge this response.
        $this->tmdb_storage->save($file_path, $data);
      }
    }

    return $data ?: NULL;
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
   * Get IMDb IDs by TMDb IDs.
   *
   * @param int[] $tmdb_ids
   * @param NodeBundle $bundle
   *
   * @return string[]
   */
  public function getImdbIdsByTmdbIds(array $tmdb_ids, NodeBundle $bundle): array {
    // @todo Add saving to file TMDb ID -> IMDb ID.
    $imdb_ids = [];
    foreach ($tmdb_ids as $tmdb_id) {
      if ($res = $this->api($bundle)
        ->get("{$bundle->value()}/{$tmdb_id}/external_ids")) {
        $imdb_ids[$tmdb_id] = $res['imdb_id'];
      }
    }

    return $imdb_ids;
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
   * Get all Movie or Tv info from TMDb API. Non-cached request.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param Language $lang
   *
   * @return array|null
   */
  public function getFullInfoByTmdbId(NodeBundle $bundle, int $tmdb_id, Language $lang): ?array {
    // @todo Cache response.
    $response = NULL;

    switch ($bundle) {
      case NodeBundle::movie():
        $response = $this->api($bundle)->getMovie($tmdb_id, [
          'language' => $lang->value(),
          'append_to_response' => 'recommendations,similar,videos,credits',
        ]);
        break;

      case NodeBundle::tv():
        $response = $this->api($bundle)->getTvshow($tmdb_id, [
          'language' => $lang->value(),
          'append_to_response' => 'recommendations,similar,videos,credits,external_ids',
        ]);

        // Edit something:
        // Duplicate title for more simple fetching title value for movie and tv.
        if ($title = $response['name']) {
          $response['title'] = $title;
        }
        // Get average episode runtime.
        if ($time_arr = $response['episode_run_time']) {
          if (is_array($time_arr)) {
            $response['episode_run_time'] = round(array_sum($time_arr) / count($time_arr));
          }
        }
        // Move IMDb ID to common fields.
        if (isset($response['external_ids']['imdb_id'])) {
          $response['imdb_id'] = $response['external_ids']['imdb_id'];
        }
        break;
    }

    $response['genres_ids'] = array_column($response['genres'], 'id');

    return $response;
  }

  /**
   * Select TMDb API based on the value of the bundle.
   *
   * @param NodeBundle $bundle
   *
   * @return \Tmdb\Api\Movies|\Tmdb\Api\Tv|null
   */
  private function api(NodeBundle $bundle) {
    $api = $this->connect();
    switch ($bundle) {
      case NodeBundle::movie():
        $api = $api->getMoviesApi();
        break;
      case NodeBundle::tv():
        $api = $api->getTvApi();
        break;
      default:
        $api = NULL;
    }
    return $api;
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
          'title',
          'poster_path',
          'belongs_to_collection', // array
          'homepage',
          'overview',
          'production_companies', // array
          'release_date',
          'runtime',
          'imdb_id',
          'genres_ids',
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
      case 'crew':
        $fields = [
          // cast fields
          'character',
          'id',
          'name',
          'profile_path',
          'gender',
          // crew fields
          'department',
          'job',
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
