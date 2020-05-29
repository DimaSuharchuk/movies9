<?php

namespace Drupal\imdb\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\imdb\NodeHelper;
use Drupal\node\Entity\Node;
use Drupal\node\NodeViewBuilder;
use Drupal\tmdb\enum\TmdbLocalStorageType;
use Drupal\tmdb\SeasonBuilder;
use Drupal\tmdb\TmdbApiAdapter;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NodeController implements ContainerInjectionInterface {

  private ?NodeHelper $node_helper;

  private ?TmdbApiAdapter $adapter;

  private ?LanguageManagerInterface $language_manager;

  private ?TmdbTeaser $tmdb_teaser;

  private NodeViewBuilder $node_builder;

  private ?SeasonBuilder $season_builder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();

    $instance->node_helper = $container->get('node_helper');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->tmdb_teaser = $container->get('tmdb.tmdb_teaser');
    $instance->language_manager = $container->get('language_manager');
    $instance->node_builder = $container->get('entity_type.manager')
      ->getViewBuilder('node');
    $instance->season_builder = $container->get('tmdb.season_builder');

    return $instance;
  }


  /**
   * Find node by TMDb ID or create if node doesn't exist and redirect to the
   * node.
   *
   * @param $bundle
   *   NodeBundle type in string format.
   * @param int $tmdb_id
   *   TMDb ID.
   *
   * @return RedirectResponse
   *
   * @see NodeBundle
   */
  public function redirect($bundle, $tmdb_id): ?RedirectResponse {
    if ($node_id = $this
      ->node_helper
      ->prepareNodeOnAllLanguages(NodeBundle::memberByValue($bundle), $tmdb_id)) {
      return new RedirectResponse(
        Url::fromRoute('entity.node.canonical', ['node' => $node_id])
          ->toString()
      );
    }

    return NULL;
  }

  /**
   * Replace block "js-replaceable-block" with content of some tab context.
   *
   * @param $node_id
   *   Node ID.
   * @param $tab
   *   Name of tab. It must be the same as existing node view mode.
   *
   * @return AjaxResponse
   */
  public function nodeTabsAjaxHandler($node_id, $tab): AjaxResponse {
    $node = Node::load($node_id);

    $response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand(
        '#js-replaceable-block',
        $this->node_builder->view($node, $tab)
      )
    );

    return $response;
  }

  /**
   * Replace block "js-replaceable-block" with season + episodes.
   *
   * @param $node_id
   *   Node bundle "tv" ID.
   * @param $season
   *   Season number.
   *
   * @return AjaxResponse
   */
  public function seasonTabsAjaxHandler($node_id, $season): AjaxResponse {
    $node = Node::load($node_id);

    $langcode = $this->language_manager->getCurrentLanguage()->getId();
    $lang = Language::memberByValue($langcode);

    $response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand(
        '#js-replaceable-block',
        $this->season_builder->buildSeason($node, $season, $lang)
      )
    );

    return $response;
  }

  /**
   * @param $nid
   * @param $page
   *
   * @return AjaxResponse
   *
   * @see RorS
   */
  public function recommendations($nid, $page): AjaxResponse {
    return $this->RorS($nid, TmdbLocalStorageType::recommendations(), $page);
  }

  /**
   * @param $nid
   * @param $page
   *
   * @return AjaxResponse
   *
   * @see RorS
   */
  public function similar($nid, $page): AjaxResponse {
    return $this->RorS($nid, TmdbLocalStorageType::similar(), $page);
  }

  /**
   * Load TMDb teasers of "recommendations" or "similar" nodes and attach them
   * via AJAX to same set of previous "TMDb results page" on page where AJAX
   * link placed with css class "more-button-wrapper".
   *
   * @param int $node_id
   *   Node ID.
   * @param TmdbLocalStorageType $storage_type
   * @param int $page
   *   TMDb results page. It used in "recommendations" and "similar" TMDb
   *   responses.
   *
   * @return AjaxResponse
   */
  private function RorS(int $node_id, TmdbLocalStorageType $storage_type, int $page): AjaxResponse {
    $node = Node::load($node_id);

    $bundle = NodeBundle::memberByValue($node->bundle());
    $tmdb_id = $node->{'field_tmdb_id'}->value;
    $langcode = $this->language_manager->getCurrentLanguage()->getId();
    $lang = Language::memberByValue($langcode);

    // Get recommendations or similar teasers from TMDb API or TMDbLocalStorage.
    $r = TmdbLocalStorageType::recommendations() === $storage_type
      ? $this->adapter->getRecommendations($bundle, $tmdb_id, $lang, $page)
      : $this->adapter->getSimilar($bundle, $tmdb_id, $lang, $page);

    // Build attachable TMDb teasers for appending to previous page teasers.
    $attachable_teasers = $this->tmdb_teaser->buildAttachableTmdbTeasers(
      $storage_type,
      $node_id,
      $r['results'],
      $bundle,
      $lang,
      $page,
      $r['total_pages'] > $page
    );

    $response = new AjaxResponse();
    // Remove button from previous (same like this) response.
    $response->addCommand(new RemoveCommand('.more-button-wrapper'));
    // Attach to previous page "prepend place".
    $response->addCommand(new AppendCommand('.append-place', $attachable_teasers));

    return $response;
  }

}
