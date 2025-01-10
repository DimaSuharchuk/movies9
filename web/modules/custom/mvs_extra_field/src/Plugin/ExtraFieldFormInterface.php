<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Extra Field Form plugins.
 */
interface ExtraFieldFormInterface extends PluginInspectionInterface {

  /**
   * Builds a renderable array for the field.
   *
   * @param array $form
   *   The entity form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Renderable array.
   */
  public function formElement(array &$form, FormStateInterface $form_state);

  /**
   * Stores the field's parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity that hosts the field.
   */
  public function setEntity(ContentEntityInterface $entity);

  /**
   * Returns the field's parent entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity that hosts the field.
   */
  public function getEntity();

  /**
   * Stores the entity form display.
   *
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display
   *   The entity form display holding the display options configured for the
   *   entity components.
   */
  public function setEntityFormDisplay(EntityFormDisplayInterface $display);

  /**
   * Returns the entity form display object of the field's host entity.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The entity view display object.
   */
  public function getEntityFormDisplay();

  /**
   * Stores the entity form mode.
   *
   * @param string $form_mode
   *   The form mode the entity is rendered in.
   */
  public function setFormMode($form_mode);

  /**
   * Returns the entity form mode of the field's host entity.
   *
   * @return string
   *   The form mode the field is being rendered in.
   */
  public function getFormMode();

}
