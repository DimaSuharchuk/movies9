<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for Extra Field plugin managers.
 *
 * @package Drupal\mvs_extra_field\Plugin
 */
abstract class ExtraFieldManagerBase extends DefaultPluginManager implements ExtraFieldManagerBaseInterface {

  /**
   * Caches bundles per entity type.
   *
   * @var array
   */
  protected $entityBundles;

  /**
   * {@inheritdoc}
   */
  abstract public function fieldInfo();

  /**
   * Clear the service's local cache.
   *
   * TODO Add this to the interface in the 3.0.0 release.
   */
  public function clearCache() {

    $this->entityBundles = [];
  }

  /**
   * Checks if the plugin bundle definition matches the entity bundle key.
   *
   * @param string[] $pluginBundles
   *   Defines which entity-bundle pair the plugin can be used for.
   *   Format: [entity type].[bundle] or [entity type].* .
   * @param string $entityBundleKey
   *   The entity-bundle string of a content entity.
   *   Format: [entity type].[bundle] .
   *
   * @return bool
   *   True of the plugin bundle definition matches the entity bundle key.
   */
  protected function matchEntityBundleKey(array $pluginBundles, $entityBundleKey) {

    $match = FALSE;
    foreach ($pluginBundles as $pluginBundle) {
      if (strpos($pluginBundle, '.*')) {
        $match = explode('.', $pluginBundle)[0] == explode('.', $entityBundleKey)[0];
      }
      else {
        $match = $pluginBundle == $entityBundleKey;
      }

      if ($match) {
        break;
      }
    }

    return $match;
  }

  /**
   * Returns entity-bundle combinations this plugin supports.
   *
   * If a wildcard entity type of bundle is set, all respective entity types or
   * bundles of the entity will be included.
   *
   * @param string[] $entityBundleKeys
   *   Array of entity-bundle strings that define the bundles for which the
   *   plugin can be used. Format: [entity].[bundle]
   *   '*' can be used as bundle wildcard.
   *
   * @return array
   *   Array of entity and bundle machine name pairs.
   */
  protected function supportedEntityBundles(array $entityBundleKeys) {

    $result = [];
    foreach ($entityBundleKeys as $entityBundleKey) {
      if (strpos($entityBundleKey, '.') === FALSE) {
        continue;
      }

      [$parsedEntityType, $parsedBundle] = explode('.', $entityBundleKey);
      if ($parsedEntityType === '*') {
        $entityTypes = $this->getAllContentEntityTypes();
        foreach ($entityTypes as $entityType) {
          $result += $this->makeEntityBundlePairs($entityType, $parsedBundle);
        }
      }
      else {
        $result += $this->makeEntityBundlePairs($parsedEntityType, $parsedBundle);
      }
    }

    return $result;
  }

  /**
   * Returns an array of entity and bundle names.
   *
   * @param string $entityType
   *   The entity type machine name.
   * @param string $bundleString
   *   The bundle machine name or '*'.
   *
   * @return array
   *   Array of entity and bundle machine names keyed by a "[entity].[bundle]"
   *   key. The value is an array with keys of:
   *   - entity: The entity type machine name.
   *   - bundle: The bundle machine name.
   */
  protected function makeEntityBundlePairs($entityType, $bundleString) {
    $pairs = [];

    if ($bundleString === '*') {
      foreach ($this->allEntityBundles($entityType) as $bundle) {
        $key = $this->entityBundleKey($entityType, $bundle);
        $pairs[$key] = [
          'entity' => $entityType,
          'bundle' => $bundle,
        ];
      }
    }
    else {
      $key = $this->entityBundleKey($entityType, $bundleString);
      $pairs[$key] = [
        'entity' => $entityType,
        'bundle' => $bundleString,
      ];
    }

    return $pairs;
  }

  /**
   * Returns the bundles that are defined for an entity type.
   *
   * @param string $entityType
   *   The entity type to get the bundles for.
   *
   * @return string[]
   *   Array of bundle names.
   */
  protected function allEntityBundles($entityType) {

    if (!isset($this->entityBundles[$entityType])) {
      $bundleType = $this->getEntityBundleType($entityType);

      if ($bundleType) {
        $bundles = $this->getEntityBundles($bundleType);
      }
      else {
        $bundles = [$entityType => $entityType];
      }

      $this->entityBundles[$entityType] = $bundles;
    }

    return $this->entityBundles[$entityType];
  }

  /**
   * Returns the machine names of all content entities.
   *
   * @return array
   *   Machine names of content entities.
   */
  protected function getAllContentEntityTypes() {

    $contentEntityTypes = [];
    $definitions = $this->getEntityTypeManager()->getDefinitions();

    foreach ($definitions as $entityTypeDefinition) {
      if (!$entityTypeDefinition instanceof ContentEntityTypeInterface) {
        continue;
      }
      $contentEntityTypes[] = $entityTypeDefinition->id();
    }

    return $contentEntityTypes;
  }

  /**
   * Build the field name string.
   *
   * @param string $pluginId
   *   The machine name of the Extra Field plugin.
   *
   * @return string
   *   Field name.
   */
  protected function fieldName($pluginId) {

    return static::EXTRA_FIELD_PREFIX . $pluginId;
  }

  /**
   * Creates a key string with entity type and bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return string
   *   Formatted string.
   */
  protected function entityBundleKey($entityType, $bundle) {

    return "$entityType.$bundle";
  }

  /**
   * Returns the name of the entity type which provides bundles.
   *
   * @param string $entityType
   *   The entity type to get the data of.
   *
   * @return string|null
   *   The entity type or null if the entity does not provide bundles.
   */
  protected function getEntityBundleType($entityType) {
    return $this->getEntityTypeManager()
      ->getDefinition($entityType)
      ->getBundleEntityType();
  }

  /**
   * Returns all bundles of an entity type.
   *
   * @param string $entityType
   *   The entity type to get the data of.
   *
   * @return array
   *   Array of bundle names.
   */
  protected function getEntityBundles($entityType) {
    return $this->getEntityTypeManager()
      ->getStorage($entityType)
      ->getQuery()
      ->execute();
  }

  /**
   * Returns the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  abstract protected function getEntityTypeManager();

}
