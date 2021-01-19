<?php

namespace Drupal\imdb;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\imdb\enum\EntityBundle;
use Drupal\imdb\enum\EntityType;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\enum\TermBundle;
use Drupal\node\Entity\Node;
use Drupal\person\Entity\PersonEntity;
use Drupal\taxonomy\Entity\Term;

class EntityCreator {

  private EntityTypeManager $entity_type_manager;

  private EntityFinder $finder;


  /**
   * EntityCreator constructor.
   *
   * @param EntityTypeManager $manager
   * @param EntityFinder $finder
   */
  public function __construct(EntityTypeManager $manager, EntityFinder $finder) {
    $this->entity_type_manager = $manager;
    $this->finder = $finder;
  }


  /**
   * Save Taxonomy Term "Genre" in DB.
   *
   * @param string $name
   *   Term's title.
   * @param int $tmdb_id
   * @param array $used_in
   *   Used in node "Movie" and|or "TV".
   * @param Language $lang
   *
   * @return Term|null
   */
  public function createTermGenre(string $name, int $tmdb_id, array $used_in, Language $lang): ?Term {
    return $this->createTerm(TermBundle::genre(), $lang, $name, $tmdb_id, ['field_used_in' => $used_in]);
  }


  /**
   * Save Node "Movie" or "TV" in DB.
   *
   * @param NodeBundle $bundle
   *   Node type "movie" or "tv".
   * @param string $title
   *   Node's title.
   * @param int $tmdb_id
   *   Node field.
   * @param string $imdb_id
   *   Node field.
   * @param string|null $poster
   *   Poster in TMDb API format. See TmdbImageItem.
   * @param array $genres_tmdb_ids
   *   Array of TMDb IDs of TMDb genres.
   * @param bool $approved
   *   Node field.
   * @param Language $lang
   *
   * @return Node|null
   *
   * @see TmdbImageItem
   */
  public function createNodeMovieOrTv(NodeBundle $bundle, string $title, int $tmdb_id, string $imdb_id, ?string $poster, array $genres_tmdb_ids, bool $approved, Language $lang): ?Node {
    $genres_ids = $this->finder->findTermsGenres()
      ->byTmdbIds($genres_tmdb_ids)
      ->execute();
    // Build array with all needed fields.
    $fields_data = [
      'field_approved' => $approved,
      'field_genres' => $genres_ids,
      'field_poster' => $poster,
    ];

    return $this->createNode($bundle, $lang, $title, $tmdb_id, $imdb_id, $fields_data);
  }

  /**
   * Save Person content entity in DB.
   *
   * @param Language $lang
   * @param int $tmdb_id
   *   Person ID from TMDb.
   * @param string $full_name
   *   Person full name.
   * @param string|null $avatar
   *   Person avatar in TMDb API format. See TmdbImageItem.
   *
   * @return PersonEntity|null
   */
  public function createPerson(Language $lang, int $tmdb_id, string $full_name, string $avatar = NULL): ?PersonEntity {
    /** @var PersonEntity $person */
    $person = $this->createEntity(
      EntityType::person(),
      $lang,
      NULL,
      [
        'tmdb_id' => $tmdb_id,
        'name' => $full_name,
      ],
      ['field_avatar' => $avatar],
      'tmdb_id',
      $tmdb_id,
    );
    return $person;
  }

  /**
   * Save Taxonomy Term in DB.
   *
   * @param TermBundle $bundle
   *   Vocabulary ID.
   * @param Language $lang
   * @param string $name
   *   Term's title.
   * @param int $tmdb_id
   *   TMDb ID field.
   * @param array $fields_data
   *   Other fields data.
   *
   * @return Term
   */
  private function createTerm(TermBundle $bundle, Language $lang, string $name, int $tmdb_id, array $fields_data = []): Term {
    $bundle = EntityBundle::memberByValue($bundle->value());
    $fields_data += [
      'name' => $name,
    ];
    /** @var Term $term */
    $term = $this->createEntityBasedOnTmdbField(EntityType::term(), $bundle, $lang, $tmdb_id, $fields_data);
    return $term;
  }

  /**
   * Save Node in DB.
   *
   * @param NodeBundle $bundle
   * @param Language $lang
   * @param string $title
   * @param int $tmdb_id
   * @param string $imdb_id
   * @param array $fields_data
   *   Other fields data.
   *
   * @return Node
   */
  private function createNode(NodeBundle $bundle, Language $lang, string $title, int $tmdb_id, string $imdb_id, array $fields_data = []): ?Node {
    $bundle = EntityBundle::memberByValue($bundle->value());
    $fields_data += [
      'title' => $title,
      'field_imdb_id' => $imdb_id,
    ];
    /** @var Node $node */
    $node = $this->createEntityBasedOnTmdbField(EntityType::node(), $bundle, $lang, $tmdb_id, $fields_data);
    return $node;
  }

  /**
   * Find node by IMDb ID and set Approved status to TRUE.
   *
   * @param string $imdb_id
   *
   * @return bool
   */
  public function updateNodeApprovedStatus(string $imdb_id): bool {
    /** @var Node $node */
    if ($node = $this->finder->findNodes()
      ->byImdbId($imdb_id)
      ->loadEntities()
      ->execute()) {

      // Node exists and status already TRUE.
      if ($node->{'field_approved'}->value) {
        return TRUE;
      }

      $node->{'field_approved'} = TRUE;
      try {
        return $node->save();
      } catch (EntityStorageException $e) {
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Create abstract entity with required field "field_tmdb_id".
   *
   * @param EntityType $type
   * @param EntityBundle $bundle
   * @param Language $lang
   * @param int $tmdb_id
   *   Required TMDb ID field.
   * @param array $fields_data
   *   Other fields data.
   *
   * @return ContentEntityInterface
   */
  private function createEntityBasedOnTmdbField(EntityType $type, EntityBundle $bundle, Language $lang, int $tmdb_id, array $fields_data = []): ?ContentEntityInterface {
    $fields_data += ['field_tmdb_id' => $tmdb_id];
    return $this->createEntity($type, $lang, $bundle, [], $fields_data, 'field_tmdb_id', $tmdb_id);
  }

  /**
   * Create abstract entity. If entity has unique field, the entity will be
   * found in DB and returned, or returned on specific language, or added new
   * fields translations to exists entity.
   *
   * @param EntityType $type
   *   Entity type, like "node", "taxonomy_term" etc.
   * @param Language $lang
   * @param EntityBundle|null $bundle
   *   Entity bundle, taxonomy vocabulary ID or something like that.
   * @param array $properties_data
   *   Some entity properties, not fields.
   * @param array $fields_data
   *   Fields data should be save in entity fields.
   * @param string $unique_field_name
   *   Key of entity's unique field.
   * @param string $unique_field_value
   *   Value of unique field for search of existing entity in DB.
   *
   * @return ContentEntityInterface
   */
  private function createEntity(
    EntityType $type,
    Language $lang,
    EntityBundle $bundle = NULL,
    array $properties_data = [],
    array $fields_data = [],
    string $unique_field_name = '',
    string $unique_field_value = ''
  ): ?ContentEntityInterface {

    $type_value = $type->value();
    $lang_value = $lang->value();
    $bundle_value = $bundle ? $bundle->value() : NULL;

    $storage = NULL;
    try {
      $storage = $this->entity_type_manager->getStorage($type_value);
    } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }

    $bundle_key = NULL;
    if ($bundle) {
      try {
        $bundle_key = $this->entity_type_manager->getDefinition($type_value, FALSE)
          ->getKey('bundle');
      } catch (PluginNotFoundException $e) {
      }
    }

    $entities = NULL;
    if ($unique_field_name && $unique_field_value) {
      $search_properties = [
        $unique_field_name => $unique_field_value,
      ];
      if ($bundle) {
        $search_properties[$bundle_key] = $bundle_value;
      }
      $entities = $storage->loadByProperties($search_properties);
    }

    if ($entities) {
      /** @var ContentEntityInterface $entity */
      $entity = reset($entities);
      if ($entity->hasTranslation($lang_value)) {
        return $entity->getTranslation($lang_value);
      }
      else {
        $entity = $entity->addTranslation($lang_value);
        $entity->{'uid'} = 1;

        // Set translatable fields.
        $translatable_fields = array_keys($entity->getTranslatableFields());
        foreach ($fields_data + $properties_data as $field => $value) {
          if (in_array($field, $translatable_fields)) {
            $entity->set($field, $value);
          }
        }
      }
    }
    else {
      $create_properties = [
        'uid' => 1,
        'langcode' => $lang_value,
      ];
      if ($bundle) {
        $create_properties[$bundle_key] = $bundle_value;
      }
      $create_properties += $properties_data;

      $entity = $storage->create($create_properties);
      // Set other fields.
      foreach ($fields_data as $field => $value) {
        if (is_array($value)) {
          foreach ($value as $item) {
            $entity->$field->appendItem($item);
          }
        }
        else {
          $entity->$field = $value;
        }
      }
    }

    try {
      $entity->save();
    } catch (EntityStorageException $e) {
      return NULL;
    }

    return $entity;
  }

}
