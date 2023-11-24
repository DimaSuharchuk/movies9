<?php

namespace Drupal\tmdb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\mvs\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;

/**
 * @FieldFormatter(
 *   id = "tmdb_image_original",
 *   label = @Translation("Original"),
 *   field_types = {
 *     "tmdb_image",
 *   }
 * )
 */
class TmdbImageOriginal extends FormatterBase {

  use ImageBuilder;

  /**
   * {@inheritDoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Rendered <em>Original</em> image.'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $adapter */
    $adapter = $items->getParent();
    $parent_entity = $adapter->getEntity();

    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $elements[$delta] = $this->buildTmdbImageRenderableArray(
          TmdbImageFormat::original,
          $item->value,
          $parent_entity->label(),
        );
      }
    }

    return $elements;
  }

}
