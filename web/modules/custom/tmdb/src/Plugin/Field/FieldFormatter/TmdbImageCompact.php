<?php

namespace Drupal\tmdb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mvs\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;

/**
 * @FieldFormatter(
 *   id = "tmdb_image_compact",
 *   label = @Translation("Compact"),
 *   field_types = {
 *     "tmdb_image",
 *   }
 * )
 */
class TmdbImageCompact extends FormatterBase {

  use ImageBuilder;

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $formats = TmdbImageFormat::getCompactFormats();
    array_walk($formats, function (&$format) {
      // Get number from format values and attach "px" to number.
      $format = $this->formatToPx($format);
    });

    $elements['width'] = [
      '#type' => 'select',
      '#options' => $formats,
      '#default_value' => $this->getSetting('width'),
    ];

    return $elements;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsSummary(): array {
    $format = TmdbImageFormat::tryFromKey($this->getSetting('width'));

    return [
      $this->t('Rendered with width: %w', [
        '%w' => $this->formatToPx($format),
      ]),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings(): array {
    return ['width' => TmdbImageFormat::w200->name] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $adapter */
    $adapter = $items->getParent();
    $parent_entity = $adapter->getEntity();
    $tmdb_width = $this->getSetting('width');

    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->value) {
        $elements[$delta] = $this->buildTmdbImageRenderableArray(
          TmdbImageFormat::tryFromKey($tmdb_width),
          $item->value,
          $parent_entity->label(),
        );
      }
    }

    return $elements;
  }

  /**
   * Build formatted string that adds "px" to number from Tmdb image format.
   *
   * @param \Drupal\tmdb\enum\TmdbImageFormat $format
   *
   * @return string
   */
  private function formatToPx(TmdbImageFormat $format): string {
    return "{$format->value}px";
  }

}
