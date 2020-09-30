<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "genres",
 *   label = @Translation("Extra: Genres"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Genres extends ExtraTmdbFieldDisplayBase {

  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($genres_raw_ids = $entity->{'field_genres'}->getValue()) {
      $genres_ids = array_column($genres_raw_ids, 'target_id');
      $finder = \Drupal::service('entity_finder');
      /** @var TermInterface[] $genres */
      $genres = $finder->findTermsGenres()->loadMultipleById($genres_ids);

      $build = $this->buildGenres($genres, $entity->language());
    }

    return $build;
  }

  /**
   * Build genres links - link to main view with enabled genre filter.
   *
   * @param TermInterface[] $genres
   * @param LanguageInterface $lang
   *
   * @return array
   */
  private function buildGenres(array $genres, LanguageInterface $lang): array {
    $langcode = $lang->getId();

    $genres_links = [];
    foreach ($genres as $genre) {
      $genre_id = $genre->id();
      $genres_links[] = [
        '#type' => 'link',
        '#title' => $genre->getTranslation($langcode)->getName(),
        '#url' => Url::fromRoute(
          'view.movies.home',
          ["field_genres_target_id[{$genre_id}]" => $genre_id],
        ),
      ];
    }
    return $genres_links;
  }

}
