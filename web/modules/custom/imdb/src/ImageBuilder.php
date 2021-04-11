<?php

namespace Drupal\imdb;

use Drupal\tmdb\enum\TmdbImageFormat;

/**
 * Centralized builder for images used on the project.
 */
trait ImageBuilder {

  /**
   * Build renderable array for Tmdb image.
   *
   * @param \Drupal\tmdb\enum\TmdbImageFormat $format
   * @param string $tmdb_image_path
   *   Unique Tmdb image name (path) from API.
   * @param string $alt
   *   Image tag "alt" and "title" text.
   *
   * @return array
   *   Renderable array for "img" tag.
   */
  public function buildTmdbImageRenderableArray(TmdbImageFormat $format, string $tmdb_image_path, string $alt): array {
    $uri = Constant::TMDB_IMAGE_BASE_URL . $format->key() . $tmdb_image_path;

    return $this->buildImageRenderableArray($uri, $alt, $format);
  }

  /**
   * Same as $this->buildTmdbImageRenderableArray(), but more flexible.
   *
   * @param string $uri
   *   Any image file path.
   * @param string|null $alt
   *   Image tag "alt" and "title" text if needed.
   * @param \Drupal\tmdb\enum\TmdbImageFormat|null $format
   *
   * @return string[]
   *   Renderable array for "img" tag.
   */
  public function buildImageRenderableArray(string $uri, ?string $alt = '', ?TmdbImageFormat $format = NULL): array {
    $build = [
      '#theme' => 'image',
      '#uri' => $uri,
    ];
    if ($alt) {
      $build += $this->buildTitleAlt($alt);
    }
    if ($format) {
      $build += $this->buildWidthHeight($format);
    }

    return $build;
  }


  /**
   * Build "alt" and "title" renderable array's part for "img" tag.
   *
   * @param string $alt
   *   Image tag "alt" and "title" text.
   *
   * @return string[]
   */
  protected function buildTitleAlt(string $alt): array {
    return [
      '#title' => $alt,
      '#alt' => $alt,
    ];
  }

  /**
   * Build "width" and "height" renderable array's part for "img" tag.
   *
   * @param \Drupal\tmdb\enum\TmdbImageFormat $format
   *
   * @return int[]
   */
  protected function buildWidthHeight(TmdbImageFormat $format): array {
    $width = intval($format->value());
    $height = intval(round($width * 1.5));

    return [
      '#width' => $width,
      '#height' => $height,
    ];
  }

}
