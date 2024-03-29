<?php

namespace Drupal\person\Plugin\ExtraField\Display;

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

      if ($person_years_now = $this->date_helper->getYearsDiff($birthday)) {
        $person_years_now_t = $this->formatPlural($person_years_now, '@count year', '@count years', [], ['context' => 'Person years']);
        $content .= " ($person_years_now_t)";
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
