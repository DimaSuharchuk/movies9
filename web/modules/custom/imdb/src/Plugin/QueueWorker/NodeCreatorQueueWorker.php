<?php

namespace Drupal\imdb\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imdb\EntityCreator;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\TmdbAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id="create_node_by_imdb_id_worker",
 *   title="Create node by IMDb ID",
 *   cron={"time" = 30}
 * )
 */
class NodeCreatorQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var Messenger|object|null
   */
  private $messenger;

  /**
   * @var TmdbAdapter|object|null
   */
  private $adapter;

  /**
   * @var EntityCreator|object|null
   */
  private $creator;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->messenger = $container->get('messenger');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->creator = $container->get('entity_creator');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    // Search type and data.
    $imdb_id = $data['imdb_id'];
    /** @var Language $lang */
    $lang = $data['lang'];

    // Update status if node already exists.
    if ($this->creator->updateNodeApprovedStatus($imdb_id, $lang)) {
      return;
    }

    $tmdb_response = $this->adapter->findByImdbId($imdb_id, $lang);
    /** @var NodeBundle $node_type */
    $node_type = $tmdb_response['type'];
    $node_data = $tmdb_response['data'];

    // Create movie or TV.
    $node = NULL;
    switch ($node_type) {
      case NodeBundle::movie():
        $node = $this->creator->createNodeMovie(
          $node_data['title'],
          $node_data['id'],
          $imdb_id,
          $node_data['poster_path'],
          $node_data['genre_ids'],
          TRUE,
          $lang
        );
        break;

      case NodeBundle::tv():
        $node = $this->creator->createNodeTv(
          $node_data['name'],
          $node_data['id'],
          $imdb_id,
          $node_data['poster_path'],
          $node_data['genre_ids'],
          TRUE,
          $lang
        );
        break;
    }

    if (!$node) {
      $error = $this->t('%type has not been created with TMDb ID %tmdb_id, title %title, language %lang.', [
        '%type' => $node_type->value(),
        '%tmdb_id' => $node_data['id'],
        '%title' => $node_data['title'] ?: $node_data['name'],
        '%lang' => $lang->value(),
      ]);
      throw new RequeueException($error);
    }
  }

}
