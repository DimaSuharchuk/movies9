<?php

namespace Drupal\mvs\Repository;

/**
 * Interface BaseRepositoryInterface.
 *
 * @package Drupal\mvs\Repository
 */
interface BaseRepositoryInterface {

  /**
   * Get repository table.
   *
   * @return string
   *   Table name.
   */
  public static function getTable(): string;

  /**
   * Get logger name for dblog type column.
   *
   * @return string
   *   Logger name.
   */
  public static function getLoggerName(): string;

  /**
   * Get item by ID.
   *
   * @param int|string $id
   *   Entity ID.
   *
   * @return array
   *   Entity values.
   */
  public function findById($id): array;

  /**
   * Get items.
   *
   * @param array $filters
   *   Filters.
   *
   * @return array
   *   Result.
   */
  public function findBy(array $filters): array;

  /**
   * Create new record in DB for new entity.
   *
   * @param array $data
   *   Values of the entity.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|string|null
   *   Record ID if successful.
   */
  public function create(array $data);

  /**
   * Update some field(s) of the Entity by ID.
   *
   * @param int|string $id
   *   Entity ID.
   * @param array $data
   *   New values.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|string|null
   *   1 if successful.
   */
  public function update($id, array $data);

  /**
   * Delete item by ID.
   *
   * @param int $id
   *   Entity ID.
   *
   * @return int
   *   1 if successfully deleted and 0 if an entity not found.
   */
  public function delete(int $id): int;

}
