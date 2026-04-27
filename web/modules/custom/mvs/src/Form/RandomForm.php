<?php

namespace Drupal\mvs\Form;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\mvs\Constant;
use Drupal\mvs\EntityFinder;
use Drupal\mvs\enum\NodeBundle;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RandomForm extends FormBase {

  protected EntityFinder $finder;

  protected LanguageManager $language;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): RandomForm {
    $instance = parent::create($container);

    $instance->finder = $container->get('entity_finder');
    $instance->language = $container->get('language_manager');
    //    $instance->adapter = $container->get('tmdb.adapter');
    //    $instance->builder = $container->get('tmdb.builder.search_mini_teaser');

    return $instance;
  }

  public function getFormId(): string {
    return 'mvs_random_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'container';

    //    $form['filters_enabled'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Show filters', options: ['context' => 'mvs']),
    //      '#attributes' => [
    //        'class' => ['mvs-page-filters-enabled'],
    //      ],
    //    ];

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'mvs-page-filters',
      ],
      //      '#states' => [
      //        'visible' => [
      //          ':input[name=filters_enabled]' => ['checked' => TRUE],
      //        ],
      //      ],
    ];

    // Media types.
    $form['filters']['type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media type', options: ['context' => 'mvs']),
      '#options' => [
        'movie' => $this->t('Movie', options: ['context' => 'mvs']),
        'tv' => $this->t('TV series', options: ['context' => 'mvs']),
        'animation' => $this->t('Animation', options: ['context' => 'mvs']),
        'animation_series' => $this->t('Animation series', options: ['context' => 'mvs']),
      ],
      '#ajax' => [
        'callback' => '::ajaxBuildGenres',
        'wrapper' => 'mvs-genres-wrapper',
      ],
    ];

    //    $form['filters']['type_actions'] = [
    //      '#type' => 'actions',
    //      '#attributes' => [
    //        'class' => ['mvs-page-filter-actions'],
    //      ],
    //    ];
    //    $form['filters']['type_actions']['select_all_types'] = [
    //      '#type' => 'button',
    //      '#value' => $this->t('Select all types', options: ['context' => 'mvs']),
    //      // Avoid validation side effects if later you add required fields.
    //      '#limit_validation_errors' => [],
    //      '#ajax' => [
    //        'callback' => '::ajaxSelectAllTypes',
    //      ],
    //      '#attributes' => [
    //        'class' => ['mvs-button'],
    //      ],
    //    ];
    //    $form['filters']['type_actions']['deselect_all_types'] = [
    //      '#type' => 'button',
    //      '#value' => $this->t('Deselect all types', options: ['context' => 'mvs']),
    //      '#limit_validation_errors' => [],
    //      '#ajax' => [
    //        'callback' => '::ajaxDeselectAllTypes',
    //      ],
    //      '#attributes' => [
    //        'class' => ['mvs-button'],
    //      ],
    //    ];

    // Genres.
    $form['filters']['genres_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'mvs-genres-wrapper',
      ],
    ];

    if ($types = $form_state->getValue('type')) {
      $genres = [];
      $lang_code = $this->language->getCurrentLanguage()->getId();

      if (!empty($types['movie']) || !empty($types['animation'])) {
        $movie_genres_terms = $this->finder->findTermsGenres()
          ->addCondition('field_used_in', NodeBundle::movie->name)
          ->loadEntities()
          ->execute();
        $genres += array_map(fn(Term $term) => $term->getTranslation($lang_code)
          ->label(), $movie_genres_terms);
      }

      if (!empty($types['tv']) || !empty($types['animation_series'])) {
        $tv_genres_terms = $this->finder->findTermsGenres()
          ->addCondition('field_used_in', NodeBundle::tv->name)
          ->loadEntities()
          ->execute();
        $genres += array_map(fn(Term $term) => $term->getTranslation($lang_code)
          ->label(), $tv_genres_terms);
      }

      if ($genres) {
        unset($genres[Constant::GENRE_ID_ANIMATION]);

        asort($genres);

        $form['filters']['genres_wrapper']['genres'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Genres', options: ['context' => 'mvs']),
          '#options' => $genres,
        ];
      }
    }

    $form['teasers'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'mvs-teasers',
        'class' => ['responsive-grid'],
      ],
    ];

    $form['teasers']['load_more'] = [
      '#type' => 'button',
      '#value' => $this->t('Load more'),
      '#attributes' => [
        'class' => ['more-button-wrapper'],
      ],
      '#ajax' => [
        'callback' => '::ajaxAddTeaser',
        //        'wrapper' => 'mvs-teasers',
      ],
    ];

    return $form;
  }

  public static function ajaxBuildGenres(array $form, FormStateInterface $form_state) {
    return $form['filters']['genres_wrapper'];
  }

  public static function ajaxAddTeaser(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $bundles = $genres = [];
    $query = Drupal::database()->select('node', 'n');
    $query->join('node__field_approved', 'a', 'n.nid = a.entity_id');
    $query->addField('n', 'nid');
    $query->condition('a.field_approved_value', 1);
    $query->range(0, 1);
    $query->orderRandom();

    if (
      ($types = $form_state->getValue('type'))
      && $types = array_filter($types)
    ) {
      $is_animation = FALSE;

      if (isset($types['movie']) || isset($types['animation'])) {
        $bundles['movie'] = 'movie';
      }

      if (isset($types['tv']) || isset($types['animation_series'])) {
        $bundles['tv'] = 'tv';
      }

      if (isset($types['animation']) || isset($types['animation_series'])) {
        $is_animation = TRUE;
      }

      // The genre filter depends on the selected types.
      if (
        ($genres_filter = $form_state->getValue('genres'))
        && $genres_filter = array_filter($genres_filter)
      ) {
        if ($is_animation) {
          $genres_filter += [Constant::GENRE_ID_ANIMATION => Constant::GENRE_ID_ANIMATION];
        }

        $query->join('node__field_genres', 'g', 'n.nid = g.entity_id');
        $query->condition('g.field_genres_target_id', $genres_filter, 'IN');
      }
    }

    // No bundles = search by all.
    // 2 bundles = search by all - unnecessary condition.
    if (count($bundles) === 1) {
      $query->condition('n.type', current($bundles));
    }

    if (
      ($node_id = $query->execute()->fetchField())
      && $node = Node::load($node_id)
    ) {
      // @todo Add nid to excluded.

      $tmdb_id = $node->get('field_tmdb_id')->value;

      $adapter = Drupal::service('tmdb.adapter');
      $tmdb_lazy = Drupal::service('tmdb.tmdb_field_lazy_builder');
      $lang_code = Drupal::languageManager()->getCurrentLanguage()->getId();

      $bundle = NodeBundle::from($node->bundle());
      $language = Drupal\mvs\enum\Language::from($lang_code);

      $data = $adapter->getCommonFieldsByTmdbId($bundle, $tmdb_id, $language);
      $teaser = [
        'id' => $tmdb_id,
        'title' => $data['title'],
        'poster_path' => $data['poster_path'],
      ];
      $content = $tmdb_lazy->generateTeaserPlaceholder($bundle, $language, $teaser);
      $response->addCommand(new Drupal\Core\Ajax\BeforeCommand('.more-button-wrapper', $content));
    }
    else {
      $response->addCommand(new Drupal\Core\Ajax\MessageCommand('Nothing found', options: ['type' => 'warning'], clear_previous: FALSE));
      $response->addCommand(new Drupal\Core\Ajax\MessageCommand('Nothing found 2', options: ['type' => 'status'], clear_previous: FALSE));
      $response->addCommand(new Drupal\Core\Ajax\MessageCommand('sd fad fasdf asdf asdf asdf asdf asd fasdf asdf asdf asdf asdf asdf asdf a d f a d f asdf af adf', options: ['type' => 'error'], clear_previous: FALSE));
    }

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

  //  public static function ajaxSelectAllTypes(array $form, FormStateInterface $form_state): AjaxResponse {
  //    $response = new AjaxResponse();
  //
  //    $response->addCommand(new InvokeCommand('#mvs-page-filters :input[name^="type["]', 'prop', [
  //      'checked',
  //      TRUE,
  //    ]));
  //    $response->addCommand(new InvokeCommand('#mvs-page-filters :input[name^="type["]', 'trigger', ['change']));
  //
  //    return $response;
  //  }
  //
  //  public static function ajaxDeselectAllTypes(array $form, FormStateInterface $form_state): AjaxResponse {
  //    $response = new AjaxResponse();
  //
  //    $response->addCommand(new InvokeCommand('#mvs-page-filters :input[name^="type["]', 'prop', [
  //      'checked',
  //      FALSE,
  //    ]));
  //    $response->addCommand(new InvokeCommand('#mvs-page-filters :input[name^="type["]', 'trigger', ['change']));
  //
  //    return $response;
  //  }

}
