<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "filmography",
 *   label = @Translation("Extra: Filmography"),
 *   description = "",
 *   bundles = {"person.person"},
 *   replaceable = true
 * )
 */
class Filmography extends ExtraTmdbFieldDisplayBase {

  private ?TmdbTeaser $tmdb_teaser;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->tmdb_teaser = $container->get('tmdb.tmdb_teaser');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    $lang = Language::from($entity->language()->getId());

    if ($credits = $this->getPersonCommonField('combined_credits', TRUE)) {
      if ($cast_teasers = $credits['cast']) {
        $build['acting'] = $this->buildTeasersBlock($lang, $cast_teasers, 'acting', 'Acting');
      }
      if ($crew_teasers = $credits['crew']) {
        $build['production'] = $this->buildTeasersBlock($lang, $crew_teasers, 'production', 'Production');
      }
    }

    return $build;
  }

  /**
   * Build a renderable array to display it on a page with Movie and TV series
   * teasers for a specific context, such as "Acting" or "Production".
   *
   * @param Language $lang
   * @param array $teasers
   *   Raw data from TMDb API with movies/series (teasers) in which the current
   *   Person was involved.
   * @param string $id
   *   Using this ID as CSS ID of the block on the page.
   * @param string $title
   *   Title of the block on the page.
   *
   * @return array
   */
  private function buildTeasersBlock(Language $lang, array $teasers, string $id, string $title): array {
    $separated_teasers = $this->separateTeasersByBundle($teasers);
    $movies = isset($separated_teasers['movie']) ? $this->uniqueTeasers($separated_teasers['movie']) : NULL;
    $tvs = isset($separated_teasers['tv']) ? $this->uniqueTeasers($separated_teasers['tv']) : NULL;

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => "filmography-$id",
        'class' => ['filmography-wrapper', 'container'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t($title, [], ['context' => 'Field label']),
        '#attributes' => [
          'class' => ['field-title'],
        ],
      ],
    ];
    if ($movies) {
      $build['movies'] = [
        '#theme' => 'tmdb_items_list',
        '#title' => $this->t('Movies', [], ['context' => 'Field label']),
        '#items' => $this->tmdb_teaser->buildTmdbTeasers($movies, NodeBundle::movie, $lang, TRUE),
        '#use_container' => FALSE,
      ];
    }
    if ($tvs) {
      $build['tvs'] = [
        '#theme' => 'tmdb_items_list',
        '#title' => $this->t('TVs', [], ['context' => 'Field label']),
        '#items' => $this->tmdb_teaser->buildTmdbTeasers($tvs, NodeBundle::tv, $lang, TRUE),
        '#use_container' => FALSE,
      ];
    }

    return $build;
  }

  /**
   * Separate movie teasers from TV series teasers into two arrays.
   *
   * @param array $teasers
   *   An array of mixed teasers.
   *
   * @return array
   *   An array that contains an array of movies available with the ["movies"]
   *   key, and TV shows with the ["tv"] key.
   */
  private function separateTeasersByBundle(array $teasers): array {
    $separated = [];

    foreach ($teasers as $teaser) {
      $separated[$teaser['bundle']][] = $teaser;
    }

    return $separated;
  }

  /**
   * Remove duplicates by Movie/TV TMDb ID.
   *
   * @param array $teasers
   *
   * @return array
   */
  private function uniqueTeasers(array $teasers): array {
    $unique = [];
    foreach ($teasers as $teaser) {
      $unique[$teaser['id']] = $teaser;
    }
    return $unique;
  }

}
