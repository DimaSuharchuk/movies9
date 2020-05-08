<?php

namespace Drupal\tmdb\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\node\Entity\Node;
use Drupal\tmdb\TmdbAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ExtraTmdbFieldDisplayBase extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * @var TmdbAdapter|object|null
   */
  private $adapter;


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
      return [
        '#theme' => 'tmdb_field',
        '#content' => $build,
        '#css_class' => $this->getPluginId(),
      ];
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
   * Fetch and return value from TMDb API or cached file.
   *
   * @param $field_name
   *   Name of field in TMDb API.
   *
   * @return mixed
   *   Field value.
   */
  public function getFieldValue($field_name) {
    $bundle = NodeBundle::memberByValue($this->entity->bundle());
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::memberByValue($this->entity->language()->getId());

    return $this->adapter->getFieldValue($bundle, $tmdb_id, $lang, $field_name);
  }

  /**
   * @return Node[]|null
   * @see TmdbAdapter::getMovieCollectionItems()
   */
  public function getMovieCollection() {
    $tmdb_id = $this->entity->{'field_tmdb_id'}->value;
    $lang = Language::memberByValue($this->entity->language()->getId());

    return $this->adapter->getMovieCollectionItems($tmdb_id, $lang);
  }

}
