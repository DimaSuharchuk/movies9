<?php

namespace Drupal\imdb\Element;

class View extends \Drupal\views\Element\View {

  /**
   * {@inheritDoc}
   */
  public static function preRenderViewElement($element) {
    $element = parent::preRenderViewElement($element);
    // Remove useless container.
    unset($element['#theme_wrappers']);

    return $element;
  }

}
