<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "runtime",
 *   label = @Translation("Extra: Runtime"),
 *   bundles = {"node.movie"}
 * )
 */
class Runtime extends ExtraTmdbFieldDisplayBase {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($runtime = $this->getFieldValue('runtime')) {
      $output = '';
      if ($runtime > 59) {
        $hours = intdiv($runtime, 60);
        $output = $this->formatPlural($hours, '1 hour', '@count hours') . ' ';
      }
      $output .= $this->formatPlural($runtime % 60, '1 minute', '@count minutes');

      $build = ['#markup' => $output];
    }

    return $build;
  }

}
