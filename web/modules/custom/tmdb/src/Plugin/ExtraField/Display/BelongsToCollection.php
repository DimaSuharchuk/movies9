<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\imdb\Constant;
use Drupal\node\NodeViewBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "belongs_to_collection",
 *   label = @Translation("Extra: Belongs to collection"),
 *   bundles = {"node.movie"}
 * )
 */
class BelongsToCollection extends ExtraTmdbFieldDisplayBase {

  private ?EntityTypeManager $manager;

  private NodeViewBuilder $node_view_builder;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->manager = $container->get('entity_type.manager');
    $instance->node_view_builder = $instance->manager->getViewBuilder('node');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $poster_format = TmdbImageFormat::w400;

    $build = [];

    if ($collection = $this->getMovieCollection()) {
      $build = [
        '#theme' => 'collection',
        '#title' => $collection['collection_info']['name'],
        '#items' => $this->buildResultItems($collection['nodes']),
      ];
      // If collection has a poster.
      if ($poster = $collection['collection_info']['poster_path']) {
        $build['#poster'] = [
          '#theme' => 'image',
          '#uri' => Constant::TMDB_IMAGE_BASE_URL . $poster_format . $poster,
        ];
      }
    }

    return $build;
  }

  /**
   * @param array $nodes
   *   Node movies entities for render.
   *
   * @return array
   *   Prepared movies of some collection to render.
   */
  private function buildResultItems(array $nodes) {
    $build = [];
    foreach ($nodes as $node) {
      $build[] = $this->node_view_builder->view($node, 'teaser');
    }

    return $build;
  }

}
