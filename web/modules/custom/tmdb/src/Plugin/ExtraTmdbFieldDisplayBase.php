<?php

namespace Drupal\tmdb\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
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
  public function view(ContentEntityInterface $entity) {
    if ($build = $this->build($entity)) {
      // Wrap every TMDb extra field with this wrapper.
      $build = [
        '#theme' => 'tmdb_field',
        '#content' => $build,
        '#css_class' => $this->getPluginId(),
      ];

      // Wrap some fields with theme "replaceable_field" for AJAX tabs.
      $d = $this->getPluginDefinition();
      if (isset($d['replaceable']) && $d['replaceable'] === TRUE) {
        $build = [
          '#theme' => 'replaceable_field',
          '#content' => $build,
        ];
      }

      return $build;
    }

    return NULL;
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
    $bundle = NodeBundle::memberByValue($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::memberByValue($this->entity->language()->getId());

    if ($common = $this->adapter->getCommonFieldsByTmdbId($bundle, $tmdb_id, $lang)) {
      return $common[$field_name] ?? NULL;
    }
    return NULL;
  }

}
