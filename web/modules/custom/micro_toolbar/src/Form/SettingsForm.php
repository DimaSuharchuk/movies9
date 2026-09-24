<?php

namespace Drupal\micro_toolbar\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the toolbar display mode.
 */
final class SettingsForm extends FormBase {

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The site state.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('state'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'micro_toolbar_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['display_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display mode'),
      '#options' => [
        'icons_and_labels' => $this->t('Icons and labels'),
        'icons_only' => $this->t('Icons only'),
      ],
      '#default_value' => $this->state->get('micro_toolbar.display_mode', 'icons_and_labels'),
      '#description' => $this->t('Icon-only links keep their labels available to screen readers and on hover.'),
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->state->set('micro_toolbar.display_mode', $form_state->getValue('display_mode'));
    $this->cacheTagsInvalidator->invalidateTags(['micro_toolbar:settings']);
    $this->messenger()->addStatus($this->t('The settings have been saved.'));
  }

}
