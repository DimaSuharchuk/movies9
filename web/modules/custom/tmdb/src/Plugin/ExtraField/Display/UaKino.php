<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\mvs\enum\Language;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "uakino",
 *   label = @Translation("Extra: UA Kino"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
final class UaKino extends ExtraTmdbFieldDisplayBase {

  private ?AccountProxyInterface $current_user;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): UaKino {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->current_user = $container->get('current_user');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if (
      $this->current_user->id() == 1
      || in_array('verified', $this->current_user->getRoles())
    ) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $entity->getTranslation(Language::en->name);

      // https://uakino.best/index.php?do=search&subaction=search&from_page=0&story=bee+movie
      $build = [
        '#type' => 'link',
        '#title' => 'torrent',
        '#url' => Url::fromUri(
          '//uakino.best/index.php',
          [
            'query' => [
              'do' => 'search',
              'story' => preg_replace('/[^a-z\d\s-]/iu', '', $node->getTitle()),
            ],
          ]
        ),
        '#attributes' => [
          'title' => $this->t('Go to uakino', [], ['context' => 'uakino']),
          'class' => [
            'uakino-icon',
          ],
          'target' => '_blank',
        ],
      ];
    }

    return $build;
  }

}
