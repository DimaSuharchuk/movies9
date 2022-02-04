<?php

namespace Drupal\imdb\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mvs\EntityCreator;
use Drupal\mvs\EntityHelper;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbApiAdapter;
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

  private ?EntityCreator $creator;

  private ?TmdbApiAdapter $adapter;

  private ?EntityHelper $entity_helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->creator = $container->get('entity_creator');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->entity_helper = $container->get('entity_helper');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data) {
    $imdb_id = $data['imdb_id'];

    // Check and update "Approved" status if the node already exists.
    if ($this->creator->updateNodeApprovedStatus($imdb_id)) {
      return;
    }

    // Get Node bundle and TMDb ID by IMDb ID.
    $tmdb_response = $this->adapter->getTmdbIdByImdbId($imdb_id);
    if (!$tmdb_response) {
      return;
    }
    /** @var NodeBundle $bundle */
    $bundle = $tmdb_response['type'];
    /** @var int $tmdb_id */
    $tmdb_id = $tmdb_response['tmdb_id'];

    // Create movie or TV on all languages.
    if (!$this->entity_helper->prepareNode($bundle, $tmdb_id, TRUE)) {
      $error = $this->t('%type has not been created with TMDb ID %tmdb_id.', [
        '%type' => $bundle->value(),
        '%tmdb_id' => $tmdb_id,
      ]);
      throw new RequeueException($error);
    }
  }

}
