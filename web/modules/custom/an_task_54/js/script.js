/**
 * @file
 * Simple JavaScript file.
 */

(function ($, Drupal) {
  Drupal.behaviors.an_task_54 = {
    attach: function (context, settings) {
      $('main', context).once('an_task_54').each( function(){
        alert('Hello World!');
      })
    }
  }
})(jQuery, Drupal, drupalSettings);
