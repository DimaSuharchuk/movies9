<?php

namespace Drupal\tmdb;

class TmdbLocalStorageFilePath {

  private string $path;

  /**
   * Create a path to file in Local Storage.
   *
   * @param string $bin_directory
   *   Most parent directory for some request type.
   * @param string $unique_name
   *   File unique name.
   * @param array $nested_directories
   *   Optional array of nested directories where every next element of array
   *   is a child of array's previous element.
   */
  public function __construct(string $bin_directory, string $unique_name, array $nested_directories = []) {
    $path_components = [];
    array_push($path_components, $bin_directory, ...$nested_directories, ...[$unique_name]);
    // Build a path to a cache file.
    $this->path = implode('/', $path_components) . '.bin';
  }

  public function __toString(): string {
    return $this->path;
  }

}
