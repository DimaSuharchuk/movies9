<?php

namespace Drupal\Tests\mvs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mvs\TimeHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass('TimeHelper')]
#[Group('mvs')]
class TimeHelperKernelTest extends KernelTestBase {

  protected TimeHelper $sut;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->sut = new TimeHelper($this->container->get('string_translation'));
  }

  /**
   * @covers \Drupal\mvs\TimeHelper::formatTimeFromMinutes
   */
  #[Group('positive')]
  public function testFormatTimeFromMinutes(): void {
    // Depending on locale, the result might differ.
    // We're in English by default, so check exact output.
    $result = $this->sut->formatTimeFromMinutes(0);
    $this->assertEquals('', $result);

    $result = $this->sut->formatTimeFromMinutes(1);
    $this->assertEquals('1 minute', $result);

    $result = $this->sut->formatTimeFromMinutes(2);
    $this->assertEquals('2 minutes', $result);

    $result = $this->sut->formatTimeFromMinutes(60);
    $this->assertEquals('1 hour', $result);

    $result = $this->sut->formatTimeFromMinutes(61);
    $this->assertEquals('1 hour 1 minute', $result);

    $result = $this->sut->formatTimeFromMinutes(90);
    $this->assertEquals('1 hour 30 minutes', $result);

    $result = $this->sut->formatTimeFromMinutes(120);
    $this->assertEquals('2 hours', $result);

    $result = $this->sut->formatTimeFromMinutes(121);
    $this->assertEquals('2 hours 1 minute', $result);

    $result = $this->sut->formatTimeFromMinutes(122);
    $this->assertEquals('2 hours 2 minutes', $result);
  }

  /**
   * @covers \Drupal\mvs\TimeHelper::formatTime
   */
  #[Group('positive')]
  public function testFormatTime(): void {
    // Depending on locale, the result might differ.
    // We're in English by default, so check exact output.
    $result = $this->sut->formatTime(0, 0);
    $this->assertEquals('', $result);

    $result = $this->sut->formatTime(0, 1);
    $this->assertEquals('1 minute', $result);

    $result = $this->sut->formatTime(0, 2);
    $this->assertEquals('2 minutes', $result);

    $result = $this->sut->formatTime(0, 90);
    $this->assertEquals('90 minutes', $result);

    $result = $this->sut->formatTime(1, 0);
    $this->assertEquals('1 hour', $result);

    $result = $this->sut->formatTime(1, 1);
    $this->assertEquals('1 hour 1 minute', $result);

    $result = $this->sut->formatTime(1, 30);
    $this->assertEquals('1 hour 30 minutes', $result);

    $result = $this->sut->formatTime(2, 0);
    $this->assertEquals('2 hours', $result);

    $result = $this->sut->formatTime(2, 1);
    $this->assertEquals('2 hours 1 minute', $result);

    $result = $this->sut->formatTime(2, 2);
    $this->assertEquals('2 hours 2 minutes', $result);
  }

}
