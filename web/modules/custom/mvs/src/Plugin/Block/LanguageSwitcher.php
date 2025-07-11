<?php

namespace Drupal\mvs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
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

  private ?CurrentRouteMatch $routeMatch;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LanguageSwitcher|ContainerFactoryPluginInterface|static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->language_manager = $container->get('language_manager');
    $instance->routeMatch = $container->get('current_route_match');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function build(): array {
    $route_name = $this->routeMatch->getRouteName();

    if (!$route_name) {
      return [];
    }

    $links = $this->language_manager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, Url::fromRouteMatch($this->routeMatch))->links;

    $current_language = $this->language_manager->getCurrentLanguage();
    $lang_code = $current_language->getId();
    $current_lang_name = $this->language_manager->getNativeLanguages()[$lang_code]->getName();

    // Remove active language from a list.
    unset($links[$lang_code]);

    // Trim language name if needed.
    $conf = $this->getConfiguration();
    if ($conf['trim_links']) {
      $length = $conf['trim_length'];

      // Trim links.
      foreach ($links as &$link) {
        $link['title'] = mb_substr($link['title'], 0, $length);
      }
      // Trim static label.
      $current_lang_name = mb_substr($current_lang_name, 0, $length);
    }

    $links_themed = [
      '#theme' => 'links',
      '#links' => $links,
    ];

    return [
      '#theme' => 'movies_language_switcher',
      '#active_label' => $current_lang_name,
      '#links' => $links_themed,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'trim_links' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $conf = $this->getConfiguration();

    $form['trim_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trim links'),
      '#default_value' => $conf['trim_links'],
    ];

    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim length'),
      '#default_value' => $conf['trim_length'] ?? 3,
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="settings[trim_links]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $is_trim = $form_state->getValue('trim_links');
    if ($is_trim && $form_state->getValue('trim_length') < 1) {
      $form_state->setErrorByName('trim_length', $this->t('Minimum 1 character.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $is_trim = $form_state->getValue('trim_links');
    $this->configuration['trim_links'] = $is_trim;
    if ($is_trim) {
      $this->configuration['trim_length'] = $form_state->getValue('trim_length');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
