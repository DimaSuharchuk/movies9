<?php

namespace Drupal\imdb;

use DateTime;
use Drupal\Core\Datetime\DateFormatter;
use Exception;

class DateHelper {

  private DateFormatter $date_formatter;

  public function __construct(DateFormatter $formatter) {
    $this->date_formatter = $formatter;
  }


  /**
   * Get year from date string.
   *
   * @param string|null $s
   *   Date string in any PHP correct format.
   *
   * @return string|null
   *   4-digit year.
   */
  public function dateStringToYear(?string $s): ?string {
    return $s ? $this->dateStringToFormat($s, 'Y') : NULL;
  }

  /**
   * Convert date string to format used in field "Release date" and same.
   *
   * @param string|null $s
   *   Date string in any PHP correct format.
   *
   * @return string|null
   */
  public function dateStringToReleaseDateFormat(?string $s): ?string {
    return $s ? $this->dateStringToFormat($s, 'd F Y') : NULL;
  }

  /**
   * Convert some date string into any PHP date format.
   *
   * @param string|null $s
   *   Date string in any PHP correct format.
   * @param string $format
   *   PHP Date format. https://www.php.net/manual/ru/function.date.php
   *
   * @return string
   *   Converted date.
   */
  public function dateStringToFormat(string $s, string $format): string {
    try {
      return $this->date_formatter->format(
        (new DateTime($s))->getTimestamp(),
        'custom',
        $format
      );
    } catch (Exception $e) {
      return '';
    }
  }

}
