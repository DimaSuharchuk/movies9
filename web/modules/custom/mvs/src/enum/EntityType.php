<?php

namespace Drupal\mvs\enum;

enum EntityType: string {

  case node = 'node';
  case term = 'taxonomy_term';
  case person = 'person';

}
