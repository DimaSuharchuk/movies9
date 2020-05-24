<?php

namespace Drupal\imdb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\imdb\Constant;
use Drupal\imdb\IMDbHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImdbIdsAddForm extends FormBase {

  /**
   * @var Settings|object|null
   */
  private $settings;

  /**
   * @var IMDbHelper|object|null
   */
  private $imdb_helper;

  /**
   * @var QueueFactory|object|null
   */
  private $queue;


  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->settings = $container->get('settings');
    $instance->messenger = $container->get('messenger');
    $instance->queue = $container->get('queue');
    $instance->imdb_helper = $container->get('imdb.helper');

    return $instance;
  }


  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'imdb.ids_add_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check API keys.
    $disabled = FALSE;
    if (!$this->settings::get('tmdb_api_key')) {
      $this->messenger->addError($this->t('TMDb API key is not defined.'));
      $disabled = TRUE;
    }
    if (!$this->settings::get('omdb_api_key')) {
      $this->messenger->addError($this->t('OMDb API key is not defined.'));
      $disabled = TRUE;
    }

    $form['imdb_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add IMDb IDs'),
      '#required' => TRUE,
      '#disabled' => $disabled,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 0,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#disabled' => $disabled,
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get IMDb IDs from form.
    $input_ids = $form_state->getValue('imdb_ids');
    $input_ids = explode("\r\n", $input_ids);

    // Collect valid IDs.
    $imdb_ids = [];
    foreach ($input_ids as $input_id) {
      if ($this->imdb_helper->isImdbId($input_id)) {
        $imdb_ids[] = $input_id;
      }
    }

    if ($imdb_ids) {
      $q = $this->queue->get(Constant::NODE_SAVE_WORKER_ID);
      foreach ($imdb_ids as $imdb_id) {
        $q->createItem(['imdb_id' => $imdb_id]);
      }
    }
  }

}
