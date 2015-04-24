(function () {
  
  // We add our "own" jQuery version, in our own variable to the DOM.
  // This way, we minimize the interference between several versions
  // and frameworks!
  
  var replace = false;
  
  if (window.jQuery) {
    // Save the old jQuery version.
    var $oldQuery = window.jQuery;
    replace = true;
  }

  {jQueryGoesHere}

  window.$krexxQuery = jQuery.noConflict();

  if (replace) {
    // Restore the old jQuery version.
    window.jQuery = $oldQuery;
  }
})();