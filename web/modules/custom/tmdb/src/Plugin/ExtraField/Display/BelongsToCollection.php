<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\Constant;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "belongs_to_collection",
 *   label = @Translation("Extra: Belongs to collection"),
 *   bundles = {"node.movie"},
 *   replaceable = true
 * )
 */
class BelongsToCollection extends ExtraTmdbFieldDisplayBase {

  private ?TmdbTeaser $tmdb_teaser;

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

    if ($collection = $this->getMovieCollection()) {
      $build = [
        '#theme' => 'collection',
        '#title' => $collection['name'],
        '#items' => $this->tmdb_teaser->buildTmdbTeasers(
          $collection['teasers'],
          NodeBundle::movie(),
          Language::memberByValue($entity->language()->getId())
        ),
      ];
      // If collection has a poster.
      if ($poster = $collection['poster_path']) {
        $collection_poster_format = TmdbImageFormat::w400;

        $build['#poster'] = [
          '#theme' => 'image',
          '#uri' => Constant::TMDB_IMAGE_BASE_URL . $collection_poster_format . $poster,
        ];
      }
    }

    return $build;
  }


  /**
   * @see TmdbApiAdapter::getMovieCollection()
   */
  private function getMovieCollection(): ?array {
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::memberByValue($this->entity->language()->getId());

    return $this->adapter->getMovieCollection($tmdb_id, $lang);
  }

}
