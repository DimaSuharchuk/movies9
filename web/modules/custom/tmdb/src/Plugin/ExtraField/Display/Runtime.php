<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\TimeHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "runtime",
 *   label = @Translation("Extra: Runtime"),
 *   description = "",
 *   bundles = {"node.movie"}
 * )
 */
class Runtime extends ExtraTmdbFieldDisplayBase {

  private ?TimeHelper $time_helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->time_helper = $container->get('time_helper');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($runtime = $this->getCommonFieldValue('runtime')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('duration', [], ['context' => 'Field label']),
        '#content' => $this->time_helper->formatTimeFromMinutes($runtime),
      ];
    }

    return $build;
  }

}
