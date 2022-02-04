<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\ImdbRating as ImdbRatingService;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "imdb_rating",
 *   label = @Translation("Extra: Imdb rating"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class ImdbRating extends ExtraTmdbFieldDisplayBase {

  /**
   * @var ImdbRatingService|object|null
   */
  private $rating;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->rating = $container->get('imdb.rating');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    $imdb_id = $entity->{'field_imdb_id'}->value;
    if ($rating = $this->rating->getRatingValue($imdb_id)) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => 'imdb',
        '#content' => $rating,
      ];
    }

    return $build;
  }

}
