<?php

namespace Drupal\imdb\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "tmdb_image_textfield",
 *   label = @Translation("Textfield"),
 *   field_types = {
 *     "tmdb_image"
 *   },
 * )
 */
class TmdbImageWidget extends WidgetBase {

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element += [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->value ?: NULL,
      '#size' => 60,
      '#maxlength' => 64,
      '#element_validate' => [
        /**
         * @see TmdbImageWidget::tmdbImageNameValidation()
         */
        [$this, 'tmdbImageNameValidation'],
      ],
    ];

    return ['value' => $element];
  }

  /**
   * Validate image name.
   *
   * @param $element
   *   Image text field form element.
   * @param FormStateInterface $form_state
   */
  public function tmdbImageNameValidation($element, FormStateInterface $form_state) {
    if ($value = $element['#value']) {
      if ($value[0] !== '/') {
        $form_state->setError($element, $this->t('%field value should start from "/".', [
          '%field' => $element['#title'],
        ]));
      }
    }
  }

}
