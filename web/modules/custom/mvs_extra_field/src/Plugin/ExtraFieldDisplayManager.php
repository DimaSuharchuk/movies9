<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manages Extra Field display plugins.
 *
 * @package Drupal\mvs_extra_field\Plugin
 */
class ExtraFieldDisplayManager extends ExtraFieldManagerBase implements ExtraFieldDisplayManagerInterface {

  /**
   * The plugin's subdirectory.
   */
  const PLUGIN_SUBDIR = 'Plugin/ExtraField/Display';

  /**
   * The interface each plugin should implement.
   */
  const PLUGIN_INTERFACE = 'Drupal\mvs_extra_field\Plugin\ExtraFieldDisplayInterface';

  /**
   * The name of the annotation that contains the plugin definition.
   */
  const PLUGIN_ANNOTATION_NAME = 'Drupal\mvs_extra_field\Annotation\ExtraFieldDisplay';

  /**
   * Name of the alter hook for the plugins.
   */
  const ALTER_HOOK = 'extra_field_display_info';

  /**
   * Name of the cache key for plugin data.
   */
  const CACHE_KEY = 'extra_field_display_plugins';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for ExtraFieldManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {

    parent::__construct(self::PLUGIN_SUBDIR, $namespaces, $module_handler, self::PLUGIN_INTERFACE, self::PLUGIN_ANNOTATION_NAME);

    $this->alterInfo(self::ALTER_HOOK);
    $this->setCacheBackend($cache_backend, self::CACHE_KEY);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldInfo() {

    $info = [];
    $definitions = $this->getDefinitions();

    foreach ($definitions as $pluginId => $definition) {
      $entityBundles = $this->supportedEntityBundles($definition['bundles']);

      foreach ($entityBundles as $entityBundle) {
        $entityType = $entityBundle['entity'];
        $bundle = $entityBundle['bundle'];
        $fieldName = $this->fieldName($pluginId);
        $info[$entityType][$bundle]['display'][$fieldName] = [
          'label' => $definition['label'],
          'description' => $definition['description'] ?? '',
          'weight' => $definition['weight'],
          'visible' => $definition['visible'],
        ];
      }
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function entityView(array &$build, ContentEntityInterface $entity, EntityViewDisplayInterface $display, $viewMode) {

    $definitions = $this->getDefinitions();
    $entityBundleKey = $this->entityBundleKey($entity->getEntityTypeId(), $entity->bundle());
    foreach ($definitions as $pluginId => $definition) {
      if (!$this->matchEntityBundleKey($definition['bundles'], $entityBundleKey)) {
        continue;
      }

      $factory = $this->getFactory();
      if (!$display->getComponent($this->fieldName($pluginId))) {
        continue;
      }

      /** @var ExtraFieldDisplayInterface $plugin */
      $plugin = $factory->createInstance($pluginId);
      $fieldName = $this->fieldName($pluginId);
      $plugin->setEntity($entity);
      $plugin->setEntityViewDisplay($display);
      $plugin->setViewMode($viewMode);
      $elements = $plugin->view($entity);
      if (empty($elements)) {
        continue;
      }

      $build[$fieldName] = $elements;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {

    return $this->entityTypeManager;
  }

}
