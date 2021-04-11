<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Annotation\ExtraFieldDisplay;
use Drupal\imdb\Constant;
use Drupal\imdb\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "gallery",
 *   label = @Translation("Extra: Gallery"),
 *   description = "",
 *   bundles = {"person.person"},
 *   replaceable = true
 * )
 */
class Gallery extends ExtraTmdbFieldDisplayBase {

  use ImageBuilder;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($avatars = $this->getPersonCommonField('images')) {
      $build = [
        '#theme' => 'tmdb_items_list',
        '#items' => $this->buildAvatars($avatars, TmdbImageFormat::w185()),
        '#attached' => [
          'library' => [
            'person/avatar-full-popup',
          ],
        ],
      ];
    }

    return $build;
  }


  /**
   * Build array of avatars.
   *
   * @param array $avatars
   *   Raw data from TMDb API.
   * @param TmdbImageFormat $format
   *
   * @return array
   */
  private function buildAvatars(array $avatars, TmdbImageFormat $format): array {
    $build = [];
    foreach ($avatars as $avatar) {
      $build[] = $this->buildThemedAvatar($avatar, $format);
    }
    return $build;
  }

  /**
   * Build renderable array with Person avatar.
   *
   * @param string $path
   *   Avatar file name from TMDb API.
   * @param TmdbImageFormat $format
   *
   * @return array
   */
  private function buildThemedAvatar(string $path, TmdbImageFormat $format): array {
    $build = $this->buildTmdbImageRenderableArray(
      $format,
      $path,
      $this->t('Click to enlarge the image'),
    );

    $big_format = TmdbImageFormat::w780();
    $big_image_uri = Constant::TMDB_IMAGE_BASE_URL . $big_format->key() . $path;
    $build += [
      '#attributes' => [
        'data-full_image' => $big_image_uri,
      ],
    ];

    return $build;
  }

}
