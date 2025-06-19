<?php

namespace Drupal\mvs;

use DateTime;
use DateTimeInterface;
use Drupal\Core\Datetime\DateFormatter;
use Exception;

readonly class DateHelper {

  public function __construct(
    private DateFormatter $formatter,
  ) {
  }

  /**
   * Get year from date string.
   *
   * @param string|null $s
   *   Date string in any PHP correct format.
   *
   * @return int|null
   *   4-digit year.
   *
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToYear()
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToYearNegative()
   */
  public function dateStringToYear(?string $s): ?int {
    if (!$s) {
      return NULL;
    }

    return $this->dateStringToFormat($s, 'Y') ?: NULL;
  }

  /**
   * Convert date string to format used in field "Release date" and same.
   *
   * @param string|null $s
   *   Date string in any PHP correct format.
   *
   * @return string|null
   *
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToReleaseDateFormat()
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToReleaseDateFormatNegative()
   */
  public function dateStringToReleaseDateFormat(?string $s): ?string {
    return $s ? $this->dateStringToFormat($s, 'd F Y') : NULL;
  }

  /**
   * Convert some date string into any PHP date format.
   *
   * @param string $s
   *   Date string in any PHP correct format.
   * @param string $format
   *   PHP Date format. https://www.php.net/manual/ru/function.date.php
   *
   * @return string
   *   Converted date.
   *
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToFormat()
   * @see \Drupal\Tests\mvs\Kernel\DateHelperKernelTest::testDateStringToFormatNegative()
   */
  public function dateStringToFormat(string $s, string $format): string {
    $timestamp = $this->dateStringToTimestamp($s);

    return $timestamp ? $this->formatter->format($timestamp, 'custom', $format) : '';
  }

  /**
   * Convert some date string into UNIX timestamp.
   *
   * @param string $s
   *   Date string in any PHP correct format.
   *
   * @return int|null
   *   Converted date.
   *
   * @see \Drupal\Tests\mvs\Unit\DateHelperUnitTest::testDateStringToTimestamp()
   * @see \Drupal\Tests\mvs\Unit\DateHelperUnitTest::testDateStringToTimestampWithInvalidString()
   */
  public function dateStringToTimestamp(string $s): ?int {
    try {
      return new DateTime($s)->getTimestamp();
    }
    catch (Exception) {
      return NULL;
    }
  }

  /**
   * Calculates number of years between the dates.
   *
   * @param DateTimeInterface $date_from
   *   Represents the initial point from which the difference is calculated.
   * @param DateTimeInterface $date_to
   *   Represents the point up to which the difference is calculated.
   *
   * @return int|null
   *   Number of years if argument is the correct date.
   *
   * @see \Drupal\Tests\mvs\Unit\DateHelperUnitTest::testGetYearsDiff()
   */
  public function getYearsDiff(DateTimeInterface $date_from, DateTimeInterface $date_to): ?int {
    return $date_to->diff($date_from)->y;
  }

}
