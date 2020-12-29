<?php

namespace Drupal\imdb;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class TimeHelper {

  use StringTranslationTrait;


  /**
   * Simply connect the two methods work for easier using in client code.
   *
   * @param int $minutes
   *
   * @return string
   *
   * @see TimeHelper::separateMinutes()
   * @see TimeHelper::formatTime()
   */
  public function formatTimeFromMinutes(int $minutes): string {
    $x = $this->separateMinutes($minutes);
    return $this->formatTime($x['h'], $x['m']);
  }

  /**
   * Returns formatted translatable string with hours and minutes.
   *
   * @param int $hours
   * @param int $minutes
   *
   * @return string
   */
  public function formatTime(int $hours, int $minutes): string {
    $output = '';

    if ($hours > 0) {
      $output = $this->formatPlural($hours, '1 hour', '@count hours') . ' ';
    }
    if ($minutes > 0) {
      $output .= $this->formatPlural($minutes, '1 minute', '@count minutes');
    }

    return $output;
  }

  /**
   * Extract the number of hours from minutes also calculates the remaining
   * minutes.
   *
   * @param int $minutes
   *
   * @return array
   */
  public function separateMinutes(int $minutes): array {
    return [
      'h' => intdiv($minutes, 60),
      'm' => $minutes % 60,
    ];
  }

}
