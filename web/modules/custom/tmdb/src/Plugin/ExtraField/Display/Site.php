<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "site",
 *   label = @Translation("Extra: Site"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Site extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($site = $this->getCommonFieldValue('site')) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('site', [], ['context' => 'Field label']),
        '#content' => [
          '#type' => 'link',
          '#title' => parse_url($site, PHP_URL_HOST),
          '#url' => Url::fromUri($site),
          '#attributes' => [
            'target' => ['_blank'],
          ],
        ],
      ];
    }

    return $build;
  }

}
