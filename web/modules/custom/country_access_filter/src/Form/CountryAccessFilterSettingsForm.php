<?php

namespace Drupal\country_access_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CountryAccessFilterSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return ['country_access_filter.settings'];
  }

  public function getFormId(): string {
    return 'country_access_filter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('country_access_filter.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable functionality'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['countries'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed countries'),
      '#description' => $this->t('Enter country codes (ISO 3166-1 alpha-2) separated by spaces.'),
      '#default_value' => $config->get('countries'),
      '#required' => TRUE,
    ];

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => $config->get('debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $countries = explode(' ', trim($form_state->getValue('countries')));

    foreach ($countries as $country_code) {
      if (!preg_match('/^[A-Z]{2}$/', $country_code)) {
        $form_state->setErrorByName('countries', $this->t('Invalid country code: %code. Please use ISO 3166-1 alpha-2 codes.', ['%code' => $country_code]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('country_access_filter.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('countries', trim($form_state->getValue('countries')))
      ->set('debug_mode', $form_state->getValue('debug_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
