<?php

namespace Drupal\imdb;

use Drupal\imdb\enum\EntityBundle;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\TmdbApiAdapter;

class NodeHelper {

  private EntityFinder $finder;

  private TmdbApiAdapter $adapter;

  private EntityCreator $creator;

  public function __construct(EntityFinder $finder, TmdbApiAdapter $adapter, EntityCreator $creator) {
    $this->finder = $finder;
    $this->adapter = $adapter;
    $this->creator = $creator;
  }


  /**
   * Create node on all languages if node with TMDb ID doesn't exist.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param bool $approved_status
   *   Field "Approved".
   *
   * @return int|null
   *   Node ID if node for all languages has been successfully created.
   */
  public function prepareNodeOnAllLanguages(NodeBundle $bundle, int $tmdb_id, bool $approved_status = FALSE): ?int {
    $e_bundle = EntityBundle::memberByKey($bundle->key());
    if (!$node_id = $this->finder->findNodes()
      ->byBundle($e_bundle)
      ->byTmdbId($tmdb_id)
      ->reduce()
      ->execute()) {
      // Fetch data for every language from TMDb API first of all.
      $node_data = [];
      foreach (Language::members() as $lang) {
        // Fetch data from TMDb API.
        $node_data[$lang->key()] = $this->adapter->getCommonFieldsByTmdbId($bundle, $tmdb_id, $lang);
        // Save data to node.
      }

      // Check if all data is received.
      if (count($node_data) === count(Language::members())) {
        $node = NULL;

        // Create node for every language.
        foreach (Language::members() as $lang) {
          $langcode = $lang->key();

          $node = $this->creator->createNodeMovieOrTv(
            $bundle,
            $node_data[$langcode]['title'],
            $tmdb_id,
            $node_data[$langcode]['imdb_id'],
            $node_data[$langcode]['poster_path'],
            $node_data[$langcode]['genres_ids'],
            $approved_status,
            $lang
          );
        }

        // Get node ID from any (last) translation.
        $node_id = $node ? $node->id() : NULL;
      }
      else {
        $node_id = NULL;
      }
    }

    return $node_id;
  }

}
