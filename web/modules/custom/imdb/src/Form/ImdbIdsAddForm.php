<?php

namespace Drupal\imdb\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_imdb_id;

class ImdbIdsAddForm extends FormBase {

  private ?Settings $settings;

  private Connection $db;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): ImdbIdsAddForm {
    $instance = parent::create($container);

    $instance->settings = $container->get('settings');
    $instance->messenger = $container->get('messenger');
    $instance->db = $container->get('database');

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
      $query = $this->db->select('node_revision__field_imdb_id', 'i')
        ->fields('i', ['field_imdb_id_value'])
        ->condition('i.field_imdb_id_value', $imdb_ids, 'IN')
        ->condition('a.field_approved_value', 1);
      $query->leftJoin('node__field_approved', 'a', 'a.entity_id = i.entity_id');
      $imdb_ids_in_db = $query->execute()->fetchCol();
      $new_imdb_ids = \array_diff($imdb_ids, $imdb_ids_in_db);

      if ($new_imdb_ids) {
        $operations = [];

        foreach (\array_unique($new_imdb_ids) as $imdb_id) {
          $operations[] = ['imdb_nodes_insert_batch', [$imdb_id]];
        }

        $batch = [
          'title' => $this->t('Adding movies to the site ...'),
          'operations' => $operations,
          'finished' => 'imdb_nodes_insert_batch_finished',
        ];
        batch_set($batch);
      }
    }
  }

}
