<?php

namespace Drupal\micro_toolbar\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Provides the administration toolbar and its theme definition.
 */
final readonly class MicroToolbarHooks {

  /**
   * Constructs the toolbar hooks.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Closure $menuTree
   *   Lazily retrieves the menu link tree.
   * @param \Closure $state
   *   Lazily retrieves the site state.
   * @param \Closure $iconPackManager
   *   Lazily retrieves the icon pack manager.
   */
  public function __construct(
    private AccountProxyInterface $currentUser,
    #[AutowireServiceClosure('menu.link_tree')]
    private \Closure $menuTree,
    #[AutowireServiceClosure('state')]
    private \Closure $state,
    #[AutowireServiceClosure('plugin.manager.icon_pack')]
    private \Closure $iconPackManager,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'micro_toolbar' => [
        'variables' => [
          'items' => [],
          'display_mode' => 'icons_and_labels',
          'front_url' => NULL,
          'logout_url' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    $account = $this->currentUser;
    // Keep anonymous requests independent of permission and toolbar services.
    if ($account->isAnonymous()) {
      $page_top['micro_toolbar'] = [
        '#access' => AccessResult::forbidden()
          ->addCacheContexts(['user.roles:authenticated']),
      ];

      return;
    }

    $access = AccessResult::allowedIfHasPermission($account, 'access micro toolbar')
      ->addCacheContexts(['user.roles:authenticated']);
    $page_top['micro_toolbar'] = ['#access' => $access];

    if (!$access->isAllowed()) {
      return;
    }

    $menu_tree = ($this->menuTree)();
    $parameters = new MenuTreeParameters();
    $parameters = $parameters
      ->setRoot('system.admin')
      ->excludeRoot()
      ->setTopLevelOnly()
      ->onlyEnabledLinks();
    $tree = $menu_tree->load('admin', $parameters);
    $tree = $menu_tree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);

    // Preserve access and link cacheability, including that of denied links.
    $build = $menu_tree->build($tree);

    foreach ($build['#items'] ?? [] as $id => $item) {
      $build['#items'][$id]['icon'] = $this->buildIcon($item['url']->getOption('icon'));
    }

    $build['#theme'] = 'micro_toolbar';
    $build['#front_url'] = Url::fromRoute('<front>');
    $build['#access'] = $access;
    $build['#display_mode'] = ($this->state)()->get('micro_toolbar.display_mode', 'icons_and_labels');
    // Drupal generates the CSRF token and bubbles its cache metadata in Twig.
    $build['#logout_url'] = Url::fromRoute('user.logout');
    $build['#attached']['library'][] = 'micro_toolbar/toolbar';
    CacheableMetadata::createFromRenderArray($build)
      ->addCacheTags([
        'micro_toolbar:settings',
        'config:system.menu.admin',
        'config:core.extension',
      ])
      ->addCacheContexts(['theme'])
      ->applyTo($build);
    $page_top['micro_toolbar'] = $build;
  }

  /**
   * Builds a menu link icon using same "options.icon" contract as Navigation.
   *
   * @param mixed $icon
   *   Icon options: pack_id, icon_id, and optional settings.
   *
   * @return array
   *   An icon render array, or an empty array to use the template's fallback.
   */
  private function buildIcon(mixed $icon): array {
    if (!is_array($icon)) {
      return [];
    }

    $pack_id = $icon['pack_id'] ?? NULL;
    $icon_id = $icon['icon_id'] ?? NULL;

    if (
      !is_string($pack_id)
      || $pack_id === ''
      || !is_string($icon_id)
      || $icon_id === ''
    ) {
      return [];
    }

    if (!($this->iconPackManager)()->getIcon($pack_id . ':' . $icon_id)) {
      return [];
    }

    return [
      '#type' => 'icon',
      '#pack_id' => $pack_id,
      '#icon_id' => $icon_id,
      '#settings' => is_array($icon['settings'] ?? NULL) ? $icon['settings'] : [],
    ];
  }

}
