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

  /**
   * @var EntityCreator|object|null
   */
  private $creator;

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
      // Update node on English if title == placeholder.
      $node = $node->getTranslation('en');
      if ($node->getTitle() === $this->creator::NODE_TITLE_EMPTY_PLACEHOLDER) {
        $this->adapter->updateMovieOrTvPlaceholderFields($node);
      }
      // Get title from Eng node.
      $build = [
        '#markup' => $node->getTranslation('en')->getTitle(),
      ];
    }

    return $build;
  }

}
