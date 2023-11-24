<?php

namespace Drupal\mvs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\mvs\EntityFinder;
use Drupal\mvs\EntityHelper;
use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\node\Entity\Node;
use Drupal\node\NodeViewBuilder;
use Drupal\tmdb\enum\TmdbLocalStorageType;
use Drupal\tmdb\SeasonBuilder;
use Drupal\tmdb\TmdbApiAdapter;
use Drupal\tmdb\TmdbTeaser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NodeController implements ContainerInjectionInterface {

  private ?EntityHelper $entity_helper;

  private ?TmdbApiAdapter $adapter;

  private ?EntityFinder $finder;

  private ?LanguageManagerInterface $language_manager;

  private ?TmdbTeaser $tmdb_teaser;

  private NodeViewBuilder $node_builder;

  private ?SeasonBuilder $season_builder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): NodeController {
    $instance = new static();

    $instance->entity_helper = $container->get('entity_helper');
    $instance->adapter = $container->get('tmdb.adapter');
    $instance->finder = $container->get('entity_finder');
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
   * @param int|string $tmdb_id
   *   TMDb ID.
   *
   * @return RedirectResponse|null
   *
   * @see NodeBundle
   */
  public function redirect($bundle, int|string $tmdb_id): ?RedirectResponse {
    if (
      ($node_bundle = NodeBundle::tryFrom($bundle))
      && is_numeric($tmdb_id)
      && ($node_id = $this->entity_helper->prepareNode($node_bundle, $tmdb_id))
    ) {
      return new RedirectResponse(
        Url::fromRoute('entity.node.canonical', ['node' => $node_id])
          ->toString()
      );
    }

    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  /**
   * Find approved random node and redirects to it.
   *
   * @return RedirectResponse
   */
  public function random(): RedirectResponse {
    // Get all approved nodes.
    $node_ids = $this->finder
      ->findNodes()
      ->addCondition('field_approved', TRUE)
      ->execute();
    // Get one random node.
    $random = array_rand($node_ids);
    // Redirect to it.
    return new RedirectResponse(
      Url::fromRoute('entity.node.canonical', ['node' => $random])->toString()
    );
  }

  /**
   * Replace block "js-replaceable-block" with content of some tab context.
   *
   * @param int|string $node_id
   *   Node ID.
   * @param string $tab
   *   Name of tab. It must be the same as existing node view mode.
   *
   * @return AjaxResponse
   */
  public function nodeTabsAjaxHandler(int|string $node_id, string $tab): AjaxResponse {
    $response = new AjaxResponse();

    if ($node = Node::load($node_id)) {
      $response->addCommand(
        new ReplaceCommand(
          '#js-replaceable-block',
          $this->node_builder->view($node, $tab)
        )
      );
    }
    else {
      $response->setStatusCode(404);
    }

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
    $response = new AjaxResponse();

    if ($node = Node::load($node_id)) {
      $langcode = $this->language_manager->getCurrentLanguage()->getId();
      $lang = Language::from($langcode);

      $response->addCommand(
        new HtmlCommand(
          '#js-replaceable-block',
          $this->season_builder->buildSeason($node, $season, $lang)
        )
      );
    }
    else {
      $response->setStatusCode(404);
    }

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
    return $this->RorS($nid, TmdbLocalStorageType::recommendations, $page);
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
    return $this->RorS($nid, TmdbLocalStorageType::similar, $page);
  }

  /**
   * Load TMDb teasers of "recommendations" or "similar" nodes and attach them
   * via AJAX to the same set of previous "TMDb results page" on page where the
   * AJAX link placed with css class "more-button-wrapper".
   *
   * @param int $node_id
   *   Node ID.
   * @param TmdbLocalStorageType $storage_type
   * @param int $page
   *   TMDb results page.
   *   It is used in "recommendations" and "similar" TMDb responses.
   *
   * @return AjaxResponse
   */
  private function RorS(int $node_id, TmdbLocalStorageType $storage_type, int $page): AjaxResponse {
    $response = new AjaxResponse();

    if (!$node = Node::load($node_id)) {
      return $response;
    }

    $bundle = NodeBundle::from($node->bundle());
    $tmdb_id = $node->{'field_tmdb_id'}->value;
    $lang = Language::from(
      $this->language_manager->getCurrentLanguage()->getId()
    );

    // Get recommendations or similar teasers from TMDb API or TMDbLocalStorage.
    $r = TmdbLocalStorageType::recommendations === $storage_type
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

    // Remove button from previous (same like this) response.
    $response->addCommand(new RemoveCommand('.more-button-wrapper'));
    // Attach to the previous page "prepend place".
    $response->addCommand(new AppendCommand('.append-place', $attachable_teasers));

    return $response;
  }

  /**
   * Returns "movie" or "tv" title on English.
   *
   * @param string $bundle
   * @param int $tmdb_id
   *
   * @return AjaxResponse
   */
  public function nodeOriginalTitle(string $bundle, int $tmdb_id): AjaxResponse {
    $common = $this->adapter->getCommonFieldsByTmdbId(NodeBundle::tryFrom($bundle), $tmdb_id, Language::en);

    return new AjaxResponse($common['title'] ?? NULL);
  }

  /**
   * Returns the TV's season title on English.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   *
   * @return AjaxResponse
   */
  public function seasonOriginalTitle(int $tv_tmdb_id, int $season_number): AjaxResponse {
    $season = $this->adapter->getSeason($tv_tmdb_id, $season_number, Language::en);

    return new AjaxResponse($season['title'] ?? NULL);
  }

  /**
   * Returns the episode title on English.
   *
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   *
   * @return AjaxResponse
   */
  public function episodeOriginalTitle(int $tv_tmdb_id, int $season_number, int $episode_number): AjaxResponse {
    if ($season = $this->adapter->getSeason($tv_tmdb_id, $season_number, Language::en)) {
      // Search for an episode.
      foreach ($season['episodes'] as $episode) {
        if ($episode['episode_number'] == $episode_number) {
          return new AjaxResponse($episode['name']);
        }
      }
    }

    return new AjaxResponse();
  }

}
