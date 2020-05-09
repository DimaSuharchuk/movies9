<?php

namespace Drupal\imdb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\imdb\Constant;
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

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $formats = TmdbImageFormat::getCompactFormats();
    array_walk($formats, function (&$value) {
      // Get number from format values and attach "px" to number.
      $value = $this->numberToPx($value->value());
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
  public function settingsSummary() {
    return [
      $this->t('Rendered with width: %w', [
        '%w' => $this->numberToPx($this->getSetting('width')),
      ]),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    return ['width' => TmdbImageFormat::w200] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $elements[$delta] = [
          '#theme' => 'image',
          '#uri' => Constant::TMDB_IMAGE_BASE_URL . $this->getSetting('width') . $item->value,
          '#langcode' => $langcode,
        ];
      }
    }

    return $elements;
  }


  /**
   * Helper method.
   * Filter string leaving numbers, add 'px'.
   *
   * @param string $s
   *   String to sanitize.
   *
   * @return string
   */
  private function numberToPx(string $s): string {
    return filter_var($s, FILTER_SANITIZE_NUMBER_INT) . 'px';
  }

}
