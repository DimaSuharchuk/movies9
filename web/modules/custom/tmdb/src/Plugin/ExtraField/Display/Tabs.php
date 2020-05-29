<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "tabs",
 *   label = @Translation("Extra: Tabs"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Tabs extends ExtraTmdbFieldDisplayBase {

  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    $bundle = NodeBundle::memberByValue($entity->bundle());
    $tmdb_id = $entity->{'field_tmdb_id'}->value;
    $lang = Language::memberByValue($entity->language()->getId());

    $build = [];

    // Check if there is content for each tab and add tabs for them.
    if ($entity->bundle() === 'tv') {
      $build['seasons'] = $this->buildAjaxLink('seasons', 'Seasons');
    }
    if ($this->adapter->getVideos($bundle, $tmdb_id, $lang)) {
      $build['trailers'] = $this->buildAjaxLink('trailers', 'Trailers');
    }
    if ($this->adapter->getCast($bundle, $tmdb_id)) {
      $build['cast'] = $this->buildAjaxLink('cast', 'Cast');
    }
    if ($this->adapter->getCrew($bundle, $tmdb_id)) {
      $build['crew'] = $this->buildAjaxLink('crew', 'Crew');
    }
    if ($entity->bundle() === 'movie' && $this->getCommonFieldValue('collection_id')) {
      $build['collection'] = $this->buildAjaxLink('collection', 'Collection');
    }
    if ($this->adapter->getRecommendations($bundle, $tmdb_id, $lang, 1)['results']) {
      $build['related'] = $this->buildAjaxLink('related', 'Related');
    }
    if ($this->adapter->getSimilar($bundle, $tmdb_id, $lang, 1)['results']) {
      $build['similar'] = $this->buildAjaxLink('similar', 'Similar');
    }

    return $build;
  }

  /**
   * Create ajax link for tab.
   *
   * @param string $tab
   *   Tab name must be the same as node view mode.
   * @param string $link_title
   *   Name of the tab that will be displayed on the page.
   *
   * @return array
   *   Renderable array of ajax link.
   */
  private function buildAjaxLink(string $tab, string $link_title) {
    return [
      '#type' => 'link',
      '#title' => $this->t($link_title, [], ['context' => 'Extra tabs']),
      '#url' => Url::fromRoute('imdb.node_tabs_ajax_handler', [
        'node_id' => $this->entity->id(),
        'tab' => $tab,
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
    ];
  }

}
