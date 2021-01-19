<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\PersonAvatar;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "cast",
 *   label = @Translation("Extra: Cast"),
 *   bundles = {"node.movie", "node.tv"},
 *   replaceable = true
 * )
 */
class Cast extends ExtraTmdbFieldDisplayBase {

  use PersonAvatar;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($cast = $this->getCast()) {
      $build = [
        '#theme' => 'tmdb_items_list',
        '#items' => $this->buildItems($cast),
      ];
    }

    return $build;
  }


  /**
   * @see TmdbApiAdapter::getCast()
   */
  private function getCast(): array {
    $bundle = NodeBundle::memberByValue($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;

    return $this->adapter->getCast($bundle, $tmdb_id);
  }

  /**
   * @param array $persons
   *   Persons from TMDb API for render.
   *
   * @return array
   *   Renderable arrays of cast persons.
   */
  private function buildItems(array $persons): array {
    $build = [];

    foreach ($persons as $person) {
      $build[] = [
        '#theme' => 'person_teaser',
        '#tmdb_id' => $person['id'],
        '#avatar' => $this->getThemedAvatar($person, TmdbImageFormat::w185()),
        '#name' => $person['name'],
        '#role' => $person['character'],
        '#photo' => (bool) $person['profile_path'],
      ];
    }

    return $build;
  }

}
