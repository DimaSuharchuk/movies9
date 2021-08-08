<?php

namespace Drupal\conditional_delete\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntitiesSelectForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager|null
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager|null
   */
  protected $fieldManager;

  /**
   * @var array
   */
  protected $bundleInfo;

  /**
   * @var ContentEntityTypeInterface[]
   */
  protected $entityTypesDefinitions;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): EntitiesSelectForm {
    $instance = parent::create($container);

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->bundleInfo = $container->get('entity_type.bundle.info')
      ->getAllBundleInfo();
    $instance->fieldManager = $container->get('entity_field.manager');

    $types = $instance->entityTypeManager->getDefinitions();
    // Filter content types.
    $instance->entityTypesDefinitions = array_filter($types, function ($type) {
      return $type instanceof ContentEntityTypeInterface;
    });

    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'conditional_delete_entities_select_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="entities-select-form-wrapper">';
    $form['#suffix'] = '</div>';

    $this->buildFiltersTable($form, $form_state);

    $this->buildFiltersKit($form, $form_state);

    // @todo Select batch or cron.

    $form['#attached']['library'][] = 'conditional_delete/form';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    // Prepare batch for deleting or prepare Drupal queue.
  }

  public function callbackOkButton(array $form, FormStateInterface $form_state) {
    // Save filters to form storage.
    $storage = $form_state->getStorage();
    $entity_type = $form_state->getValue('entity_type');
    $bundle = $form_state->getValue('bundle');
    $field = $form_state->getValue('field');
    $condition_value = $form_state->getValue('condition_value');
    $condition_operator = $form_state->getValue('condition_operator');
    $condition_invert = $form_state->getValue('condition_invert');
    $storage[] = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'field' => $field,
      'condition_value' => $condition_value,
      'condition_operator' => $condition_operator,
      'condition_invert' => $condition_invert,
    ];
    $form_state->setStorage(array_values($storage));
    // @todo Show how many entities (count) should be deleted.

    // Reset form by clear "entity_type" value.
    $form_state->setValues(array_merge($form_state->getValues(), ['entity_type' => NULL]));
    $form_state->setRebuild(TRUE);
  }

  public function callbackRemoveFiltersTableRow(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue('filters_table');
    $row_key = NULL;
    foreach ($values as $key => $value) {
      if (is_string($value)) {
        $row_key = $key;
        break;
      }
    }
    if (!is_null($row_key)) {
      // Remove the row from storage will remove row from the table.
      $storage = $form_state->getStorage();
      unset($storage[$row_key]);
      $form_state->setStorage(array_values($storage));
    }

    $form_state->setRebuild(TRUE);
  }

  public function ajaxForm(array $form): array {
    return $form;
  }

  public function ajaxFiltersKit(array $form): array {
    return $form['filters_kit'];
  }

  public function ajaxFiltersTable(array $form): array {
    return $form['filters_table_wrapper'];
  }

  private function buildFiltersTable(array &$form, FormStateInterface $form_state): void {
    $form['filters_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'filters-table-wrapper',
      ],
    ];
    $storage = $form_state->getStorage();
    $this->attachFiltersTable($form['filters_table_wrapper'], $storage);
    // Check for any rows in table and show the buttons.
    $storage && $this->attachFiltersTableButtons($form['filters_table_wrapper']);
  }

  private function attachFiltersTable(array &$parent, array $storage_rows): void {
    $header = [
      'entity_type' => 'Entity type',
      'bundle' => 'Bundle',
      'field' => 'Field',
      'condition' => 'Condition',
      'count' => 'Entities count',
      'remove' => 'Remove filter',
    ];

    // @todo Refactor this.
    $renderer = \Drupal::service('renderer');
    $button = [
      '#type' => 'button',
      '#value' => '❌',
      '#attributes' => [
        'class' => ['remove-filters-table-row'],
      ],
    ];
    $remove_button = $renderer->render($button);

    $rows = [];
    foreach ($storage_rows as $row) {
      $rows[] = [
        'entity_type' => $row['entity_type'],
        'bundle' => $row['bundle'] ?: '*',
        'field' => $row['field'] ?: '*',
        'condition' => '*', // @todo Describe conditions.
        'count' => 9999, // @todo Real count.
        'remove' => $remove_button,
      ];
    }

    $parent['filters_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No conditions added yet.'),
    ];

  }

  private function attachFiltersTableButtons(array &$parent): void {
    $parent['actions'] = ['#type' => 'actions'];
    $parent['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete entities'),
    ];
    // Hidden button for removing rows.
    $parent['actions']['remove_filters_table_row'] = [
      '#type' => 'submit',
      '#value' => '❌',
      '#submit' => ['::callbackRemoveFiltersTableRow'],
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
      '#ajax' => [
        'wrapper' => 'filters-table-wrapper',
        /* @see ajaxFiltersTable() */
        'callback' => '::ajaxFiltersTable',
      ],
    ];
  }

  private function buildFiltersKit(array &$form, FormStateInterface $form_state) {
    $this->attachFiltersKit($form);
    // Entity type.
    $entity_type = $form_state->getValue('entity_type');
    $this->attachEntityTypes($form['filters_kit'], $entity_type);
    // Bundle.
    $this->attachBundles($form['filters_kit'], $entity_type);
    // Field.
    $bundle = $form_state->getValue('bundle');
    $this->attachFields($form['filters_kit'], $entity_type, $bundle);
    // Condition.
    $field = $form_state->getValue('field');
    $this->attachConditions($form['filters_kit'], $entity_type, $bundle, $field);
    // Actions.
    $entity_type && $this->attachFiltersKitButtons($form['filters_kit']);
  }

  private function attachFiltersKit(array &$parent): void {
    $parent['filters_kit'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'filters-kit',
      ],
    ];
  }

  private function attachEntityTypes(array &$parent, string $chosen_entity_type = NULL): void {
    $options = [];
    foreach ($this->entityTypesDefinitions as $entity_type_name => $definition) {
      $options[$entity_type_name] = $definition->getLabel();
    }
    $parent['entity_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Entity type'),
      '#options' => $options,
      '#disabled' => !!$chosen_entity_type,
      '#default_value' => $chosen_entity_type,
      '#ajax' => [
        'wrapper' => 'filters-kit',
        /* @see ajaxFiltersKit() */
        'callback' => '::ajaxFiltersKit',
      ],
    ];
  }

  private function attachBundles(array &$parent, string $entity_type = NULL): void {
    if ($entity_type) {
      $options = [
        '' => $this->t('- Any -'),
      ];
      foreach ($this->bundleInfo[$entity_type] as $bundle_name => $bundle_definition) {
        $options[$bundle_name] = $bundle_definition['label'];
      }
      $parent['bundle'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select a specific bundle or "any".'),
        '#options' => $options,
        '#default_value' => '',
        '#ajax' => [
          'wrapper' => 'filters-kit',
          /* @see ajaxFiltersKit() */
          'callback' => '::ajaxFiltersKit',
        ],
      ];
    }
  }

  private function attachFields(array &$parent, string $entity_type = NULL, string $bundle = NULL): void {
    if (!($entity_type && $bundle)) {
      return;
    }

    // @todo We need to add the ability to select conditions on several fields at the same time. For example: unpublished nodes + older than some date of their creation.

    $fields = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
    $fields = array_filter($fields, function ($field) {
      return $field instanceof FieldConfig;
    });

    $allowed_field_types = [
      'boolean',
      'integer',
      'string',
      'entity_reference',
    ];
    $options = [
      '' => $this->t('- Any -'),
    ];
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    foreach ($fields as $field_definition) {
      $field_type = $field_definition->getType();
      if (in_array($field_type, $allowed_field_types)) {
        $machine_name = $field_definition->getName();
        $options[$machine_name] = "{$field_definition->getLabel()} ($machine_name)";
      }
    }
    $parent['field'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select field (optional)'),
      '#options' => $options,
      '#default_value' => '',
      '#ajax' => [
        'wrapper' => 'filters-kit',
        /* @see ajaxFiltersKit() */
        'callback' => '::ajaxFiltersKit',
      ],
    ];
  }

  /**
   * @param array $parent
   * @param string|null $entity_type
   * @param string|null $bundle
   * @param string|null $field
   */
  private function attachConditions(
    array  &$parent,
    string $entity_type = NULL,
    string $bundle = NULL,
    string $field = NULL
  ): void {
    if (!($entity_type && $bundle && $field)) {
      return;
    }

    $parent['condition_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditions'),
      '#attributes' => [
        'class' => ['conditions-kit'],
      ],
    ];

    $field_definition = $this->fieldManager->getFieldDefinitions($entity_type, $bundle)[$field];
    $field_type = $field_definition->getType();
    switch ($field_type) {
      case 'boolean':
        $parent['condition_wrapper']['condition_value'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Checked'),
        ];
        break;

      case 'integer':
        $parent['condition_wrapper']['invert'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Invert'),
          '#default_value' => FALSE,
        ];
        $parent['condition_wrapper']['operator'] = [
          '#type' => 'select',
          '#options' => [
            '=',
            '<',
            '>',
          ],
          '#default_value' => '=',
        ];
        $parent['condition_wrapper']['condition_value'] = [
          '#type' => 'number',
        ];
        break;

      case 'string':
        $parent['condition_wrapper']['invert'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Invert'),
          '#default_value' => FALSE,
        ];
        $parent['condition_wrapper']['operator'] = [
          '#type' => 'select',
          '#options' => [
            '=',
            '%LIKE',
            'LIKE%',
            '%LIKE%',
          ],
          '#default_value' => '=',
        ];
        $parent['condition_wrapper']['condition_value'] = [
          '#type' => 'textfield',
        ];
        break;

      case 'entity_reference':
        $field_settings = $field_definition->getSettings();
        $target_type = $field_settings['target_type'];
        $target_bundles = $field_settings['handler_settings']['target_bundles'];
        try {
          $storage = $this->entityTypeManager->getStorage($target_type);
          $bundle_key = $this->entityTypesDefinitions[$target_type]->getKey('bundle');
          $references = $storage->loadByProperties([
            $bundle_key => $target_bundles,
          ]);
          $options = [];
          foreach ($references as $reference) {
            $options[$reference->id()] = $reference->label();
          }
          if ($options) {
            $parent['condition_wrapper']['condition_value'] = [
              '#type' => 'checkboxes',
              '#title' => $this->t('Referenced entities'),
              '#options' => $options,
            ];
          }
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        }
        break;
    }
  }

  private function attachFiltersKitButtons(array &$parent) {
    $parent['actions'] = [
      '#type' => 'actions',
    ];
    $this->attachOkButton($parent['actions']);
  }

  private function attachOkButton(array &$parent) {
    $parent['ok'] = [
      '#type' => 'submit',
      '#value' => '✅',
      '#submit' => ['::callbackOkButton'],
      '#ajax' => [
        'wrapper' => 'entities-select-form-wrapper',
        /* @see ajaxForm() */
        'callback' => '::ajaxForm',
      ],
    ];
  }

}
