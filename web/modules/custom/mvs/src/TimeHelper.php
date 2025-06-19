<?php

namespace Drupal\mvs;

use Drupal\Core\StringTranslation\TranslationManager;

readonly class TimeHelper {

  public function __construct(
    protected TranslationManager $translationManager,
  ) {
  }

  /**
   * Connect the two methods work for easier using in client code.
   *
   * @param int $minutes
   *
   * @return string
   *
   * @see TimeHelperKernelTest::testFormatTimeFromMinutes()
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
   *
   * @see TimeHelperKernelTest::testFormatTime()
   * @internal
   */
  public function formatTime(int $hours, int $minutes): string {
    $output = [];

    if ($hours > 0) {
      $output[] = $this->translationManager->formatPlural($hours, '1 hour', '@count hours');
    }
    if ($minutes > 0) {
      $output[] = $this->translationManager->formatPlural($minutes, '1 minute', '@count minutes');
    }

    return implode(' ', $output);
  }

  /**
   * Extract the number of hours from minutes also calculates the remaining
   * minutes.
   *
   * @param int $minutes
   *
   * @return array
   *
   * @see \Drupal\Tests\mvs\Unit\TimeHelperTest::testSeparateMinutes()
   * @internal
   */
  public function separateMinutes(int $minutes): array {
    return [
      'h' => intdiv($minutes, 60),
      'm' => $minutes % 60,
    ];
  }

}
