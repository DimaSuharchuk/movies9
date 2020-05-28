<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "noname_club",
 *   label = @Translation("Extra: Noname Club"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class NonameClub extends ExtraTmdbFieldDisplayBase {

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

    if ($this->current_user->isAuthenticated()) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $entity->getTranslation('en');

      $build = [
        '#type' => 'link',
        '#title' => 'torrent',
        '#url' => Url::fromUri(
          '//nnmclub.to/forum/tracker.php',
          [
            'query' => [
              'nm' => $node->getTitle(), // better to search using eng title.
              'o' => 10, // sort by Seeders
              's' => 2, // sorting DESC
              'sha' => 0, // disable Author column
              'shr' => 1, // enable Rating column
            ],
          ]
        ),
        '#attributes' => [
          'class' => [
            'noname-club-icon',
          ],
          'target' => '_blank',
        ],
      ];
    }

    return $build;
  }

}
