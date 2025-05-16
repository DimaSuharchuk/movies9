<?php

namespace Drupal\tmdb;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Core\Site\Settings;

class TmdbLocalStorage {

  private FileStorage $tmdb_storage;

  /**
   * TmdbLocalStorage constructor.
   *
   * @param Settings $settings
   */
  public function __construct(Settings $settings) {
    $this->tmdb_storage = new FileStorage([
      'directory' => $settings::get('file_private_path'),
      'bin' => 'tmdb_storage',
    ]);
  }

  /**
   * Checks if a file exists in this path.
   *
   * @param TmdbLocalStorageFilePath $file_path
   *
   * @return bool
   */
  public function checkFile(TmdbLocalStorageFilePath $file_path): bool {
    return $this->tmdb_storage->exists($file_path);
  }

  /**
   * Load data from a file path if a file exists.
   *
   * @param TmdbLocalStorageFilePath $file_path
   *   Path to file for a load.
   *
   * @return array|null
   */
  public function load(TmdbLocalStorageFilePath $file_path): ?array {
    if ($this->checkFile($file_path)) {
      return igbinary_unserialize(
        file_get_contents($this->tmdb_storage->getFullPath($file_path))
      );
    }

    return NULL;
  }

  /**
   * Save data in file.
   *
   * @param TmdbLocalStorageFilePath $file_path
   *   Path to file.
   * @param array $data
   *   Data for saving.
   */
  public function save(TmdbLocalStorageFilePath $file_path, array $data): void {
    $this->tmdb_storage->save($file_path, igbinary_serialize($data));
  }

}
