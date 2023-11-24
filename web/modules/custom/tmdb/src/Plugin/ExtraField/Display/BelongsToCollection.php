<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Annotation\ExtraFieldDisplay;
use Drupal\mvs\DateHelper;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\mvs\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "belongs_to_collection",
 *   label = @Translation("Extra: Belongs to collection"),
 *   description = "",
 *   bundles = {"node.movie"},
 *   replaceable = true
 * )
 */
class BelongsToCollection extends ExtraTmdbFieldDisplayBase {

  use ImageBuilder;

  private ?TmdbTeaser $tmdb_teaser;

  private ?DateHelper $date_helper;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->tmdb_teaser = $container->get('tmdb.tmdb_teaser');
    $instance->date_helper = $container->get('date_helper');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($collection = $this->getMovieCollection()) {
      // Sort movies by release date.
      usort($collection['teasers'], function ($a, $b) {
        // If some movie hasn't "release date" (null returned from method), we
        // should move that movie to the end of the collection.
        // Set the biggest "timestamp" for movie sorting.
        $time_a = $this->getReleaseDateTimestampByTmdbId($a['id']) ?? PHP_INT_MAX;
        $time_b = $this->getReleaseDateTimestampByTmdbId($b['id']) ?? PHP_INT_MAX;

        return $time_a <=> $time_b;
      });
      $build = [
        '#theme' => 'collection',
        '#title' => $collection['name'],
        '#items' => $this->tmdb_teaser->buildTmdbTeasers(
          $collection['teasers'],
          NodeBundle::movie,
          Language::from($entity->language()->getId())
        ),
      ];
      // If a collection has a poster.
      if ($poster = $collection['poster_path']) {
        $build['#poster'] = $this->buildTmdbImageRenderableArray(
          TmdbImageFormat::w400,
          $poster,
          $collection['name'],
        );
      }
    }

    return $build;
  }

  /**
   * @see TmdbApiAdapter::getMovieCollection()
   */
  private function getMovieCollection(): ?array {
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::from($this->entity->language()->getId());

    return $this->adapter->getMovieCollection($tmdb_id, $lang);
  }

  /**
   * Return Movie's release date converted to timestamp if the date exists.
   *
   * @param int $tmdb_id
   *
   * @return int|null
   */
  private function getReleaseDateTimestampByTmdbId(int $tmdb_id): ?int {
    if ($common = $this->adapter->getCommonFieldsByTmdbId(NodeBundle::movie, $tmdb_id, Language::en)) {
      return ($date_string = $common['release_date'] ?? FALSE) ? $this->date_helper->dateStringToTimestamp($date_string) : NULL;
    }

    return NULL;
  }

}
