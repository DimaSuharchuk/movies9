<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\mvs\DateHelper;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ExtraFieldDisplay(
 *   id = "noname_club",
 *   label = @Translation("Extra: Noname Club"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class NonameClub extends ExtraTmdbFieldDisplayBase {

  private ?AccountProxyInterface $current_user;

  private ?DateHelper $date_helper;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->current_user = $container->get('current_user');
    $instance->date_helper = $container->get('date_helper');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($this->current_user->hasPermission('view nnm')) {
      // Search on "nnm" in English if English is currently active, or Russian
      // for others.
      $lang = $entity->language()->getId() === 'en' ? 'en' : 'ru';
      /** @var \Drupal\node\NodeInterface $node */
      $node = $entity->getTranslation($lang);

      $search_string = preg_replace('/[^a-zа-я\d\s-]/iu', '', $node->getTitle());

      switch ($entity->bundle()) {
        case NodeBundle::movie:
          if ($release_date = $this->getCommonFieldValue('release_date')) {
            $search_string .= ' ' . $this->date_helper->dateStringToYear($release_date);
          }
          break;

        case NodeBundle::tv:
          if ($start_date = $this->getCommonFieldValue('first_air_date')) {
            $search_string .= ' ' . $this->date_helper->dateStringToYear($start_date);
          }
          break;

      }

      $build = [
        '#type' => 'link',
        '#title' => 'torrent',
        '#url' => Url::fromUri(
          '//nnmclub.to/forum/tracker.php',
          [
            'query' => [
              'nm' => $search_string, // search string: "title + year"
              'o' => 10, // sort by Seeders
              's' => 2, // sorting DESC
              'sha' => 0, // disable Author column
              'shr' => 1, // enable Rating column
            ],
          ]
        ),
        '#attributes' => [
          'title' => $this->t('Go to nnm-club', [], ['context' => 'nnm']),
          'class' => [
            'noname-club-icon',
          ],
          'target' => '_blank',
        ],
      ];
    }

    return $build;
  }

}
