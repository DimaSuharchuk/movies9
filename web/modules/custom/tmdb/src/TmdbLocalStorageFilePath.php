<?php

namespace Drupal\tmdb;

class TmdbLocalStorageFilePath {

  private string $path;


  /**
   * Create path to file in TMDb Local Storage.
   *
   * @param string $bin_directory
   *   Most parent directory for some request type.
   * @param string $unique_name
   *   File unique name.
   * @param array $nested_directories
   *   Optional array of nested directories where every next element of array
   *   is a child of previous element of array.
   */
  public function __construct(string $bin_directory, string $unique_name, array $nested_directories = []) {
    $nested = '';
    if ($nested_directories) {
      $nested = implode('/', $nested_directories) . '/';
    }

    // Build path.
    $this->path = "{$bin_directory}/{$nested}{$unique_name}.bin";
  }

  public function __toString(): string {
    return $this->path;
  }

}
