<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "copy_movie_admin_info",
 *   label = @Translation("Extra: Copy movie admin info"),
 *   description = "Copy TMDb ID, original title and year(s).",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class CopyMovieAdminInfo extends ExtraTmdbFieldDisplayBase {

  private ?AccountProxyInterface $current_user;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->current_user = $container->get('current_user');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($this->current_user->hasPermission('administer site configuration')) {
      $build = [
        '#type' => 'button',
        '#options' => [
          'attributes' => [
            'title' => $this->t('Copy movie info for Excel'),
            'class' => ['copy-movie-admin-info-icon'],
          ],
        ],
        '#attached' => ['library' => ['tmdb/copy_movie_admin_info']],
      ];
    }

    return $build;
  }

}
