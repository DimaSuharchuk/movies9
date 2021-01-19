<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "known_for_department",
 *   label = @Translation("Extra: Known for department"),
 *   bundles = {"person.person"}
 * )
 */
class KnownForDepartment extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($value = $this->getPersonCommonField('known_for_department')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('known for department', [], ['context' => 'Field label']),
        '#content' => $this->t($value, [], ['context' => 'known for']),
      ];
    }

    return $build;
  }

}
