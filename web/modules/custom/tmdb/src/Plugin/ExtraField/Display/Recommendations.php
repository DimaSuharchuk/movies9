<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbLocalStorageType;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "recommendations",
 *   label = @Translation("Extra: Recommendations"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"},
 *   replaceable = true
 * )
 */
class Recommendations extends ExtraTmdbFieldDisplayBase {

  private ?TmdbTeaser $tmdb_teaser;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->tmdb_teaser = $container->get('tmdb.tmdb_teaser');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($recommendations = $this->getRecommendationsFirstPage()) {
      $build = $this->tmdb_teaser->buildAttachableTmdbTeasersWithWrapper(
        TmdbLocalStorageType::recommendations,
        $entity->id(),
        $recommendations['results'],
        NodeBundle::from($entity->bundle()),
        Language::from($entity->language()->getId()),
        1,
        $recommendations['total_pages'] > 1
      );
    }

    return $build;
  }

  /**
   * @see TmdbApiAdapter::getRecommendations()
   */
  private function getRecommendationsFirstPage(): ?array {
    $bundle = NodeBundle::from($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::from($this->entity->language()->getId());

    return $this->adapter->getRecommendations($bundle, $tmdb_id, $lang, 1);
  }

}
