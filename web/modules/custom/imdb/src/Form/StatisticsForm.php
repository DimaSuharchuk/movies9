<?php

namespace Drupal\imdb\Form;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\State;
use Drupal\imdb\EntityFinder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StatisticsForm extends FormBase {

  /**
   * @var FileSystem|object|null
   */
  private $file_system;

  /**
   * @var Settings|object|null
   */
  private $settings;

  /**
   * @var DateFormatter|object|null
   */
  private $date_formatter;

  /**
   * @var State|object|null
   */
  private $state;

  /**
   * @var EntityFinder|object|null
   */
  private $finder;

  /**
   * @var QueueFactory|object|null
   */
  private $queue;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->file_system = $container->get('file_system');
    $instance->settings = $container->get('settings');
    $instance->date_formatter = $container->get('date.formatter');
    $instance->state = $container->get('state');
    $instance->queue = $container->get('queue');
    $instance->finder = $container->get('entity_finder');

    return $instance;
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'imdb.statistics_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 0,
    ];
    $form['actions']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear TMDb queue'),
    ];


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
    // Cron info.
    $form['important']['cron'] = [
      '#type' => 'item',
      '#markup' => $this->t('Cron last run: %time ago.', [
        '%time' => $this->date_formatter->formatTimeDiffSince($this->state->get('system.cron_last')),
      ]),
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

    // TMDb Queue.
    $form['statistics']['node_save_queue'] = [
      '#type' => 'item',
      '#markup' => $this->t('Untreated Node saving queue items: %count.', [
        '%count' => $this->queue->get(ImdbIdsAddForm::NODE_SAVE_WORKER_ID)
          ->numberOfItems(),
      ]),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->queue->get(ImdbIdsAddForm::NODE_SAVE_WORKER_ID)->deleteQueue();
  }

}
