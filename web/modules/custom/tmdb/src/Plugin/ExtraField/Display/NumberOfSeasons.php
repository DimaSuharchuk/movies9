<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "number_of_seasons",
 *   label = @Translation("Extra: Number of seasons"),
 *   bundles = {"node.tv"}
 * )
 */
class NumberOfSeasons extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($num = $this->getCommonFieldValue('number_of_seasons')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('number of seasons', [], ['context' => 'Field label']),
        '#content' => $num,
      ];
    }

    return $build;
  }

}
