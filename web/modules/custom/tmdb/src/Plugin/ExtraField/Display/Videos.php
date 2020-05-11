<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\imdb\enum\Language;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "videos",
 *   label = @Translation("Extra: Videos"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Videos extends ExtraTmdbFieldDisplayBase {

  /**
   * @var LoggerChannelInterface|object|null
   */
  private $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->logger = $container->get('logger.factory')->get('Extra: Videos');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($videos = $this->getFieldValue('videos')['results']) {
      $lang = Language::memberByValue($entity->language()->getId());
      $build = [
        '#theme' => 'collection',
        '#items' => $this->buildItems($videos, $lang),
      ];
    }

    return $build;
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
      if ($video['site'] === 'YouTube') {
        $build[] = [
          '#theme' => 'video',
          '#name' => $video['name'],
          '#size' => $video['size'],
          '#key' => $video['key'],
          '#language' => $lang->value(),
        ];
      }
      else {
        // Write logs if the video is not from YouTube.
        $this->logger->warning(
          printf('Video name: "%s", site: "%s", key: "%s".', $video['name'], $video['site'], $video['key'])
        );
      }
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
