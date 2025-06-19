<?php

namespace Drupal\Tests\mvs\Unit;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\mvs\TimeHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass('Drupal\mvs\TimeHelper')]
#[Group('mvs')]
class TimeHelperUnitTest extends TestCase {

  private $translationManagerMock;

  /**
   * @var \Drupal\mvs\TimeHelper
   */
  private TimeHelper $sut;

  protected function setUp(): void {
    parent::setUp();

    $this->translationManagerMock = $this->createMock(TranslationManager::class);
    $this->sut = new TimeHelper($this->translationManagerMock);
  }

  /**
   * @covers \Drupal\mvs\TimeHelper::separateMinutes
   */
  #[Group('positive')]
  public function testSeparateMinutes() {
    $result = $this->sut->separateMinutes(0);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(0, $result['h']);
    $this->assertSame(0, $result['m']);

    $result = $this->sut->separateMinutes(1);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(0, $result['h']);
    $this->assertSame(1, $result['m']);

    $result = $this->sut->separateMinutes(59);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(0, $result['h']);
    $this->assertSame(59, $result['m']);

    $result = $this->sut->separateMinutes(60);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(1, $result['h']);
    $this->assertSame(0, $result['m']);

    $result = $this->sut->separateMinutes(61);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(1, $result['h']);
    $this->assertSame(1, $result['m']);

    $result = $this->sut->separateMinutes(120);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(2, $result['h']);
    $this->assertSame(0, $result['m']);

    $result = $this->sut->separateMinutes(121);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('h', $result);
    $this->assertArrayHasKey('m', $result);
    $this->assertSame(2, $result['h']);
    $this->assertSame(1, $result['m']);
  }

}
