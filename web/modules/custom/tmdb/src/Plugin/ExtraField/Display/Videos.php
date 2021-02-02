<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "videos",
 *   label = @Translation("Extra: Videos"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"},
 *   replaceable = true
 * )
 */
class Videos extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $lang = Language::memberByValue($entity->language()->getId());

    if ($videos = $this->getVideos($lang)) {
      $build = [
        '#theme' => 'videos',
        '#items' => $this->buildItems($videos, $lang),
      ];
    }
    else {
      $build = [
        '#theme' => 'container_wrapper',
        '#content' => [
          '#markup' => $this->t('Unfortunately, there are no trailers.'),
        ],
      ];
    }

    return $build;
  }


  /**
   * @param Language $lang
   *
   * @return array
   *
   * @see TmdbApiAdapter::getVideos()
   */
  private function getVideos(Language $lang): array {
    $bundle = NodeBundle::memberByValue($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;

    return $this->adapter->getVideos($bundle, $tmdb_id, $lang);
  }

  /**
   * @param array $videos
   *   Videos from TMDb API for render.
   * @param Language $lang
   *
   * @return array
   */
  private function buildItems(array $videos, Language $lang): array {
    $build = [];

    // Sort videos by size.
    $this->sortBySize($videos);

    foreach ($videos as $video) {
      $build[] = [
        '#theme' => 'video',
        '#name' => $video['name'],
        '#size' => $video['size'],
        '#key' => $video['key'],
        '#language' => $lang->value(),
      ];
    }

    return $build;
  }

  /**
   * Sort videos by size: videos with better quality goes to top.
   *
   * @param array $videos
   *   Videos from TMDb API.
   */
  private function sortBySize(array &$videos): void {
    usort($videos, function ($a, $b) {
      // Better videos above.
      return $b['size'] <=> $a['size'];
    });
  }

}
