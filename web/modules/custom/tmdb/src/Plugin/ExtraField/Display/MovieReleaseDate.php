<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use DateTime;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "movie_release_date",
 *   label = @Translation("Extra: Movie release date"),
 *   bundles = {"node.movie"}
 * )
 */
class MovieReleaseDate extends ExtraTmdbFieldDisplayBase {

  private ?DateFormatter $date_formatter;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->date_formatter = $container->get('date.formatter');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($release_date = $this->getCommonFieldValue('release_date')) {
      try {
        $obj = new DateTime($release_date);
        $output = $this->date_formatter->format($obj->getTimestamp(), 'custom', 'd F Y');

        $build = [
          '#theme' => 'field_with_label',
          '#label' => $this->t('release date'),
          '#content' => $output,
        ];
      } catch (Exception $e) {
        return $build;
      }
    }

    return $build;
  }

}
