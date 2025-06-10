<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use DateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\DateHelper;
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
      $content = $this->date_helper->dateStringToReleaseDateFormat($deathday);

      // Calculate person's years.
      $birthday = $this->getPersonCommonField('birthday');
      $date_from = DateTime::createFromFormat('Y-m-d', $birthday);
      $date_to = DateTime::createFromFormat('Y-m-d', $deathday);

      if ($person_years_now = $this->date_helper->getYearsDiff($date_from, $date_to)) {
        $person_years_now_t = $this->formatPlural($person_years_now, '@count year', '@count years', [], ['context' => 'Person years']);
        $content .= " ($person_years_now_t)";
      }

      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('deathday', [], ['context' => 'Field label']),
        '#content' => $content,
      ];
    }

    return $build;
  }

}
