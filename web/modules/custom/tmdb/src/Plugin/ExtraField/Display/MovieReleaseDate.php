<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\DateHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "movie_release_date",
 *   label = @Translation("Extra: Movie release date"),
 *   description = "",
 *   bundles = {"node.movie"}
 * )
 */
class MovieReleaseDate extends ExtraTmdbFieldDisplayBase {

  private ?DateHelper $date_helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->date_helper = $container->get('date_helper');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($release_date = $this->getCommonFieldValue('release_date')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('release date', [], ['context' => 'Field label']),
        '#content' => $this->date_helper->dateStringToReleaseDateFormat($release_date),
      ];
    }

    return $build;
  }

}
