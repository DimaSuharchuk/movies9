<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\PersonAvatar;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "cast",
 *   label = @Translation("Extra: Cast"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Cast extends ExtraTmdbFieldDisplayBase {

  use PersonAvatar;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($collection = $this->getFieldValue('cast')) {
      $build = [
        '#theme' => 'collection',
        '#items' => $this->buildItems($collection['cast']),
      ];
    }

    return $build;
  }

  /**
   * @param array $persons
   *   Persons from TMDb API for render.
   *
   * @return array
   *   Renderable arrays of cast persons.
   */
  private function buildItems(array $persons) {
    $build = [];

    foreach ($persons as $person) {
      $build[] = [
        '#theme' => 'person',
        '#avatar' => $this->getThemedAvatar($person, TmdbImageFormat::w185()),
        '#name' => $person['name'],
        '#role' => $person['character'],
      ];
    }

    return $build;
  }

}
