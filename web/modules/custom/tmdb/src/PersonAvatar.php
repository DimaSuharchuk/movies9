<?php

namespace Drupal\tmdb;

use Drupal\imdb\Constant;
use Drupal\tmdb\enum\TmdbImageFormat;

trait PersonAvatar {

  /**
   * Wrap with theme "Image" person's avatar or find defined avatar by gender.
   *
   * @param array $person
   *   Array with Person info from TMDb API.
   * @param TmdbImageFormat $format
   *
   * @return array
   *   Renderable array for avatar.
   */
  public function getThemedAvatar(array $person, TmdbImageFormat $format): array {
    if ($person['profile_path']) {
      $uri = Constant::TMDB_IMAGE_BASE_URL . $format->value() . $person['profile_path'];
    }
    else {
      switch ($person['gender']) {
        case Constant::GENDER_MAN:
          $uri = Constant::UNDEFINED_MAN_IMAGE;
          break;

        case Constant::GENDER_WOMAN:
          $uri = Constant::UNDEFINED_WOMAN_IMAGE;
          break;

        default:
          $uri = Constant::UNDEFINED_PERSON_IMAGE;
      }
    }

    return [
      '#theme' => 'image',
      '#uri' => $uri,
      '#title' => $person['name'],
      '#alt' => $person['name'],
    ];
  }

}
