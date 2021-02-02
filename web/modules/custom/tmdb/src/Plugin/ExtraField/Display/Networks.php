<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\NetworksAndCompanies;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "networks",
 *   label = @Translation("Extra: Networks"),
 *   description = "",
 *   bundles = {"node.tv"}
 * )
 */
class Networks extends ExtraTmdbFieldDisplayBase {

  use NetworksAndCompanies;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($networks = $this->getCommonFieldValue('networks')) {
      if ($content = $this->buildItems($networks)) {
        $build = [
          '#theme' => 'field_with_label',
          '#label' => $this->t('networks', [], ['context' => 'Field label']),
          '#content' => $content,
        ];
      }
    }

    return $build;
  }

}
