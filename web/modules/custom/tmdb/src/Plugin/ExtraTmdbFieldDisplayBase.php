<?php

namespace Drupal\tmdb\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\mvs\enum\Language;
use Drupal\tmdb\TmdbApiAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ExtraTmdbFieldDisplayBase extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected ?TmdbApiAdapter $adapter;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->adapter = $container->get('tmdb.adapter');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function view(ContentEntityInterface $entity): ?array {
    $build = $this->build($entity);

    $definition = $this->getPluginDefinition();

    // Wrap every TMDb extra field with this wrapper.
    $build = [
      '#theme' => 'tmdb_field',
      '#content' => $build,
      '#css_class' => $definition['css_class'] ?? $this->getPluginId(),
    ];

    // Wrap some fields with theme "replaceable_field" for AJAX tabs.
    if (isset($definition['replaceable']) && $definition['replaceable'] === TRUE) {
      $build = [
        '#theme' => 'replaceable_field',
        '#content' => $build,
      ];
    }

    return $build;
  }

  /**
   * Builds a renderable array for the field.
   *
   * @param ContentEntityInterface $entity
   *   Entity this extra field used for.
   *
   * @return array
   *   Renderable array.
   */
  abstract public function build(ContentEntityInterface $entity): array;

  /**
   * Helper method: Get value of some common movie or TV field from TMDb API or
   * cached file.
   *
   * @param $field_name
   *   Name of common field in TMDbLocalStorage (some fields key rewrote with
   *   more logical name).
   *
   * @return mixed|null
   *   Field value.
   */
  protected function getCommonFieldValue(string $field_name) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entity;

    return $this->adapter->getCommonFieldValue($node, $field_name);
  }

  /**
   * Get value of some common TMDb Person's field.
   *
   * @param string $field
   *   Name of common field in TMDbLocalStorage (some fields key rewrote with
   *   more logical name).
   * @param bool $language_required
   *
   * @return mixed|null
   *   Field value.
   */
  protected function getPersonCommonField(string $field, bool $language_required = FALSE) {
    $tmdb_id = $this->entity->{'tmdb_id'}->value;
    $lang = $language_required ? Language::memberByValue($this->entity->language()
      ->getId()) : Language::en();

    if ($person = $this->adapter->getPerson($tmdb_id, $lang)) {
      return $person[$field] ?? NULL;
    }

    return NULL;
  }

}
