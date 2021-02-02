<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\DateHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "deathday",
 *   label = @Translation("Extra: Deathday"),
 *   description = "",
 *   bundles = {"person.person"}
 * )
 */
class Deathday extends ExtraTmdbFieldDisplayBase {

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

    if ($deathday = $this->getPersonCommonField('deathday')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('deathday', [], ['context' => 'Field label']),
        '#content' => $this->date_helper->dateStringToReleaseDateFormat($deathday),
      ];
    }

    return $build;
  }

}
