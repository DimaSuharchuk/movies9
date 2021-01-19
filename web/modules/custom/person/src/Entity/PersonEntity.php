<?php

namespace Drupal\person\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Person entity.
 *
 * @ingroup person
 *
 * @ContentEntityType(
 *   id = "person",
 *   label = @Translation("Person"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\person\PersonEntityListBuilder",
 *     "views_data" = "Drupal\person\Entity\PersonEntityViewsData",
 *     "translation" = "Drupal\person\PersonEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\person\Form\PersonEntityForm",
 *       "add" = "Drupal\person\Form\PersonEntityForm",
 *       "edit" = "Drupal\person\Form\PersonEntityForm",
 *       "delete" = "Drupal\person\Form\PersonEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\person\PersonEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\person\PersonEntityAccessControlHandler",
 *   },
 *   base_table = "person",
 *   data_table = "person_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer person entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/person/{person}",
 *     "add-form" = "/admin/structure/person/add",
 *     "edit-form" = "/admin/structure/person/{person}/edit",
 *     "delete-form" = "/admin/structure/person/{person}/delete",
 *     "collection" = "/admin/structure/person",
 *   },
 *   field_ui_base_route = "person.settings"
 * )
 */
class PersonEntity extends ContentEntityBase implements PersonEntityInterface {

  use EntityPublishedTrait;


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['tmdb_id'] = BaseFieldDefinition::create('integer')
      ->setLabel('TMDb ID')
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel('Full name')
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setTranslatable(TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
      ]);

    return $fields;
  }

}
