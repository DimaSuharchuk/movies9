<?php

namespace Drupal\mvs\Repository;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Exception;
use PDO;

/**
 * Class BaseRepository.
 *
 * @package Drupal\mvs\Repository
 */
abstract class BaseRepository implements BaseRepositoryInterface {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * LessonManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $logger) {
    $this->database = $database;
    $this->logger = $logger->get(static::getLoggerName());
  }

  /**
   * {@inheritdoc}
   */
  public function findById(int $id): array {
    return (array) $this->database->select($this::getTable(), 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function findBy(array $filters): array {
    $query = $this->database->select($this::getTable(), 't');

    foreach ($filters as $key => $value) {
      $query->condition($key, $value);
    }

    $query->fields('t');

    return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $data): int|string|StatementInterface|null {
    if ($this->validateFields($data)) {
      try {
        return $this->database
          ->insert($this::getTable())
          ->fields($data)
          ->execute();
      }
      catch (Exception $e) {
        // Try to write over existing value.
        $this->logger->error($e->getMessage());
      }
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, array $data): int|string|StatementInterface|null {
    if ($this->validateFields($data)) {
      $query = $this->database->update($this::getTable());
      $query->condition('id', $id);

      return $query->fields($data)->execute();
    }

    return 0;
  }

  /**
   * {@inheritDoc}
   */
  public function delete(int $id): int {
    return $this->database->delete($this::getTable())
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Delete items by columns.
   *
   * @param array $data
   *   Columns. Column name => value.
   *
   * @return int
   *   Result.
   */
  public function deleteByColumns(array $data): int {
    if ($this->validateFields($data)) {
      $query = $this->database->delete($this::getTable());

      foreach ($data as $name => $value) {
        $query->condition($name, $value);
      }

      return $query->execute();
    }

    return 0;
  }

  /**
   * Delete all items in table.
   */
  public function truncate(): void {
    $this->database->truncate($this::getTable())->execute();
  }

  /**
   * Upsert multiple values in a single query.
   *
   * @param string $key
   * @param array $fields
   * @param array $data
   *
   * @return void
   * @throws \Exception
   */
  protected function upsert(string $key, array $fields, array $data): void {
    if (
      !$this->validateFields([$key])
      || !$this->validateFields($fields)
    ) {
      throw new Exception('Incorrect keys for the table.');
    }

    $query = $this->database->upsert($this::getTable())
      ->key($key)
      ->fields($fields);

    foreach ($data as $array) {
      if ($values = array_filter($array, fn($name) => array_key_exists($name, $fields), ARRAY_FILTER_USE_KEY)) {
        $query->values($values);
      }
    }

    $query->execute();
  }

  /**
   * Validate fields.
   *
   * @param array $data
   *   Data.
   *
   * @return bool
   *   Result.
   */
  protected function validateFields(array $data): bool {
    foreach ($data as $name => $value) {
      if (!$this->database->schema()->fieldExists(
        $this::getTable(),
        $name
      )) {
        $this->logger->error(
          'There is no column with name %column in table %table',
          ['%column' => $name, '%table' => $this::getTable()]
        );

        return FALSE;
      }
    }

    return TRUE;
  }

}
