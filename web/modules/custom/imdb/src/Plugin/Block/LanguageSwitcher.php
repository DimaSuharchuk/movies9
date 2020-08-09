<?php

namespace Drupal\imdb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block (
 *   id = "movies_language_switcher",
 *   admin_label = @Translation ("Language switcher"),
 *   category = @Translation ("Custom"),
 * )
 */
class LanguageSwitcher extends BlockBase implements ContainerFactoryPluginInterface {

  private ?LanguageManager $language_manager;

  private ?PathMatcher $path_matcher;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->language_manager = $container->get('language_manager');
    $instance->path_matcher = $container->get('path.matcher');

    return $instance;
  }


  /**
   * @inheritDoc
   */
  public function build() {
    $route = $this->path_matcher->isFrontPage() ? '<front>' : '<current>';
    $links = $this->language_manager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, Url::fromRoute($route));
    $links = $links->{'links'};

    $current_language = $this->language_manager->getCurrentLanguage();
    $lang_code = $current_language->getId();
    $lang_name = $this->language_manager->getNativeLanguages()[$lang_code]->getName();

    // Remove active language from list.
    unset($links[$lang_code]);

    // Get only first 3 letters of language name.
    foreach ($links as &$link) {
      $link['title'] = mb_substr($link['title'], 0, 3);
    }

    $links_themed = [
      '#theme' => 'links',
      '#links' => $links,
    ];

    return [
      '#theme' => 'movies_language_switcher',
      '#active_label' => mb_substr($lang_name, 0, 3),
      '#links' => $links_themed,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
