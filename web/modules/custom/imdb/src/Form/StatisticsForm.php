<?php

namespace Drupal\imdb\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\mvs\EntityFinder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StatisticsForm extends FormBase {

  private ?FileSystem $file_system;

  private ?Settings $settings;

  private ?EntityFinder $finder;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): StatisticsForm {
    $instance = parent::create($container);

    $instance->file_system = $container->get('file_system');
    $instance->settings = $container->get('settings');
    $instance->finder = $container->get('entity_finder');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'imdb.statistics_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['important'] = [
      '#type' => 'details',
      '#title' => $this->t('Other important settings'),
      '#open' => TRUE,
    ];
    // Private file system.
    $private_system = $this->settings::get('file_private_path');
    $form['important']['private_system'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private file system exists and directory is readable.'),
      '#default_value' => $private_system && $this->file_system->prepareDirectory($private_system, NULL),
      '#disabled' => TRUE,
    ];

    /**
     * Statistics.
     */
    // Statistics fieldset.
    $form['statistics'] = [
      '#type' => 'details',
      '#title' => $this->t('Statistics'),
      '#open' => TRUE,
    ];
    // Nodes count.
    $form['statistics']['nodes_count'] = [
      '#type' => 'item',
      '#markup' => $this->t('Nodes count: %count.', [
        '%count' => $this->finder->findNodes()->count()->execute(),
      ]),
    ];
    // Approved nodes count.
    $form['statistics']['approved_nodes_count'] = [
      '#type' => 'item',
      '#markup' => $this->t('Approved nodes count: %count.', [
        '%count' => $this->finder->findNodes()
          ->addCondition('field_approved', TRUE)
          ->count()
          ->execute(),
      ]),
    ];
    // Movies count.
    $form['statistics']['movies_count'] = [
      '#type' => 'item',
      '#markup' => $this->t('Movies count: %count.', [
        '%count' => $this->finder->findNodesMovie()->count()->execute(),
      ]),
    ];
    // TV count.
    $form['statistics']['tv_count'] = [
      '#type' => 'item',
      '#markup' => $this->t('TV count: %count.', [
        '%count' => $this->finder->findNodesTv()->count()->execute(),
      ]),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
