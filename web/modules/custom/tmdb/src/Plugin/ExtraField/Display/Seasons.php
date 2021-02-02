<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\enum\Language;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\SeasonBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "seasons",
 *   label = @Translation("Extra: Seasons"),
 *   description = "",
 *   bundles = {"node.tv"},
 *   replaceable = true
 * )
 */
class Seasons extends ExtraTmdbFieldDisplayBase {

  private ?SeasonBuilder $season_builder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->season_builder = $container->get('tmdb.season_builder');

    return $instance;
  }


  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $entity;
    // Build first season by default.
    return $this->season_builder
      ->buildSeason(
        $node,
        1,
        Language::memberByValue($node->language()->getId())
      );
  }

}
