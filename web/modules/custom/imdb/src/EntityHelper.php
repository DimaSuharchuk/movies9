<?php

namespace Drupal\imdb;

use Drupal\imdb\enum\EntityBundle;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\exception\ImdbException;
use Drupal\tmdb\TmdbApiAdapter;
use TypeError;

class EntityHelper {

  private EntityFinder $finder;

  private TmdbApiAdapter $adapter;

  private EntityCreator $creator;

  public function __construct(EntityFinder $finder, TmdbApiAdapter $adapter, EntityCreator $creator) {
    $this->finder = $finder;
    $this->adapter = $adapter;
    $this->creator = $creator;
  }


  /**
   * Create node in all languages if node with TMDb ID doesn't exist.
   *
   * @param NodeBundle $bundle
   * @param int $tmdb_id
   * @param bool $approved_status
   *   Field "Approved".
   *
   * @return int|null
   *   Node ID if node for all languages has been successfully created.
   */
  public function prepareNode(NodeBundle $bundle, int $tmdb_id, bool $approved_status = FALSE): ?int {
    $e_bundle = EntityBundle::memberByKey($bundle->key());
    if (!$node_id = $this->finder->findNodes()
      ->byBundle($e_bundle)
      ->byTmdbId($tmdb_id)
      ->reduce()
      ->execute()) {
      $all_langs = Language::members();

      // Fetch data for every language from TMDb API first of all.
      // This is very important to do because this code will only work once for
      // one entity. And if the creation is interrupted due to some failure in
      // getting data for some language, the node will be broken forever.
      $node_data = [];
      foreach ($all_langs as $lang) {
        // Fetch data from TMDb API.
        if (!$fetch = $this->adapter->getCommonFieldsByTmdbId($bundle, $tmdb_id, $lang)) {
          return NULL;
        }
        // Check if the node belongs to the excluded genres.
        if (array_intersect(Constant::EXCLUDED_GENRES_TMDB_IDS, $fetch['genres_ids'])) {
          // We can't create nodes related to certain genres, so we'll finish
          // the work here.
          return NULL;
        }

        $node_data[$lang->key()] = $fetch;
      }

      // Check if all data is received.
      if (count(array_filter($node_data)) === count($all_langs)) {
        $node = NULL;

        // Create node for every language.
        foreach ($all_langs as $lang) {
          $langcode = $lang->key();

          try {
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
          catch (ImdbException | TypeError $e) {
            // This means that only part of necessary data comes from the
            // TMDb API. Therefore such node cannot be saved.
            return NULL;
          }
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

  /**
   * Create Person content entity in all languages if it doesn't exist.
   *
   * @param int $tmdb_id
   *   Person TMDb ID.
   *
   * @return int|null
   *   Drupal entity ID.
   */
  public function preparePerson(int $tmdb_id): ?int {
    if (!$person_id = $this->finder->findPersons()
      ->addCondition('tmdb_id', $tmdb_id)
      ->reduce()
      ->execute()) {
      $all_langs = Language::members();

      // Fetch data for every language from TMDb API first of all.
      // This is very important to do because this code will only work once for
      // one entity. And if the creation is interrupted due to some failure in
      // getting data for some language, the node will be broken forever.
      $person_data = [];
      foreach ($all_langs as $lang) {
        // Fetch data from TMDb API.
        $person_data[$lang->key()] = $this->adapter->getPerson($tmdb_id, $lang);
      }

      // Check if all data is received.
      if (count(array_filter($person_data)) === count($all_langs)) {
        $person = NULL;

        // Create Person for every language.
        foreach ($all_langs as $lang) {
          $lang_code = $lang->key();

          try {
            $person = $this->creator->createPerson(
              $lang,
              $tmdb_id,
              $person_data[$lang_code]['name'],
              $person_data[$lang_code]['profile_path'],
            );
          }
          catch (ImdbException | TypeError $e) {
            // This means that only part of necessary data comes from the
            // TMDb API. Therefore such node cannot be saved.
            return NULL;
          }
        }

        // Get Person ID from any (last) translation.
        $person_id = $person ? $person->id() : NULL;
      }
      else {
        $person_id = NULL;
      }
    }

    return $person_id;
  }

}
