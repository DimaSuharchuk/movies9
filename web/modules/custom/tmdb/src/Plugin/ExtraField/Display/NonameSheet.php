<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "noname_sheet",
 *   label = @Translation("Extra: Noname Sheet"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class NonameSheet extends ExtraTmdbFieldDisplayBase {

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

    if ($this->current_user->hasPermission('view nnm')) {
      $build = [
        '#type' => 'link',
        '#title' => 'Torrent sheet',
        '#url' => Url::fromRoute('tmdb.nnm-sheet', ['nid' => $entity->id()]),
        '#options' => [
          'attributes' => [
            'title' => $this->t('Show torrents info', [], ['context' => 'nnm']),
            'class' => ['use-ajax', 'noname-sheet-icon'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '80%',
              'dialogClass' => 'nnm-sheet-modal',
            ]),
          ],
        ],
        '#attached' => ['library' => ['core/drupal.dialog.ajax']],
      ];
    }

    return $build;
  }

}
