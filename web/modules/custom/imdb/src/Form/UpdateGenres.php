<?php

namespace Drupal\imdb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\imdb\EntityCreator;
use Drupal\imdb\EntityFinder;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\TmdbAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateGenres extends FormBase {

  /**
   * @var EntityFinder|object|null
   */
  private $finder;

  /**
   * @var TmdbAdapter|object|null
   */
  private $adapter;

  /**
   * @var EntityCreator|object|null
   */
  private $creator;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->finder = $container->get('entity_finder');
    $instance->creator = $container->get('entity_creator');
    $instance->adapter = $container->get('tmdb.adapter');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'imdb.check_genres';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 0,
    ];
    $form['actions']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update genres'),
    ];

    /**
     * Info.
     */
    $genres = $this->finder->findTermsGenres()
      ->count()
      ->execute();
    $form['genres_info'] = [
      '#type' => 'item',
      '#title' => $this->formatPlural($genres, '@count genre in DB.', '@count genres in DB.'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (Language::members() as $lang) {
      // Get movie and TV genres.
      $movie_genres = $this->adapter->getMovieGenres($lang);
      $tv_genres = $this->adapter->getTvGenres($lang);

      // Build genre array from movie+tv genres for saving in TaxonomyTerm.
      $genres = [];
      $this->buildGenres($genres, $movie_genres, NodeBundle::movie());
      $this->buildGenres($genres, $tv_genres, NodeBundle::tv());

      // Save genres in DB.
      foreach ($genres as $tmdb_id => $genre) {
        $this->creator->createTermGenre($genre['name'], $tmdb_id, $genre['used_in'], $lang);
      }
    }
  }

  /**
   * Helper method.
   *
   * @param array $genres
   * @param array $genres_of_type
   * @param NodeBundle $bundle
   */
  private function buildGenres(array &$genres, array $genres_of_type, NodeBundle $bundle) {
    foreach ($genres_of_type['genres'] as $genre) {
      $n = $genre['name'];
      // 1-st letter to uppercase.
      $n = mb_convert_case(mb_substr($n, 0, 1), MB_CASE_UPPER) . mb_substr($n, 1, mb_strlen($n));

      $genres[$genre['id']]['name'] = $n;
      $genres[$genre['id']]['used_in'][] = $bundle->value();
    }
  }

}
