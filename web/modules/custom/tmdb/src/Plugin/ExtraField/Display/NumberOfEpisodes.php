<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "number_of_episodes",
 *   label = @Translation("Extra: Number of episodes"),
 *   description = "",
 *   bundles = {"node.tv"}
 * )
 */
class NumberOfEpisodes extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($num = $this->getCommonFieldValue('number_of_episodes')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('number of episodes', [], ['context' => 'Field label']),
        '#content' => $num,
      ];
    }

    return $build;
  }

}
