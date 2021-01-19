<?php

namespace Drupal\imdb;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\imdb\enum\EntityBundle;
use Drupal\imdb\enum\EntityType;

class EntityFinder {

  private EntityTypeManagerInterface $entity_type_manager;

  private ?EntityStorageInterface $storage;

  private array $search_values = [];

  private int $limit = 0;

  private bool $reduce = FALSE;

  private bool $count = FALSE;

  private bool $load = FALSE;


  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entity_type_manager = $entity_type_manager;
  }


  /**
   * Find nodes of type "Movie".
   * 1-2 query step.
   *
   * @return $this
   */
  public function findNodesMovie(): self {
    return $this->findNodes()->byBundle(EntityBundle::movie());
  }

  /**
   * Find nodes of type "TV".
   * 1-2 query step.
   *
   * @return $this
   */
  public function findNodesTv(): self {
    return $this->findNodes()->byBundle(EntityBundle::tv());
  }

  /**
   * Find taxonomy terms of type "Genre".
   * 1-2 query step.
   *
   * @return $this
   */
  public function findTermsGenres(): self {
    return $this->findTerms()->byBundle(EntityBundle::genre());
  }

  /**
   * Find nodes.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findNodes(): self {
    return $this->findEntities(EntityType::node());
  }

  /**
   * Find taxonomy terms.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findTerms(): self {
    return $this->findEntities(EntityType::term());
  }

  /**
   * Find Person content entities.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findPersons(): self {
    return $this->findEntities(EntityType::person());
  }

  /**
   * Find entities of some type.
   * 1-st *required* query step.
   *
   * @param EntityType $type
   *   Entity type like "node" or "taxonomy_term".
   *
   * @return $this
   */
  public function findEntities(EntityType $type): self {
    $this->getStorage($type);
    return $this;
  }


  /**
   * Set bundle.
   * 2-nd *optional* query step.
   *
   * @param EntityBundle $bundle
   *   Bundle of entity, like "movie" for nodes, or "genre" for terms.
   *
   * @return $this
   */
  public function byBundle(EntityBundle $bundle): self {
    return $this->byBundles([$bundle]);
  }

  /**
   * Set array of bundles.
   * 2-nd *optional* query step.
   *
   * @param EntityBundle[] $bundles
   *   Bundles of entity, like ["movie", "tv"] for nodes, or ["genre"] for
   *   terms.
   *
   * @return $this
   */
  public function byBundles(array $bundles): self {
    try {
      $bundle_key = $this->entity_type_manager
        ->getDefinition($this->storage->getEntityTypeId())
        ->getKey('bundle');
      if ($bundle_key) {
        // Convert Entity Bundles to strings.
        array_walk($bundles, function (&$value) {
          $value = $value->value();
        });
        // Set bundles.
        $this->search_values[$bundle_key] = $bundles;
      }
    } catch (PluginNotFoundException $e) {
    }
    return $this;
  }


  /**
   * It's a condition, that means "search by one TMDb ID".
   *
   * Attention: Search by TMDb ID with NodeBundle, because TMDb ID may be
   * repeated.
   *
   * @param int $tmdb_id
   *
   * @return $this
   * @see EntityFinder::addCondition()
   */
  public function byTmdbId(int $tmdb_id): self {
    $this->reduce();
    return $this->byTmdbIds([$tmdb_id]);
  }

  /**
   * It's a condition, that means "search by array of TMDb IDs".
   *
   * Attention: Search by TMDb ID with NodeBundle, because TMDb ID may be
   * repeated.
   *
   * @param int[] $tmdb_ids
   *
   * @return $this
   * @see EntityFinder::addCondition()
   */
  public function byTmdbIds(array $tmdb_ids): self {
    return $this->addCondition('field_tmdb_id', $tmdb_ids);
  }

  /**
   * It's a condition, that means "search by one IMDb ID".
   *
   * @param string $imdb_id
   *
   * @return $this
   * @see EntityFinder::addCondition()
   */
  public function byImdbId(string $imdb_id): self {
    $this->reduce();
    return $this->byImdbIds([$imdb_id]);
  }

  /**
   * It's a condition, that means "search by array of IMDb IDs".
   *
   * @param string[] $imdb_ids
   *
   * @return $this
   * @see EntityFinder::addCondition()
   */
  public function byImdbIds(array $imdb_ids): self {
    return $this->addCondition('field_imdb_id', $imdb_ids);
  }

  /**
   * Set additional optional conditions in search query.
   *
   * @param string $property
   *   It should be property or field of entity. For example: "uid", "title",
   *   "field_imdb_id" etc.
   * @param $value
   *   Value of property of field for search.
   *
   * @return $this
   */
  public function addCondition(string $property, $value): self {
    $this->search_values[$property] = $value;
    return $this;
  }


  /**
   * Query should return only single result.
   *
   * @return $this
   */
  public function reduce(): self {
    $this->limit = 1;
    $this->reduce = TRUE;
    return $this;
  }

  /**
   * Query should return as many results as indicated in the "limit".
   *
   * @param int $limit
   *   The number of results will be returned no more than indicated here.
   *
   * @return $this
   */
  public function limit(int $limit): self {
    $this->limit = $limit > 0 ? $limit : 0;
    return $this;
  }


  /**
   * Query will return the number of results.
   *
   * @return $this
   */
  public function count(): self {
    $this->count = TRUE;
    return $this;
  }


  /**
   * Drupal entities should be loaded instead of their IDs.
   *
   * @return $this
   */
  public function loadEntities(): self {
    $this->load = TRUE;
    return $this;
  }


  /**
   * Last *required* step return results of query.
   *
   * @return EntityInterface|EntityInterface[]|int|mixed
   */
  public function execute() {
    $return = $ids = $this->findByProperties($this->search_values);

    if ($this->count) {
      $return = count($ids);
    }

    if ($this->load) {
      $return = $ids ? $this->loadMultipleById($ids) : [];
    }

    if ($this->reduce && is_array($return)) {
      $return = reset($return);
    }

    $this->storage = NULL;
    $this->search_values = [];
    $this->limit = 0;
    $this->reduce = FALSE;
    $this->count = FALSE;
    $this->load = FALSE;

    return $return;
  }


  /**
   * Loads an entity by its identifier.
   *
   * This method doesn't quite match the request template of this service,
   * since there is no need to use the method "execute()" additionally.
   *
   * @param int $id
   *   Drupal ID of Drupal entity.
   *
   * @return EntityInterface|null
   *   Drupal entity or null if an error occurred in the process.
   */
  public function loadById(int $id): ?EntityInterface {
    $entities = $this->loadMultipleById([$id]);
    return $entities ? reset($entities) : NULL;
  }

  /**
   * Load an entities by their identifier.
   *
   * This method doesn't quite match the request template of this service,
   * since there is no need to use the method "execute()" additionally.
   *
   * @param array $ids
   *   Drupal ID of Drupal entities.
   *
   * @return EntityInterface[]
   *   Array of Drupal entities or empty array if an error occurred in the
   *   process.
   */
  public function loadMultipleById(array $ids): array {
    return $this->storage->loadMultiple($ids);
  }


  /**
   * Builds an entity query.
   *
   * @param QueryInterface $entity_query
   *   EntityQuery instance.
   * @param array $values
   *   An associative array of properties of the entity, where the keys are the
   *   property names and the values are the values those properties must have.
   */
  private function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    foreach ($values as $name => $value) {
      // Cast scalars to array so we can consistently use an IN condition.
      $entity_query->condition($name, (array) $value, 'IN');
    }
  }

  /**
   * Updated Drupal core method EntityStorageInterface::loadByProperties(),
   * added limit to query and we don't load Drupal entities in this method
   * unnecessarily if query should return only IDs.
   *
   * @param array $values
   *   Search terms. See self::addCondition().
   *
   * @return EntityInterface[]
   *
   * @see EntityFinder::addCondition()
   *
   * @see EntityStorageInterface::loadByProperties()
   */
  private function findByProperties(array $values = []): array {
    if ($this->storage) {
      // Build a query to fetch the entity IDs.
      $entity_query = $this->storage->getQuery();
      $entity_query->accessCheck(FALSE);
      if ($this->limit) {
        $entity_query->range(0, $this->limit);
      }
      $this->buildPropertyQuery($entity_query, $values);
      $result = $entity_query->execute();

      return $result ?: [];
    }
    return [];
  }

  /**
   * Load Drupal storage by entity type.
   *
   * @param EntityType $type
   *   Drupal entity type, like "node", "taxonomy_term" etc.
   */
  private function getStorage(EntityType $type): void {
    try {
      $this->storage = $this->entity_type_manager->getStorage($type->value());
    } catch (PluginException $_) {
      $this->storage = NULL;
    }
  }

}
