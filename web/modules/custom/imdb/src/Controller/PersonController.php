<?php

namespace Drupal\imdb\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Url;
use Drupal\imdb\NodeHelper;
use Drupal\person\Entity\PersonEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PersonController implements ContainerInjectionInterface {

  private ?NodeHelper $node_helper;

  private EntityViewBuilderInterface $builder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): PersonController {
    $instance = new static();

    $instance->node_helper = $container->get('node_helper');
    $instance->builder = $container
      ->get('entity_type.manager')
      ->getViewBuilder('person');

    return $instance;
  }

  /**
   * Find Person entity by its TMDb ID, or create one if it doesn't exist, and
   * redirect to it.
   *
   * @param $tmdb_id
   *   Person TMDb ID.
   *
   * @return RedirectResponse
   */
  public function redirect($tmdb_id): RedirectResponse {
    if (is_numeric($tmdb_id) && $entity_id = $this
        ->node_helper
        ->preparePerson($tmdb_id)) {
      return new RedirectResponse(
        Url::fromRoute('entity.person.canonical', ['person' => $entity_id])
          ->toString()
      );
    }

    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  /**
   * Replace block "js-replaceable-block" with content of some tab context.
   *
   * @param $person_id
   *   Person entity ID.
   * @param $tab
   *   Name of tab. It must be the same as existing person view mode.
   *
   * @return AjaxResponse
   */
  public function personTabsAjaxHandler($person_id, $tab): AjaxResponse {
    $person = PersonEntity::load($person_id);

    $response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand(
        '#js-replaceable-block',
        $this->builder->view($person, $tab)
      )
    );

    return $response;
  }

}
