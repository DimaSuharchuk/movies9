<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
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
    $lang = Language::from($entity->language()->getId());
    $is_eng = $lang == Language::en;
    $eng_videos = $this->getVideos(Language::en);
    $videos = !$is_eng ? $this->getVideos($lang) : [];
    $videos = array_merge($videos, $eng_videos);

    if ($videos) {
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
    $bundle = NodeBundle::from($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;

    $videos = $this->adapter->getVideos($bundle, $tmdb_id, $lang);
    // Sort videos by size.
    $this->sortBySize($videos);

    return $videos;
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

    foreach ($videos as $video) {
      $build[] = [
        '#theme' => 'video',
        '#name' => $video['name'],
        '#size' => $video['size'],
        '#key' => $video['key'],
        '#language' => $lang->name,
      ];
    }

    return $build;
  }

  /**
   * Sort videos by size: videos with better quality go to the top.
   *
   * @param array $videos
   *   Videos from TMDb API.
   */
  private function sortBySize(array &$videos): void {
    usort($videos, function ($a, $b) {
      // Better videos up.
      return $b['size'] <=> $a['size'];
    });
  }

}
