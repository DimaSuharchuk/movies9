<?php

namespace Drupal\imdb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
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
    $current_lang_name = $this->language_manager->getNativeLanguages()[$lang_code]->getName();

    // Remove active language from list.
    unset($links[$lang_code]);

    // Trim language name if need.
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
  public function defaultConfiguration() {
    return [
      'trim_links' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $conf = $this->getConfiguration();

    $form['trim_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trim links'),
      '#default_value' => $conf['trim_links'],
    ];

    $form['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim length'),
      '#default_value' => $conf['trim_links'] ? $conf['length'] : '',
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
  public function blockValidate($form, FormStateInterface $form_state) {
    $is_trim = $form_state->getValue('trim_links');
    if ($is_trim && $form_state->getValue('trim_length') < 1) {
      $form_state->setErrorByName('trim_length', $this->t('Minimum 1 character.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $is_trim = $form_state->getValue('trim_links');
    $this->configuration['trim_links'] = $is_trim;
    if ($is_trim) {
      $this->configuration['trim_length'] = $form_state->getValue('trim_length');
    }
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
