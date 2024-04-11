<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use DateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\DateHelper;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "birthday",
 *   label = @Translation("Extra: Birthday"),
 *   description = "",
 *   bundles = {"person.person"}
 * )
 */
class Birthday extends ExtraTmdbFieldDisplayBase {

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

    if ($birthday = $this->getPersonCommonField('birthday')) {
      $content = $this->date_helper->dateStringToReleaseDateFormat($birthday);

      // We count how old the person is.
      // But if the person dies, the number of years is displayed opposite
      // the date of death.
      if (!$this->getPersonCommonField('deathday')) {
        $date_from = DateTime::createFromFormat('Y-m-d', $birthday);

        if ($person_years_now = $this->date_helper->getYearsDiff($date_from, new DateTime())) {
          $person_years_now_t = $this->formatPlural($person_years_now, '@count year', '@count years', [], ['context' => 'Person years']);
          $content .= " ($person_years_now_t)";
        }
      }

      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('birthday', [], ['context' => 'Field label']),
        '#content' => $content,
      ];
    }

    return $build;
  }

}
