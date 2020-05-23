<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\EntityCreator;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "original_title",
 *   label = @Translation("Extra: Original title"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class OriginalTitle extends ExtraTmdbFieldDisplayBase {

  private ?EntityCreator $creator;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->creator = $container->get('entity_creator');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $node): array {
    $build = [];

    // Show original title only for non-english content.
    /** @var \Drupal\node\Entity\Node $node */
    if ($node->language()->getId() !== 'en') {
      // Get title from Eng node.
      $node = $node->getTranslation('en');
      $build = [
        '#markup' => $node->getTitle(),
      ];
    }

    return $build;
  }

}
