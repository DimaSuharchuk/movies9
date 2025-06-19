<?php

namespace Drupal\Tests\mvs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mvs\DateHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass('Drupal\mvs\DateHelper')]
#[Group('mvs')]
class DateHelperKernelTest extends KernelTestBase {

  /**
   * @var \Drupal\mvs\DateHelper
   */
  private DateHelper $sut;

  protected function setUp(): void {
    parent::setUp();

    $formatter = $this->container->get('date.formatter');
    $this->sut = new DateHelper($formatter);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToYear
   */
  #[Group('positive')]
  public function testDateStringToYear() {
    $result = $this->sut->dateStringToYear('02-12-2008');
    $this->assertSame(2008, $result);

    $result = $this->sut->dateStringToYear('02-12-2008 00:00:00');
    $this->assertSame(2008, $result);

    $result = $this->sut->dateStringToYear('02-12-2008 00:00:00+3:00');
    $this->assertSame(2008, $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToYear
   */
  #[Group('negative')]
  public function testDateStringToYearNegative() {
    $result = $this->sut->dateStringToYear('not-a-date');
    $this->assertNull($result);

    $result = $this->sut->dateStringToYear('');
    $this->assertNull($result);

    $result = $this->sut->dateStringToYear(NULL);
    $this->assertNull($result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToReleaseDateFormat
   */
  #[Group('positive')]
  public function testDateStringToReleaseDateFormat() {
    $result = $this->sut->dateStringToReleaseDateFormat('02-12-2008');
    $this->assertEquals('02 December 2008', $result);

    $result = $this->sut->dateStringToReleaseDateFormat('02-12-2008 00:00:00');
    $this->assertEquals('02 December 2008', $result);

    $result = $this->sut->dateStringToReleaseDateFormat('02-12-2008 00:00:00+3:00');
    $this->assertEquals('02 December 2008', $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToReleaseDateFormat
   */
  #[Group('negative')]
  public function testDateStringToReleaseDateFormatNegative() {
    $result = $this->sut->dateStringToReleaseDateFormat('not-a-date');
    $this->assertEquals('', $result);

    $result = $this->sut->dateStringToReleaseDateFormat(NULL);
    $this->assertNull($result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToFormat
   */
  #[Group('positive')]
  public function testDateStringToFormat() {
    $result = $this->sut->dateStringToFormat('02-12-2008', 'Y');
    $this->assertEquals('2008', $result);

    $result = $this->sut->dateStringToFormat('02-12-2008 00:00:00', 'Y-m-d');
    $this->assertEquals('2008-12-02', $result);

    $result = $this->sut->dateStringToFormat('02-12-2008 00:00:00+3:00', 'U');
    $this->assertSame('1228165200', $result);
  }

  /**
   * @covers \Drupal\mvs\DateHelper::dateStringToFormat
   */
  #[Group('negative')]
  public function testDateStringToFormatNegative() {
    $result = $this->sut->dateStringToFormat('not-a-date', 'U');
    $this->assertEquals('', $result);
  }

}
