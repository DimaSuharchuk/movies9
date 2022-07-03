<?php

namespace Drupal\mvs\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Contains global controllers.
 */
class MvsController extends ControllerBase {

  /**
   * Content of page of 403 error.
   *
   * @return array
   *   Renderable array.
   */
  public function page403(): array {
    return [
      '#theme' => 'mvs_error_page',
      '#error_code' => 403,
    ];
  }

  /**
   * Content of page of 404 error.
   *
   * @return array
   *   Renderable array.
   */
  public function page404(): array {
    return [
      '#theme' => 'mvs_error_page',
      '#error_code' => 404,
    ];
  }

}
