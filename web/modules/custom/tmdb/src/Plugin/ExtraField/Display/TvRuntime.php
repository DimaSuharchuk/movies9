<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\mvs\TimeHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\SeasonBuilder;
use Drupal\tmdb\TmdbApiAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "tv_runtime",
 *   label = @Translation("Extra: Tv runtime"),
 *   description = "",
 *   bundles = {"node.tv"}
 * )
 */
class TvRuntime extends ExtraTmdbFieldDisplayBase {

  protected ?TmdbApiAdapter $adapter;

  protected ?SeasonBuilder $seasonBuilder;

  protected ?TimeHelper $timeHelper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->adapter = $container->get('tmdb.adapter');
    $instance->seasonBuilder = $container->get('tmdb.season_builder');
    $instance->timeHelper = $container->get('time_helper');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];
    $runtime = 0;
    $tmdb_id = $entity->{'field_tmdb_id'}->value;
    $lang = Language::en;
    $seasons_count = $this->adapter->getCommonFieldsByTmdbId(NodeBundle::tv, $tmdb_id, $lang)['number_of_seasons'] ?? 0;

    for ($i = 1; $i <= $seasons_count; $i++) {
      $season = $this->adapter->getSeason($tmdb_id, $i, $lang);
      $runtime += $this->seasonBuilder->calculateSeasonRuntime($season['episodes'], TRUE);
    }

    if ($runtime) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('series duration', [], ['context' => 'Field label']),
        '#content' => $this->timeHelper->formatTimeFromMinutes($runtime),
      ];
    }

    return $build;
  }

}
