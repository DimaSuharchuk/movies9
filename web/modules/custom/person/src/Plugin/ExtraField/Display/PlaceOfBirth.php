<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "place_of_birth",
 *   label = @Translation("Extra: Place of birth"),
 *   bundles = {"person.person"}
 * )
 */
class PlaceOfBirth extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($value = $this->getPersonCommonField('place_of_birth')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('place of birth', [], ['context' => 'Field label']),
        '#content' => $value,
      ];
    }

    return $build;
  }

}
