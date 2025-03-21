(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customHighcharts = {
    attach: function (context, settings) {
      console.log(drupalSettings.highcharts_data);
      if (drupalSettings.highcharts_data) {
        Highcharts.chart('highcharts-container', drupalSettings.highcharts_data);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);