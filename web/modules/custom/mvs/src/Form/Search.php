<?php

namespace Drupal\mvs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mvs\enum\Language;
use Drupal\tmdb\builder\SearchMiniTeaserBuilder;
use Drupal\tmdb\enum\TmdbSearchType;
use Drupal\tmdb\TmdbApiAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Search extends FormBase {

  protected ?LanguageManager $languageManager;

  protected ?TmdbApiAdapter $adapter;

  protected ?SearchMiniTeaserBuilder $builder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): Search {
    $instance = parent::create($container);

    $instance->languageManager = $container->get('language_manager');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->builder = $container->get('tmdb.builder.search_mini_teaser');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'search_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $search_title = $this->t('Search in', [], ['context' => 'Search']);

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $search_title,
      '#title_display' => 'invisible',
      '#attributes' => [
        'placeholder' => $search_title,
        'autocomplete' => 'off',
      ],
      '#ajax' => [
        'callback' => '::ajaxHandler',
        'event' => 'change',
        'wrapper' => 'search-results',
        'method' => 'html',
      ],
    ];
    $form['container_right'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'search-elements-align-right',
      ],
    ];
    $form['container_right']['search_type'] = [
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#ajax' => [
        'callback' => '::ajaxHandler',
        'wrapper' => 'search-results',
        'method' => 'html',
      ],
    ];
    $form['container_right']['clear_input'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'reset-search-input',
      ],
      'content' => [
        '#markup' => '&times;',
      ],
    ];
    $form['search_results'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'search-results',
      ],
    ];

    $form['#attached']['library'][] = 'mvs/search';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return string[]
   *   Search results.
   */
  public function ajaxHandler(array $form, FormStateInterface $form_state): array {
    $response = ['#markup' => ''];

    if (!$query = $form_state->getValue('query')) {
      return $response;
    }

    $search_type_raw = $form_state->getValue('search_type');

    if (!$search_type = TmdbSearchType::tryFrom($search_type_raw)) {
      return $response;
    }

    $lang_id = $this->languageManager->getCurrentLanguage()->getId();
    $lang = Language::from($lang_id);

    // Perform a search.
    $results = $this->adapter->search($query, $search_type, $lang);
    // Build renderable themed results.
    $themed_results = $this->builder->build($results['results']);

    // Add build.
    if ($themed_results) {
      $response[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['teasers-list'],
        ],
        'content' => $themed_results,
      ];
    }
    // Add total results count.
    $response[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['total-results'],
      ],
      'content' => [
        '#markup' => $this->t(
          'Total results (@count)',
          ['@count' => $results['total_results']],
          ['context' => 'Search']
        ),
      ],
    ];
    // Add a link to "Search results page".
    if ($results['total_pages'] > 1) {
      $response[] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['all-results-link'],
        ],
        'content' => [
          // @todo Link to "All results" page.
          '#markup' => $this->t('See all results', [], ['context' => 'Search']) . ' &rarr;',
        ],
      ];
    }

    return $response;
  }

  /**
   * Build options for search's select list.
   */
  protected function getOptions(): array {
    $options = [];

    foreach (TmdbSearchType::cases() as $type) {
      $options[$type->name] = $this->getOptionLabel($type);
    }

    return $options;
  }

  /**
   * Get label by type.
   *
   * @param \Drupal\tmdb\enum\TmdbSearchType $type
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getOptionLabel(TmdbSearchType $type): TranslatableMarkup {
    return match ($type) {
      TmdbSearchType::multi => $this->t('any', [], ['context' => 'Search']),
      TmdbSearchType::movies => $this->t('movies', [], ['context' => 'Search']),
      TmdbSearchType::tv => $this->t('tv series', [], ['context' => 'Search']),
      TmdbSearchType::persons => $this->t('persons', [], ['context' => 'Search']),
    };
  }

}
