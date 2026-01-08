<?php

namespace Drupal\mvs;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\mvs\enum\EntityBundle;
use Drupal\mvs\enum\EntityType;

class EntityFinder {

  private EntityTypeManagerInterface $entity_type_manager;

  private ?EntityStorageInterface $storage;

  private array $search_values = [];

  private int $limit = 0;

  private bool $reduce = FALSE;

  private bool $count = FALSE;

  private bool $load = FALSE;

  private array $order = [];

  private bool $random = FALSE;

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
    return $this->findNodes()->byBundle(EntityBundle::movie);
  }

  /**
   * Find nodes of type "TV".
   * 1-2 query step.
   *
   * @return $this
   */
  public function findNodesTv(): self {
    return $this->findNodes()->byBundle(EntityBundle::tv);
  }

  /**
   * Find taxonomy terms of type "Genre".
   * 1-2 query step.
   *
   * @return $this
   */
  public function findTermsGenres(): self {
    return $this->findTerms()->byBundle(EntityBundle::genre);
  }

  /**
   * Find nodes.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findNodes(): self {
    return $this->findEntities(EntityType::node);
  }

  /**
   * Find taxonomy terms.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findTerms(): self {
    return $this->findEntities(EntityType::term);
  }

  /**
   * Find Person content entities.
   * 1-st *required* query step.
   *
   * @return $this
   */
  public function findPersons(): self {
    return $this->findEntities(EntityType::person);
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
        // Convert "Entity Bundle"s to strings.
        $bundles = array_column($bundles, 'name');
        // Set bundles.
        $this->search_values[$bundle_key] = [
          'value' => $bundles,
          'operator' => 'IN',
        ];
      }
    }
    catch (PluginNotFoundException) {
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
   * Set additional optional conditions in a search query.
   *
   * @param string $property
   *   It should be property or field of entity.
   *   For example, "uid", "title", "field_imdb_id" etc.
   * @param $value
   *   Value of property of field for search.
   *
   * @return $this
   */
  public function addCondition(string $property, $value, ?string $operator = NULL): self {
    $this->search_values[$property] = [
      'value' => $value,
      'operator' => $operator,
    ];

    return $this;
  }

  /**
   * Add sorting criteria to the query.
   *
   * @param string $property
   *   The property by which to sort.
   * @param bool $asc_direction
   *   TRUE sorts in ascending order, FALSE sorts in descending order.
   *
   * @return $this
   */
  public function addOrderBy(string $property, bool $asc_direction): self {
    $this->order[$property] = $asc_direction ? 'ASC' : 'DESC';

    return $this;
  }

  /**
   * Adds random sorting.
   *
   * Does not work together with sorting by criteria.
   *
   * @return $this
   */
  public function randomOrder(): self {
    $this->random = TRUE;

    return $this;
  }

  /**
   * The query should return only a single result.
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
    $this->limit = max($limit, 0);

    return $this;
  }

  /**
   * The query will return the number of results.
   *
   * @return $this
   */
  public function count(): self {
    $this->count = TRUE;

    return $this;
  }

  /**
   * Drupal's entities should be loaded instead of their IDs.
   *
   * @return $this
   */
  public function loadEntities(): self {
    $this->load = TRUE;

    return $this;
  }

  /**
   * Last *required* step return results of the query.
   *
   * @return EntityInterface|EntityInterface[]|int|mixed
   */
  public function execute(): mixed {
    $return = $ids = $this->findByProperties();

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
    $this->order = [];
    $this->random = FALSE;
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
   *   "Drupal entity" or null if an error occurred in the process.
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
   */
  private function buildPropertyQuery(QueryInterface $entity_query): void {
    foreach ($this->search_values as $name => $item) {
      if (!isset($item['value'])) {
        continue;
      }

      if (empty($item['operator'])) {
        $item['operator'] = is_array($item['value']) ? 'IN' : '=';
      }

      $entity_query->condition($name, $item['value'], $item['operator']);
    }
  }

  /**
   * Updated Drupal core method EntityStorageInterface::loadByProperties(),
   * added limit to query, and we don't load Drupal entities in this method
   * unnecessarily if the query should return only IDs.
   *
   * @return EntityInterface[]
   *
   * @see EntityFinder::addCondition()
   *
   * @see EntityStorageInterface::loadByProperties()
   * @see self::addCondition()
   * @see self::addOrderBy()
   * @see self::randomOrder()
   */
  private function findByProperties(): array {
    if ($this->storage) {
      // Build a query to fetch the entity IDs.
      $entity_query = $this->storage->getQuery();
      $entity_query->accessCheck(FALSE);

      if ($this->limit) {
        $entity_query->range(0, $this->limit);
      }

      if ($this->order) {
        foreach ($this->order as $property => $direction) {
          $entity_query->sort($property, $direction);
        }
      }
      elseif ($this->random) {
        $entity_query->addTag('random_sort');
      }

      $this->buildPropertyQuery($entity_query);
      $result = $entity_query->execute();

      return $result ?: [];
    }

    return [];
  }

  /**
   * Load Drupal storage by entity type.
   *
   * @param EntityType $type
   *   Drupal's entity type, like "node", "taxonomy_term" etc.
   */
  private function getStorage(EntityType $type): void {
    try {
      $this->storage = $this->entity_type_manager->getStorage($type->value);
    }
    catch (PluginException) {
      $this->storage = NULL;
    }
  }

}
