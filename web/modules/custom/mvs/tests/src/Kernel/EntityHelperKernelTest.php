<?php

namespace Drupal\Tests\mvs\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\mvs\EntityHelper;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\mvs\Kernel\Stub\FakeTmdbApiAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass('Drupal\mvs\EntityHelper')]
#[Group('mvs')]
class EntityHelperKernelTest extends KernelTestBase {

  protected static $modules = [
    'language',
    'node',
    'options',
    'taxonomy',
    'field',
    'user',
    'text',
    'system',
    'filter',
    'mvs',
    'tmdb',
    'person',
    'imdb',
  ];

  /**
   * @var \Drupal\mvs\EntityHelper
   */
  private EntityHelper $sut;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('configurable_language');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    foreach (Language::cases() as $lang) {
      ConfigurableLanguage::createFromLangcode($lang->name)->save();
    }

    NodeType::create([
      'type' => 'movie',
      'name' => 'Movie',
    ])->save();

    $this->_createNodeFields();

    Vocabulary::create([
      'vid' => 'genre',
      'name' => 'Genres',
    ])->save();

    $this->_createTermFields();

    Term::create([
      'vid' => 'genre',
      'name' => 'Action',
      'field_tmdb_id' => 1,
      'field_used_in' => ['value' => 'movie'],
    ])->save();
    Term::create([
      'vid' => 'genre',
      'name' => 'Drama',
      'field_tmdb_id' => 2,
      'field_used_in' => ['value' => 'movie'],
    ])->save();

    /** @var \Drupal\mvs\EntityFinder $finder */
    $finder = $this->container->get('entity_finder');
    /** @var \Drupal\mvs\EntityCreator $creator */
    $creator = $this->container->get('entity_creator');
    $adapter = new FakeTmdbApiAdapter();

    $this->sut = new EntityHelper($finder, $adapter, $creator);
  }

  /**
   * @covers \Drupal\mvs\EntityHelper::prepareNode
   */
  #[Group('positive')]
  public function testPrepareNodeCreatesNode(): void {
    $tmdb_id = 999999;
    $bundle = NodeBundle::movie;

    $nid = $this->sut->prepareNode($bundle, $tmdb_id, TRUE);
    $this->assertIsInt($nid, 'Node ID should be returned');
    $this->assertGreaterThan(0, $nid, 'Node ID should be positive');
  }

  private function _createNodeFields() {
    // Approved (boolean).
    FieldStorageConfig::create([
      'field_name' => 'field_approved',
      'entity_type' => 'node',
      'type' => 'boolean',
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('node', 'field_approved'),
    //      'bundle' => 'movie',
    //      'label' => 'Approved',
    //      'entity_type' => 'node',
    //    ])->save();

    // TMDB ID.
    FieldStorageConfig::create([
      'field_name' => 'field_tmdb_id',
      'entity_type' => 'node',
      'type' => 'integer',
      'cardinality' => 1,
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('node', 'field_tmdb_id'),
    //      'bundle' => 'movie',
    //      'label' => 'Tmdb ID',
    //      'entity_type' => 'node',
    //    ])->save();

    // IMDb ID.
    FieldStorageConfig::create([
      'field_name' => 'field_imdb_id',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('node', 'field_imdb_id'),
    //      'bundle' => 'movie',
    //      'label' => 'IMDb ID',
    //      'entity_type' => 'node',
    //    ])->save();

    // Poster (string, якщо це кастомний field type — потрібно буде окремо реєструвати тип).
    FieldStorageConfig::create([
      'field_name' => 'field_poster',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('node', 'field_poster'),
    //      'bundle' => 'movie',
    //      'label' => 'Poster',
    //      'entity_type' => 'node',
    //    ])->save();

    // Genres — reference to taxonomy_term:genre.
    FieldStorageConfig::create([
      'field_name' => 'field_genres',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('node', 'field_genres'),
    //      'bundle' => 'movie',
    //      'label' => 'Genres',
    //      'entity_type' => 'node',
    //      'settings' => [
    //        'handler' => 'default',
    //        'handler_settings' => [
    //          'target_bundles' => ['genre' => 'genre'],
    //        ],
    //      ],
    //    ])->save();
  }

  private function _createTermFields() {
    FieldStorageConfig::create([
      'field_name' => 'field_tmdb_id',
      'entity_type' => 'taxonomy_term',
      'type' => 'integer',
      'cardinality' => 1,
    ])->save();
    //    FieldConfig::create([
    //      'field_storage' => FieldStorageConfig::loadByName('taxonomy_term', 'field_tmdb_id'),
    //      'bundle' => 'genre',
    //      'label' => 'Tmdb ID',
    //      'entity_type' => 'node',
    //    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_used_in',
      'entity_type' => 'taxonomy_term',
      'type' => 'list_string',
      'settings' => [
        'allowed_values' => [
          'movie' => 'Movie',
          'tv' => 'TV',
        ],
      ],
      'cardinality' => 1,
    ])->save();
    //    FieldConfig::create([
    //      'field_name' => 'field_used_in',
    //      'entity_type' => 'taxonomy_term',
    //      'bundle' => 'genre',
    //      'label' => 'Used in',
    //      'required' => TRUE,
    //    ])->save();
  }

}
