<?php

namespace Drupal\tmdb\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\imdb\DateHelper;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\NnmHelper;
use Drupal\tmdb\TmdbApiAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NnmController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  private ?EntityStorageInterface $node_storage;

  private ?LanguageManager $language_manager;

  private ?TmdbApiAdapter $adapter;

  private ?DateHelper $date_helper;

  private ?NnmHelper $nnm;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): NnmController {
    $instance = new static();

    $entity_type_manager = $container->get('entity_type.manager');

    $instance->node_storage = $entity_type_manager->getStorage('node');
    $instance->language_manager = $container->get('language_manager');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->date_helper = $container->get('date_helper');
    $instance->nnm = $container->get('tmdb.nnm_helper');

    return $instance;
  }

  public function getTable(int $nid): array {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->node_storage->load($nid);

    // Search on "nnm" in English if English is currently active, or Russian
    // for others.
    $lang = $this->language_manager->getCurrentLanguage()
      ->getId() === 'en' ? 'en' : 'ru';
    $node = $node->getTranslation($lang);

    $search_string = preg_replace('/[^a-zа-я\d\s-]/iu', '', $node->getTitle());

    switch ($node->bundle()) {
      case NodeBundle::movie:
        if ($release_date = $this->adapter->getCommonFieldValue($node, 'release_date')) {
          $search_string .= ' ' . $this->date_helper->dateStringToYear($release_date);
        }
        break;

      case NodeBundle::tv:
        if ($start_date = $this->adapter->getCommonFieldValue($node, 'first_air_date')) {
          $search_string .= ' ' . $this->date_helper->dateStringToYear($start_date);
        }
        break;
    }

    if ($rows = $this->nnm->getAllTorrentsData($search_string)) {
      $header = [
        'title' => $this->t('Title', [], ['context' => 'nnm']),
        'size' => $this->t('Size', [], ['context' => 'nnm']),
        'seeders' => $this->t('Seeders', [], ['context' => 'nnm']),
        'more' => $this->t('Get torrent/magnet', [], ['context' => 'nnm']),
      ];

      foreach ($rows as $topic_id => &$row) {
        unset($row['topic_id']);

        $row['more'] = Link::createFromRoute(
          $this->t('Load', [], ['context' => 'nnm']),
          'tmdb.nnm-magnet',
          ['topic_id' => $topic_id],
          ['attributes' => ['data-topic-id' => $topic_id]]
        )->toString();
      }

      return [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attached' => ['library' => ['tmdb/nnm']],
      ];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('No results. You can also try again on other languages.', [], ['context' => 'nnm']),
      '#attributes' => [
        'class' => ['nnm-sheet-empty-message'],
      ],
    ];
  }

  public function getTorrentMagnet(int $topic_id): AjaxResponse {
    $response = new AjaxResponse();

    if ($response_data = $this->nnm->getTorrentMagnet($topic_id)) {
      $response->setData($response_data);
    }

    return $response;
  }

}
