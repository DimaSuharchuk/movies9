<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\enum\NodeBundle;
use Drupal\person\Avatar;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "cast",
 *   label = @Translation("Extra: Cast"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"},
 *   replaceable = true
 * )
 */
class Cast extends ExtraTmdbFieldDisplayBase {

  private ?Avatar $person_avatar;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->person_avatar = $container->get('person.avatar');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($cast = $this->getCast()) {
      $build = [
        '#theme' => 'tmdb_avatars_list',
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
        '#avatar' => $this->person_avatar->build($person, TmdbImageFormat::w185()),
        '#name' => $person['name'],
        '#role' => $person['character'],
        '#photo' => (bool) $person['profile_path'],
      ];
    }

    return $build;
  }

}
