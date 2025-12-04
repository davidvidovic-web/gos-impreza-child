/**
 * Main JavaScript Entry Point
 * Loads global scripts and utilities
 */

(function($) {
  'use strict';

  // Initialize global utilities
  const ImprezaChild = {
    init() {
      this.initTheme();
      console.log('Impreza Child Theme initialized');
    },

    initTheme() {
      // Add any global theme initialization here
      // This runs on all pages
    }
  };

  // Initialize on document ready
  $(document).ready(function() {
    ImprezaChild.init();
  });

})(jQuery);
