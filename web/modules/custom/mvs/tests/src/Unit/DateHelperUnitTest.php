<?php

namespace Drupal\Tests\mvs\Unit;

use DateTime;
use DateTimeImmutable;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\mvs\DateHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass('Drupal\mvs\DateHelper')]
#[Group('mvs')]
class DateHelperUnitTest extends TestCase {

  private $formatterMock;

  private DateHelper $sut;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formatterMock = $this->createMock(DateFormatter::class);
    $this->sut = new DateHelper($this->formatterMock);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToYear
   */
  #[Group('positive')]
  public function testDateStringToYear(): void {
    $input = '1999-03-12';
    // DateFormatter::format should return year.
    $this->formatterMock->method('format')->willReturn('1999');

    $result = $this->sut->dateStringToYear($input);
    $this->assertEquals('1999', $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToReleaseDateFormat
   */
  #[Group('positive')]
  public function testDateStringToReleaseDateFormat(): void {
    $input = '1999-03-12';
    $format = 'd F Y';

    $timestamp = new DateTimeImmutable($input)->getTimestamp();

    // We expect the formatter to return a string like this.
    $this->formatterMock->expects($this->once())
      ->method('format')
      ->with($timestamp, 'custom', $format)
      ->willReturn('12 March 1999');

    $result = $this->sut->dateStringToReleaseDateFormat($input);
    $this->assertEquals('12 March 1999', $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToTimestamp
   */
  #[Group('positive')]
  public function testDateStringToTimestamp(): void {
    $input = '2020-01-01';
    $expected = new DateTimeImmutable($input)->getTimestamp();

    $result = $this->sut->dateStringToTimestamp($input);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToTimestamp
   */
  #[Group('negative')]
  public function testDateStringToTimestampWithInvalidString(): void {
    $result = $this->sut->dateStringToTimestamp('not-a-date');
    $this->assertNull($result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::getYearsDiff
   */
  #[Group('positive')]
  public function testGetYearsDiff() {
    $this->assertSame(26, $this->sut->getYearsDiff(
      new DateTime('1999-03-12'),
      new DateTime('2025-03-12'))
    );
    $this->assertSame(26, $this->sut->getYearsDiff(
      new DateTimeImmutable('1999-03-12'),
      new DateTimeImmutable('2025-03-12'))
    );
    $this->assertSame(25, $this->sut->getYearsDiff(
      new DateTime('1999-03-12'),
      new DateTime('2025-03-11'))
    );
    $this->assertSame(26, $this->sut->getYearsDiff(
      new DateTime('2025-03-12'),
      new DateTime('1999-03-11'))
    );
    $this->assertSame(0, $this->sut->getYearsDiff(
      new DateTime('1999-03-12'),
      new DateTime('1999-03-11'))
    );
  }

}
