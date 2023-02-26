<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorage;
use Drupal\tmdb\TmdbLocalStorageFilePath;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tmdb\Client;
use Tmdb\Event\BeforeRequestEvent;
use Tmdb\Event\Listener\Request\AcceptJsonRequestListener;
use Tmdb\Event\Listener\Request\ApiTokenRequestListener;
use Tmdb\Event\Listener\Request\ContentTypeJsonRequestListener;
use Tmdb\Event\Listener\Request\UserAgentRequestListener;
use Tmdb\Event\Listener\RequestListener;
use Tmdb\Event\RequestEvent;
use Tmdb\Exception\TmdbApiException;
use Tmdb\Token\Api\ApiToken;
use const TMDB_API_KEY;

abstract class CacheableTmdbRequest {

  protected TmdbLocalStorage $local_storage;

  protected Client $connect;

  /**
   * Response from TMDb API already cached into TMDbLocalStorage.
   *
   * @return array|null
   *   Response from TMDb API or NULL if an error occurs.
   *
   * @see TmdbLocalStorage
   */
  public function response(): ?array {
    $this->local_storage = Drupal::service('tmdb.local_storage');
    $file_path = $this->getStorageFilePath();

    $data = &drupal_static(__METHOD__ . $file_path);

    if (!is_null($data)) {
      return $data;
    }

    if (!$data = $this->local_storage->load($file_path)) {
      // Create and save connect to TMDb API.
      $token = new ApiToken(TMDB_API_KEY);
      $ed = new EventDispatcher();
      $this->connect = new Client([
        'api_token' => $token,
        'event_dispatcher' => [
          'adapter' => $ed,
        ],
        'http' => [
          'client' => new GuzzleClient(),
        ],
      ]);
      /**
       * Required event listeners and events to be registered with the PSR-14 Event Dispatcher.
       */
      $requestListener = new RequestListener($this->connect->getHttpClient(), $ed);
      $ed->addListener(RequestEvent::class, $requestListener);

      $apiTokenListener = new ApiTokenRequestListener($this->connect->getToken());
      $ed->addListener(BeforeRequestEvent::class, $apiTokenListener);

      $acceptJsonListener = new AcceptJsonRequestListener();
      $ed->addListener(BeforeRequestEvent::class, $acceptJsonListener);

      $jsonContentTypeListener = new ContentTypeJsonRequestListener();
      $ed->addListener(BeforeRequestEvent::class, $jsonContentTypeListener);

      $userAgentListener = new UserAgentRequestListener();
      $ed->addListener(BeforeRequestEvent::class, $userAgentListener);

      try {
        $data = $this->request();
      }
      catch (TmdbApiException $e) {
        // Don't wanna log "404".
        if ($e->getCode() == TmdbApiException::STATUS_RESOURCE_NOT_FOUND) {
          $data = [];

          return [];
        }

        Drupal::logger(static::class)
          ->info($e->getMessage() . "<br><br>\n\n" . $e->getTraceAsString());

        return NULL;
      }
      // Prepare data to caching.
      $data = $this->massageBeforeSave($data);
      // Cache data.
      $this->local_storage->save($file_path, $data);
    }
    // Prepare data to return.
    $this->massageAfterLoad($data);

    return $data;
  }

  /**
   * Only check is the "static" request cached.
   *
   * @return bool
   *   Local file exists.
   */
  public function hasCache(): bool {
    $this->local_storage = Drupal::service('tmdb.local_storage');
    $file_path = $this->getStorageFilePath();

    return $this->local_storage->checkFile($file_path);
  }

  /**
   * Make non-cached request to TMDb API.
   *
   * @throws TmdbApiException
   */
  abstract protected function request(): array;

  /**
   * OPTIONAL: Modify the data before sending it to the client code.
   *
   * @param array $data
   *   Data from the cache file for possible changes.
   */
  protected function massageAfterLoad(array &$data) {
  }

  /**
   * OPTIONAL: Prepare and clear raw data from junk to save to a file.
   *
   * @param array $data
   *   This is usually a response from TMDb API.
   *
   * @return array
   *   Filtered and prepared data.
   */
  protected function massageBeforeSave(array $data): array {
    return $data;
  }

  /**
   * Return the local storage path where the cache should be stored.
   *
   * @return TmdbLocalStorageFilePath
   */
  abstract protected function getStorageFilePath(): TmdbLocalStorageFilePath;

  /**
   * Helper method. Filter raw array of arrays by fields from $allowed_fields
   * array.
   *
   * @param array[] $raw
   *   Raw data.
   * @param string[] $allowed_fields
   *   List of allowed fields that are associative keys in $raw.
   *
   * @return array[]
   *   Filtered data.
   */
  protected function allowedFieldsFilter(array $raw, array $allowed_fields): array {
    $purged = [];
    foreach ($raw as $i => $item) {
      foreach ($allowed_fields as $field) {
        $purged[$i][$field] = $item[$field] ?? NULL;
      }
    }
    return $purged;
  }

  /**
   * Helper method.
   *
   * @param NodeBundle $bundle
   *
   * @return \Tmdb\Api\Movies|\Tmdb\Api\Tv
   */
  protected function nodeApi(NodeBundle $bundle) {
    return NodeBundle::movie() === $bundle ? $this->connect->getMoviesApi() : $this->connect->getTvApi();
  }

  /**
   * Helper method used in a few places.
   * Purge fields of recommendations or similar arrays.
   *
   * @param array[] $data
   *
   * @return array[]
   */
  protected function purgeRecommendationsFields(array $data): array {
    $data['results'] = $this->purgeNodeTeasers($data['results']);

    return $data;
  }

  /**
   * Helper method used in a few places.
   * Purge fields of node teaser and rename "name" and "original_name" fields
   * of TV show to "title" and "original_title" like in "movie" bundle.
   *
   * @param array $raw_teasers
   *   Teasers from TMDb API.
   *
   * @return array
   */
  protected function purgeNodeTeasers(array $raw_teasers): array {
    $allowed_fields = [
      'id',
      'poster_path',
      'title',
      'original_title',
    ];

    $purged = [];
    foreach ($raw_teasers as $i => $teaser) {
      foreach ($allowed_fields as $field) {
        if ($field === 'title' && !isset($teaser['title'])) {
          $purged[$i]['title'] = $teaser['name'];
        }
        elseif ($field === 'original_title' && !isset($teaser['original_title'])) {
          $purged[$i]['original_title'] = $teaser['original_name'];
        }
        else {
          $purged[$i][$field] = $teaser[$field];
        }
      }
    }
    return $purged;
  }

}
