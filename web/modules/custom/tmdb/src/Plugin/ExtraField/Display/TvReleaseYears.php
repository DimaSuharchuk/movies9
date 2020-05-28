<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\DateHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "tv_release_years",
 *   label = @Translation("Extra: Tv release years"),
 *   bundles = {"node.tv"}
 * )
 */
class TvReleaseYears extends ExtraTmdbFieldDisplayBase {

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

    if ($start_date = $this->getCommonFieldValue('first_air_date')) {
      // Prepare start and end years.
      $start_year = $this->date_helper->dateStringToYear($start_date);

      $end_year = '';
      $in_production = $this->getCommonFieldValue('in_production');
      if ($in_production === FALSE) {
        $end_date = $this->getCommonFieldValue('last_air_date');
        $end_year = $this->date_helper->dateStringToYear($end_date);
      }

      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('release years', [], ['context' => 'Field label']),
        '#content' => "{$start_year}–{$end_year}",
      ];
    }

    return $build;
  }

}
