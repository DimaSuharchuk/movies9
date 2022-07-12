<?php

namespace Drupal\imdb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\mvs\Constant;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_imdb_id;

class ImdbIdsAddForm extends FormBase {

  private ?Settings $settings;

  private ?QueueFactory $queue;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): ImdbIdsAddForm {
    $instance = parent::create($container);

    $instance->settings = $container->get('settings');
    $instance->messenger = $container->get('messenger');
    $instance->queue = $container->get('queue');

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'imdb.ids_add_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get IMDb IDs from form.
    $input_ids = $form_state->getValue('imdb_ids');
    $input_ids = explode("\r\n", $input_ids);

    // Collect valid IDs.
    $imdb_ids = [];
    foreach ($input_ids as $input_id) {
      if (is_imdb_id($input_id)) {
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
