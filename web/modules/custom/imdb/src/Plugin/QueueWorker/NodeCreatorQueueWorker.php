<?php

namespace Drupal\imdb\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imdb\EntityCreator;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\NodeHelper;
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

  private ?EntityCreator $creator;

  private ?TmdbAdapter $adapter;

  private ?NodeHelper $node_helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->creator = $container->get('entity_creator');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->node_helper = $container->get('node_helper');

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
    /** @var NodeBundle $bundle */
    $bundle = $tmdb_response['type'];
    /** @var int $tmdb_id */
    $tmdb_id = $tmdb_response['tmdb_id'];

    // Create movie or TV on all languages.
    if (!$node_id = $this->node_helper->prepareNodeOnAllLanguages($bundle, $tmdb_id, TRUE)) {
      $error = $this->t('%type has not been created with TMDb ID %tmdb_id.', [
        '%type' => $bundle->value(),
        '%tmdb_id' => $tmdb_id,
      ]);
      throw new RequeueException($error);
    }
  }

}
