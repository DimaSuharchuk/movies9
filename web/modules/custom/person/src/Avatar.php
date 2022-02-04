<?php

namespace Drupal\person;

use Drupal\mvs\Constant;
use Drupal\mvs\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;

class Avatar {

  use ImageBuilder;

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
  public function build(array $person, TmdbImageFormat $format): array {
    if (!empty($person['profile_path'])) {
      return $this->buildTmdbImageRenderableArray(
        $format,
        $person['profile_path'],
        $person['name'],
      );
    }

    $uri = $this->getAvatarUriByGender($person['gender'] ?? NULL);
    return $this->buildImageRenderableArray($uri, $person['name'] ?? '', $format);
  }

  /**
   * Returns the default avatar path based on the Person's gender.
   *
   * @param int|null $gender
   *   2 - man
   *   1 - woman
   *   0|null - undefined
   *
   * @return string
   */
  public function getAvatarUriByGender(int $gender = NULL): string {
    switch ($gender) {
      case Constant::GENDER_MAN:
        $uri = Constant::UNDEFINED_MAN_IMAGE;
        break;

      case Constant::GENDER_WOMAN:
        $uri = Constant::UNDEFINED_WOMAN_IMAGE;
        break;

      default:
        $uri = Constant::UNDEFINED_PERSON_IMAGE;
    }

    return $uri;
  }

}
