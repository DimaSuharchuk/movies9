<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Manages Extra Field form plugins.
 *
 * @package Drupal\mvs_extra_field\Plugin
 */
class ExtraFieldFormManager extends ExtraFieldManagerBase implements ExtraFieldFormManagerInterface {

  /**
   * The plugin's subdirectory.
   */
  const PLUGIN_SUBDIR = 'Plugin/ExtraField/Form';

  /**
   * The interface each plugin should implement.
   */
  const PLUGIN_INTERFACE = 'Drupal\mvs_extra_field\Plugin\ExtraFieldFormInterface';

  /**
   * The name of the annotation that contains the plugin definition.
   */
  const PLUGIN_ANNOTATION_NAME = 'Drupal\mvs_extra_field\Annotation\ExtraFieldForm';

  /**
   * Name of the alter hook for the plugins.
   */
  const ALTER_HOOK = 'extra_field_form_info';

  /**
   * Name of the cache key for plugin data.
   */
  const CACHE_KEY = 'extra_field_form_plugins';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for ExtraFieldFormManager objects.
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
        $info[$entityType][$bundle]['form'][$fieldName] = [
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
  public function entityFormAlter(array &$form, FormStateInterface $formState) {

    $formObject = $formState->getFormObject();
    if (!$formObject instanceof ContentEntityFormInterface || $formObject instanceof ConfirmFormInterface) {
      return;
    }

    $display = $formObject->getFormDisplay($formState);
    if (!$display) {
      return;
    }
    $entityType = $display->getTargetEntityTypeId();
    $entityBundleKey = $this->entityBundleKey($entityType, $display->get('bundle'));
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      if (!$this->matchEntityBundleKey($definition['bundles'], $entityBundleKey)) {
        continue;
      }

      $factory = $this->getFactory();
      if (empty($display->getComponent($this->fieldName($pluginId)))) {
        continue;
      }

      /** @var ExtraFieldFormInterface $plugin */
      $plugin = $factory->createInstance($pluginId);
      $fieldName = $this->fieldName($pluginId);
      $plugin->setEntity($formObject->getEntity());
      $plugin->setEntityFormDisplay($display);
      $plugin->setFormMode($display->get('mode'));

      $form[$fieldName] = $plugin->formElement($form, $formState);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {

    return $this->entityTypeManager;
  }

}
