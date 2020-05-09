<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\PersonAvatar;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "crew",
 *   label = @Translation("Extra: Crew"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Crew extends ExtraTmdbFieldDisplayBase {

  use PersonAvatar;

  /**
   * @var ModuleHandler|object|null
   */
  private $module_handler;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->module_handler = $container->get('module_handler');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($collection = $this->getFieldValue('crew')) {
      $build = [
        '#theme' => 'collection',
        '#items' => $this->buildItems($collection['crew']),
      ];
      if ($entity->bundle() === NodeBundle::tv) {
        $build['#title'] = $this->t('Crew');
      }
    }

    return $build;
  }


  /**
   * @param array $persons
   *   Persons from TMDb API for render.
   *
   * @return array
   *   Renderable arrays of crew persons.
   */
  private function buildItems(array $persons) {
    $build = [];

    // Sort persons at first.
    $this->sortByJob($persons);

    foreach ($persons as $person) {
      $build[] = [
        '#theme' => 'person',
        '#avatar' => $this->getThemedAvatar($person, TmdbImageFormat::w185()),
        '#name' => $person['name'],
        '#department' => $person['department'],
        '#job' => $person['job'],
      ];
    }

    return $build;
  }

  /**
   * Sort persons by department and job of department.
   *
   * @param array $persons
   */
  private function sortByJob(array &$persons): void {
    $list = $this->sortedJobsList();

    usort($persons, function ($a, $b) use ($list) {
      if ($a['department'] === $b['department']) {
        $position_a_job = array_search($a['job'], $list['jobs']);
        $position_b_job = array_search($b['job'], $list['jobs']);
        return $position_a_job <=> $position_b_job;
      }
      $position_a_dep = array_search($a['department'], $list['departments']);
      $position_b_dep = array_search($b['department'], $list['departments']);
      return $position_a_dep <=> $position_b_dep;
    });
  }

  /**
   * Get sorted departments and jobs from file.
   *
   * @return array
   */
  private function sortedJobsList() {
    $module = $this->module_handler->getModule('tmdb')->getPath();
    $yml = file_get_contents("{$module}/files/departments.yaml");
    $departments = Yaml::decode($yml);

    return [
      'departments' => array_keys($departments),
      'jobs' => call_user_func_array('array_merge', $departments),
    ];
  }

}
