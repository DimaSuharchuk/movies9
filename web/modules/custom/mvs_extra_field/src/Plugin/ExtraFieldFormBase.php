<?php

namespace Drupal\mvs_extra_field\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Extra field Form plugins.
 */
abstract class ExtraFieldFormBase extends PluginBase implements ExtraFieldFormInterface {

  use StringTranslationTrait;

  /**
   * The field's parent entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The form mode.
   *
   * @var string
   */
  protected $formMode;

  /**
   * The entity form display.
   *
   * Contains the form display options configured for the entity components.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entityFormDisplay;

  /**
   * {@inheritdoc}
   */
  public function setEntity(ContentEntityInterface $entity) {

    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityFormDisplay(EntityFormDisplayInterface $display) {

    $this->entityFormDisplay = $display;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFormDisplay() {

    return $this->entityFormDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormMode($form_mode) {

    $this->formMode = $form_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormMode() {

    return $this->formMode;
  }

}
