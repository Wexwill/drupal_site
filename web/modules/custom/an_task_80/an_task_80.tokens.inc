<?php

/**
 * @file
 * File to manage tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;

/*
* implement hook_token_info()
*/
function an_task_80_token_info(){

  $types['custom'] = [
    'name' => t('Custom type'),
    'description' => t('Custom type grouping for tokens'),
  ];

  $custom['link_to_google'] = [
    'name' => t('Google link'),
    'description' => t('Contains link to google site'),
  ];

  return [
    'types' => $types,
    'tokens' => [
      'custom' => $custom,
    ],
  ];
}

/*
* implement hook_tokens()
*/
function an_task_80_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata){

  $replacements = [];
  if($type == 'custom') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'link_to_google':
          $replace = '<a href="https://www.google.com/">Link to Google</a>';
          $replacements[$original] = Markup::create($replace);
          break;
      }
    }
  }

  return $replacements;
}
