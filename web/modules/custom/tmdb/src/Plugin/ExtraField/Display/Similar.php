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
 *   id = "similar",
 *   label = @Translation("Extra: Similar"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"},
 *   replaceable = true
 * )
 */
class Similar extends ExtraTmdbFieldDisplayBase {

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

    if ($similar = $this->getSimilarFirstPage()) {
      $build = $this->tmdb_teaser->buildAttachableTmdbTeasersWithWrapper(
        TmdbLocalStorageType::similar,
        $entity->id(),
        $similar['results'],
        NodeBundle::from($entity->bundle()),
        Language::from($entity->language()->getId()),
        1,
        $similar['total_pages'] > 1
      );
    }

    return $build;
  }

  /**
   * @see TmdbApiAdapter::getSimilar()
   */
  private function getSimilarFirstPage(): ?array {
    $bundle = NodeBundle::from($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::from($this->entity->language()->getId());

    return $this->adapter->getSimilar($bundle, $tmdb_id, $lang, 1);
  }

}
