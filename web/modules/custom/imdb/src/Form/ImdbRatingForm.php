<?php

namespace Drupal\imdb\Form;

use Drupal;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\imdb\Manager\ImdbRatingDbManager;
use Drupal\imdb\Manager\ImdbRatingFileManager;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_chunk;

class ImdbRatingForm extends FormBase {

  private ?ImdbRatingDbManager $ratingDbManager;

  private ?ImdbRatingFileManager $ratingFileManager;

  private ?State $state;

  private ?DateFormatter $dateFormatter;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): ImdbRatingForm {
    $instance = parent::create($container);

    $instance->ratingDbManager = $container->get('imdb.rating.manager.db');
    $instance->ratingFileManager = $container->get('imdb.rating.manager.file');
    $instance->state = $container->get('state');
    $instance->dateFormatter = $container->get('date.formatter');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'imdb.rating_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Info'),
    ];

    if ($timestamp = $this->state->get('imdb.rating.last_update')) {
      $last_update_date = $this->dateFormatter->format($timestamp, 'long');
    }
    else {
      $last_update_date = $this->t('Never');
    }

    $form['info']['last_updated'] = [
      '#type' => 'item',
      '#title' => $this->t('Last update'),
      '#markup' => $last_update_date,
    ];
    $form['info']['items'] = [
      '#type' => 'item',
      '#title' => $this->t('Items in DB'),
      '#markup' => $this->ratingDbManager->getRatingsCount(),
    ];

    $form['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh ratings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Truncate table with IMDb ratings first.
    $this->ratingDbManager->clear();
    // Refresh the file with ratings.
    try {
      $this->ratingFileManager->refresh();
    }
    catch (Exception $e) {
      $this->logger('imdb_rating')->error($e->getMessage());
      return;
    }

    // Create queue.
    $operations = [];

    $imdb_ids = Drupal::database()
      ->select('node__field_imdb_id', 'n')
      ->fields('n', ['field_imdb_id_value'])
      ->execute()
      ->fetchCol();

    foreach (array_chunk($imdb_ids, 500) as $chunk) {
      $operations[] = ['imdb_rating_insert_batch', [$chunk]];
    }

    $batch = [
      'title' => $this->t('Saving IMDB ratings into DB ...'),
      'operations' => $operations,
      'finished' => 'imdb_rating_insert_batch_finished',
    ];
    batch_set($batch);
  }

}
