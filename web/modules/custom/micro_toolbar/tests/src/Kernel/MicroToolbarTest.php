<?php

namespace Drupal\Tests\micro_toolbar\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Form\FormState;
use Drupal\micro_toolbar\Form\SettingsForm;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\micro_toolbar\Hook\MicroToolbarHooks;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests toolbar visibility, menu access, rendering, and configuration.
 */
#[Group('micro_toolbar')]
class MicroToolbarTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'micro_toolbar', 'micro_toolbar_test'];

  /**
   * Tests that even a permission grant cannot expose the toolbar to anonymous.
   */
  public function testAnonymousAccess(): void {
    $this->setAccount(FALSE, TRUE);
    $page_top = [];
    $this->container->get('module_handler')->invoke('micro_toolbar', 'page_top', [&$page_top]);
    $this->assertFalse($page_top['micro_toolbar']['#access']->isAllowed());
  }

  /**
   * Tests that anonymous requests never resolve services or check permissions.
   */
  public function testAnonymousFastPath(): void {
    $account = $this->createMock(AccountProxyInterface::class);
    $account->expects($this->once())->method('isAnonymous')->willReturn(TRUE);
    $account->expects($this->never())->method('hasPermission');
    $unused_service = function () {
      $this->fail('Anonymous requests must not resolve toolbar services.');
    };
    $hooks = new MicroToolbarHooks($account, $unused_service, $unused_service, $unused_service);
    $page_top = [];
    $hooks->pageTop($page_top);
    $access = $page_top['micro_toolbar']['#access'];
    $this->assertTrue($access->isForbidden());
    $this->assertContains('user.roles:authenticated', $access->getCacheContexts());
    $this->assertArrayNotHasKey('#attached', $page_top['micro_toolbar']);
  }

  /**
   * Tests authenticated users without the toolbar permission.
   */
  public function testPermissionRequired(): void {
    $this->setAccount(TRUE, FALSE);
    $page_top = [];
    $this->container->get('module_handler')->invoke('micro_toolbar', 'page_top', [&$page_top]);
    $this->assertFalse($page_top['micro_toolbar']['#access']->isAllowed());
  }

  /**
   * Tests actual menu rendering and configuration cache invalidation metadata.
   */
  public function testToolbarRendering(): void {
    $this->installConfig(['system']);
    $this->container->get('router.builder')->rebuild();
    $this->container->get('plugin.manager.menu.link')->rebuild();
    $this->setAccount(TRUE, TRUE);
    foreach (['icons_and_labels', 'icons_only'] as $mode) {
      $this->container->get('state')->set('micro_toolbar.display_mode', $mode);
      $page_top = [];
      $this->container->get('module_handler')->invoke('micro_toolbar', 'page_top', [&$page_top]);
      $build = $page_top['micro_toolbar'];
      $this->assertTrue($build['#access']->isAllowed());
      $this->assertArrayHasKey('system.admin_config', $build['#items']);
      $this->assertContains('micro_toolbar:settings', $build['#cache']['tags']);
      $html = (string) $this->container->get('renderer')->renderInIsolation($build);
      $this->assertStringContainsString('<svg', $html);
      $this->assertStringContainsString('title="Configuration"', $html);
      $this->assertStringContainsString('Log out', $html);
      $this->assertStringContainsString('token=', $html);
      $this->assertSame($mode === 'icons_only', str_contains($html, 'micro-toolbar--icons-only'));
    }
  }

  /**
   * Tests that toolbar access does not bypass individual menu permissions.
   */
  public function testMenuAccess(): void {
    $this->installConfig(['system']);
    $this->container->get('router.builder')->rebuild();
    $this->container->get('plugin.manager.menu.link')->rebuild();
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);
    $account->method('isAuthenticated')->willReturn(TRUE);
    $account->method('hasPermission')->willReturnCallback(
      static fn (string $permission): bool => $permission === 'access micro toolbar',
    );
    $this->container->get('current_user')->setAccount($account);
    $page_top = [];
    $this->container->get('module_handler')->invoke('micro_toolbar', 'page_top', [&$page_top]);
    $build = $page_top['micro_toolbar'];
    $this->assertTrue($build['#access']->isAllowed());
    $this->assertArrayNotHasKey('system.admin_config', $build['#items'] ?? []);
    $this->assertContains('user.permissions', $build['#cache']['contexts']);
  }

  /**
   * Tests discovered menu icons, missing icons, and render metadata bubbling.
   */
  public function testIconIntegration(): void {
    $this->installConfig(['system']);
    $this->container->get('router.builder')->rebuild();
    $this->container->get('plugin.manager.menu.link')->rebuild();
    $this->setAccount(TRUE, TRUE);
    $page_top = [];
    $this->container->get('module_handler')->invoke('micro_toolbar', 'page_top', [&$page_top]);
    $build = $page_top['micro_toolbar'];
    $this->assertSame('icon', $build['#items']['micro_toolbar_test.custom']['icon']['#type']);
    $this->assertSame([], $build['#items']['micro_toolbar_test.missing']['icon']);
    $context = new RenderContext();
    $renderer = $this->container->get('renderer');
    $html = (string) $renderer->executeInRenderContext($context, function () use ($renderer, &$build) {
      return $renderer->render($build);
    });
    $this->assertStringContainsString('data-test-icon="custom"', $html);
    $this->assertStringNotContainsString('&lt;svg', $html);
    $this->assertContains('micro_toolbar_test/icon', $context->pop()->getAttachments()['library']);
  }

  /**
   * Tests form persistence and cache invalidation.
   */
  public function testStateSettings(): void {
    $state = $this->container->get('state');
    $form = SettingsForm::create($this->container);
    $form_state = new FormState();
    $build = $form->buildForm([], $form_state);
    $this->assertSame('icons_and_labels', $build['display_mode']['#default_value']);
    $cache = $this->container->get('cache.render');
    $cache->set('micro_toolbar_test', 'old toolbar', -1, ['micro_toolbar:settings']);
    $this->assertNotFalse($cache->get('micro_toolbar_test'));
    $form_state->setValue('display_mode', 'icons_only');
    $form->submitForm($build, $form_state);
    $this->assertSame('icons_only', $state->get('micro_toolbar.display_mode'));
    $this->assertFalse($cache->get('micro_toolbar_test'));
    $build = $form->buildForm([], new FormState());
    $this->assertSame('icons_only', $build['display_mode']['#default_value']);
  }

  /**
   * Sets an account with controlled authentication and permissions.
   */
  private function setAccount(bool $authenticated, bool $allowed): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn($authenticated ? 2 : 0);
    $account->method('isAuthenticated')->willReturn($authenticated);
    $account->method('isAnonymous')->willReturn(!$authenticated);
    $account->method('getRoles')->willReturn([$authenticated ? 'authenticated' : 'anonymous']);
    $account->method('hasPermission')->willReturn($allowed);
    $this->container->get('current_user')->setAccount($account);
  }

}
