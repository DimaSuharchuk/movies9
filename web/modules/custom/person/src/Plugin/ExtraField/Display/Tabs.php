<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "person_tabs",
 *   label = @Translation("Extra: Tabs"),
 *   description = "",
 *   bundles = {"person.person"},
 *   css_class = "tabs"
 * )
 */
class Tabs extends ExtraTmdbFieldDisplayBase {

  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
          'tmdb/extra-tabs',
        ],
      ],
    ];

    // Check if there is content for each tab and add tabs for them.
    $build['filmography'] = $this->buildAjaxLink('filmography', 'Filmography');
    if ($this->getPersonCommonField('images')) {
      $build['gallery'] = $this->buildAjaxLink('gallery', 'Gallery');
    }

    return $build;
  }

  /**
   * Create an ajax link for tab.
   *
   * @param string $tab
   *   Tab name must be the same as node view mode.
   * @param string $link_title
   *   Name of the tab that will be displayed on the page.
   *
   * @return array
   *   Render an array of ajax link.
   */
  private function buildAjaxLink(string $tab, string $link_title): array {
    return [
      '#type' => 'link',
      '#title' => $this->t($link_title, [], ['context' => 'Extra tabs']),
      '#url' => Url::fromRoute('mvs.person_tabs_ajax_handler', [
        'person_id' => $this->entity->id(),
        'tab' => $tab,
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
    ];
  }

}
