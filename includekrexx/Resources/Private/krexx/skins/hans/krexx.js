/**
 * @file
 * Template js functions for kreXX.
 *
 * This is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @license http://opensource.org/licenses/LGPL-2.1 GNU Lesser General Public License Version 2.1
 * @package Krexx
 */

(function () {
  "use strict";
  
  if(!window.$krexxQuery) {
    // Huh, our own jQuery version is not here, we should take the one from the hosting CMS.
    var $krexxQuery = window.jQuery;
  }
  else {
    var $krexxQuery = window.$krexxQuery;
  }

  /*
   * Register our jQuery draggable plugin.
   *
   * @param {string} handle
   *   The jQuery selector for the handle (the element where you click
   *   ans pull the "window".
   */
  $krexxQuery.fn.draXX = function (handle) {
    var selector = this.selector;
    var $handle = this.find(handle);

    /*
     * @param {event} event
     *   The mousedown event from the pulling of the handle.
     *
     * @event mousedown
     *   The mousedown event from the pulling of the handle.
     */
    return $handle.on("mousedown", function (event) {
      var $content = $krexxQuery(this).parents(selector);

      // Calculate original offset.
      var offSetY = $content.offset().top + $content.outerHeight() - event.pageY - $content.outerHeight();
      var offSetX = $content.offset().left + $content.outerWidth() - event.pageX - $content.outerWidth();

      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      /*
       * @param {event} event
       *   The mousemove event from the pulling of the handle.
       *
       * @event mousemove
       *   The actual dragging of the handle.
       */
      $krexxQuery(document).on("mousemove", function (event) {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();
        $content.offset({top : event.pageY + offSetY, left : event.pageX + offSetX});
        
        // The next line is not part of the draXX plugin. You should
        // remove it, in case you want to use draXX.
        $krexxQuery('.search-wrapper').css('position', 'absolute');
      });
      $content.on("mouseup", function () {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();
        // Unregister to prevent slowdown.
        $krexxQuery(document).off("mousemove");
        
        // The next line is not part of the draXX plugin. You should
        // remove it, in case you want to use draXX.
        $krexxQuery('.search-wrapper').css('position', 'fixed');
      });
    });
  };

  /*
   * Register the frontend functions.
   *
   * @event onDocumentReady
   *   All events are getting registered as soon as the
   *   document is complete.
   */
  $krexxQuery(document).ready(function () {

    // Initialize the draggable.
    $krexxQuery('.kwrapper').draXX('.kheadnote');

    /*
     * Register functions for the local dev-settings.
     *
     * @event change
     *   Changes on the krexx html forms.
     *   All changes will automatically be written to the browser cookies.
     */
    $krexxQuery('.kwrapper .keditable select, .kwrapper .keditable input').on("change", function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.setSetting(this);
    });

    /*
     * Register cookie reset function on the reset button.
     *
     * @event click
     *   Resets the local settings in the settings cookie,
     *   when the reset button ic clicked.
     */
    $krexxQuery('.kwrapper .resetbutton').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.resetSetting();
    });

    /*
     * Register krexx close button function.
     *
     * @event click
     *   Displayes a closing animation of the corresponding
     *   krexx output "window" and then removes it from the markup.
     */
    $krexxQuery('.kwrapper .kversion .kclose').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.close(this);
    });

    /*
     * Register toggeling to the elements.
     *
     * @event click
     *   Expands a krexx node when it is not expanded.
     *   When it is already expanded, it closes it.
     */
    $krexxQuery('.kwrapper .kexpand').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.toggle(this);
    });

    /*
     * Register the recursions resolving.
     *
     * @event click
     *   When a recursion is clicked, krexx tries to locate the
     *   first output of the object and highlight it.
     */
    $krexxQuery('.kwrapper .kcopyFrom').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.copyFrom(this);
    });
    
    /*
     * Register the displaying of the search menu
     * 
     * @event click
     *   When the button is clicked, krexx will display the
     *   seach menu associated this the same output window.
     */
    $krexxQuery('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();
      
      krexx.displaySearch(this);
    });
    
    /*
     * Register the seach event on the next button.
     * 
     * @event click
     *   When the button is clicked, krexx will strart searching.
     */
    $krexxQuery('.kwrapper .ksearchnow').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();
      
      krexx.performSearch(this);
    });
    
    /*
     * Litsens for a <RETURN> in the searchfield.
     * 
     * @event keyup
     *   A <RETURN> will initiate the search.
     */
    $krexxQuery('.kwrapper .ksearchfield').keyup(function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();
      
      // If this is no <RETURN> key, do nothing.
      if (event.which != 13) {
        return;
      }
      
      // Initiate the search.
      $krexxQuery('.kwrapper .ksearchnow').click();
      
    });
    
    $krexxQuery('.kwrapper .kcollapse-me').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();
      
      krexx.collapse(this);
    });

    // Disable form-buttons in case a logfile is opened local.
    if (window.location.protocol === 'file:') {
      krexx.disableForms();
    }
  });

  /**
   * kreXX JS Class.
   *
   * @namespace
   *   It a just a collection of used js routines.
   */
  function krexx() {}
  
  /**
   * When clicked on s recusrion, this function will
   * copy the original analysis result there and delete
   * the recursion.
   * 
   * @param {HTMLElement} el
   *   The recursion display
   */
  krexx.copyFrom = function(el) {
    // Get the DOM id of the original analysis.
    var domid = $krexxQuery(el).data('domid');
    // Get the analysis data.
    var $orgNest = $krexxQuery('#' + domid);
    // Does the element exist?
    if ($orgNest.length > 0){
      // Get the EL of the data (element with the arrow).
      var $orgEl = $orgNest.prev();
      // Get the old recursion EL.
      var $el = $krexxQuery(el);
      // Clone the analysis data and insert it after the recursion EL.
      $orgNest.clone(true, true).insertAfter(el);
      // Clone the EL of the analysis data and insert it after the recursion EL.
      var $newEl = $orgEl.clone(true, true).insertAfter(el);
      // Change the key of the just cloned EL to the one from the recursion.
      $newEl.children('.kname').html($el.children('.kname').html());
      // Remove the recursion EL.
      $el.remove();
    }
  };
  
  /**
   * Collapses elements for a breadcrumb
   * 
   * Hides all other elements, exept the one with
   * the button. This way, we can get a breadcrumb
   * to the element we want to look at.
   * 
   * @param {HTMLElement} el
   *   The collapse button
   */
  krexx.collapse = function(el) {
    var $button = $krexxQuery(el);
    
    // Remove all old classes.
    $krexxQuery('.kfilterroot').removeClass('kfilterroot');
    $krexxQuery('.krootline').removeClass('krootline');
    $krexxQuery('.ktopline').removeClass('ktopline');
    
    // Here we start the hiding, only when clicked on a
    // none-colapsed button.
    if (!$button.hasClass('collapsed')) {
      $button.parents('div.kbg-wrapper > ul').addClass('kfilterroot');
      // Add the "rootline to all elements between the button and the filterroot
      $button.parents('ul.knode, li.kchild').addClass('krootline');
      // Add the "topline" to the hightest element in the rootline
      $button.closest('.krootline').addClass('ktopline');
      // Reset the old collapse button.
      $krexxQuery('.collapsed').removeClass('collapsed');
      // Highlight the new collapse button.
      $button.addClass('collapsed');
    }
    else {
      // Reset the button, since we are un-collapsing nodes here.
      $krexxQuery('.collapsed').removeClass('collapsed');
    }
  };
  
  /**
   * Here we save the seach results
   * 
   * This is multidimensional array:
   * results[kreXX-instance][seach text][search results]
   *                                    [pointer]
   * The [pointer] is the key of the [search result] where
   * you would jump to when you click "next" 
   * 
   * @var array
   */
  var results = [];
  
  /**
   * Inintiates the search.
   * 
   * The results are saved in the var results.
   * 
   * @param {HTMLElement} el
   *   The search button.
   */
  krexx.performSearch = function(el) {
    var $el = $krexxQuery(el);
    var searchtext = $el.prevAll('.ksearchfield').val();
    
    // we only search for more than 3 chars.
    if (searchtext.length > 3) {
    
      var instance = $el.data('instance');
      var direction = $el.data('direction');
      
      // We need to un-collapse everything, in case it it collpsed.
      $krexxQuery('#' + instance).find('.collapsed').click();
      
      // Are we already having some results?
      if (typeof results[instance] != "undefined") {
        if (typeof results[instance][searchtext] == "undefined") {
          // Remove all previous highlights
          $krexxQuery('.ksearch-found-highlight').removeClass('ksearch-found-highlight');
          // Get a new list of elements
          results[instance][searchtext] = [];
          results[instance][searchtext]['data'] = $krexxQuery('#' + instance).find("li span:contains('" + searchtext + "'), li div.kpreview:contains('" + searchtext + "')").toggleClass('ksearch-found-highlight');
          results[instance][searchtext]['pointer'] = -1;
        }
      }
      else {
        // Remove all previous highlights
        $krexxQuery('.ksearch-found-highlight').removeClass('ksearch-found-highlight');
        // Get a new list of elements
        results[instance] = [];
        results[instance][searchtext] = [];
        results[instance][searchtext]['data'] = $krexxQuery('#' + instance).find("li span:contains('" + searchtext + "'), li div.kpreview:contains('" + searchtext + "')").toggleClass('ksearch-found-highlight');
        results[instance][searchtext]['pointer'] = -1;
      }

      // Set the pointer to the next or previous element
      if (direction == 'forward') {
        results[instance][searchtext]['pointer']++;
      }
      else {
        results[instance][searchtext]['pointer']--;
      }

      // Do we have an element?
      if (typeof results[instance][searchtext]['data'][results[instance][searchtext]['pointer']] == "undefined") {
        if (direction == 'forward') {
          // There is no next element, we go back to the first one.
          results[instance][searchtext]['pointer'] = 0;
        }
        else {
          results[instance][searchtext]['pointer'] = results[instance][searchtext]['data'].length -1;
        }
      }
      
      // Now we simply jump to the element in the array.
      krexx.jumpTo(results[instance][searchtext]['data'][results[instance][searchtext]['pointer']]);
      
      // Feedback about where we are
      $el.prevAll('.ksearch-state').text(results[instance][searchtext]['pointer'] + ' / ' + (results[instance][searchtext]['data'].length -1));
    }
    else {
      $el.prevAll('.ksearch-state').text('<- must be bigger than 3 characters');
    }
  };
  
  /**
   * Display the search dialog
   *
   * @param {HTMLElement} el
   *   The butten which was pressed
   */
  krexx.displaySearch = function (el) {
    // Get the search menu.
    var $search = $krexxQuery('#search-' + $krexxQuery(el).data('instance'));
    // Toggle display / hidden.
    if ($search.hasClass('hidden')) {
      $search.removeClass('hidden');
      $search.css('position', 'fixed');
      $search.find('.ksearchfield').focus();
    }
    else {
      // Remove all previous highlights
      $krexxQuery('.ksearch-found-highlight').removeClass('ksearch-found-highlight');
      // $krexxQuery('.highlight-jumpto').removeClass('highlight-jumpto');
      results = [];
      $search.addClass('hidden');
      $search.css('position', 'absolute');
    }
  };

  /**
   * Add a CSS class to an HTML element.
   *
   * @param {HTMLElement} el
   *   The Element we want to reclass.
   * @param {string} className
   *   The classname we want to add.
   */
  krexx.reclass = function (el, className) {
    if (el.className.indexOf(className) < 0) {
      el.className += (' ' + className);
    }
  };

  /**
   * Remove a CSS class to an HTML element.
   *
   * @param {HTMLElement} el
   *   The Element we want to unclass.
   * @param {string} className
   *   The classname we want to remove.
   */
  krexx.unclass = function (el, className) {
    if (el.className.indexOf(className) > -1) {
      el.className = el.className.replace(className, '');
    }
  };

  /**
   * Hides or displayes the nest under an expandable element.
   *
   * @param {HTMLLiElement} el
   *   The Element you want to expand.
   */
  krexx.toggle = function (el) {
    
      $krexxQuery(el).toggleClass('kopened').next().toggleClass('khidden');

  };

  /**
   * "Jumps" to an element in the markup and highlights it.
   *
   * It is used when we are facing a recursion in our analysis.
   *
   * @param {HTMLElement} el
   *   The element you want to focus on.
   */
  krexx.jumpTo = function (el) {
    var domId = $krexxQuery(el).data('domid');
    
    if (typeof domId == "undefined") {
      // we have no DOM ID, so we jump to the element!
      var $nests = $krexxQuery(el).parents('.knest');
      var $expandableElement = $krexxQuery(el);
    }
    else {
      var $nests = $krexxQuery('#' + domId).parents('.knest');
      var $expandableElement = $krexxQuery('#' + domId).prev();
    }
    var $container;

    if ($expandableElement.length) {
      // Show them, we need to expand them all.
      $nests.removeClass('khidden');
      $nests.prev().addClass('kopened');

      // Remove old highlighting.
      $krexxQuery('.highlight-jumpto').removeClass('highlight-jumpto');
      // Highlight new one.
      $expandableElement.addClass('highlight-jumpto');
      // The mainproblem here is, I might have 2 different container:
      // '.kwrapper' in case of the error handler or
      // 'html, body' in case of a normal output.
      $container = $krexxQuery('.kfatalwrapper-outer');
      // Fatal errorhandler scrolling.
      if ($container.length > 0) {
        $container.animate({
          scrollTop : $expandableElement.offset().top - $container.offset().top + $container.scrollTop() -50
        }, 500);
      }
      // Normal scrolling.
      $container = $krexxQuery('html, body');
      if ($container.length > 0) {
        $container.animate({
          scrollTop : $expandableElement.offset().top -50
        }, 500);
      }
    }
  };

  /**
   * Reads the values from a cookie.
   *
   * @param {string} krexxDebugSettings
   *   Name of the cookie.
   *
   * @return string
   *   The value, set in the cookie.
   */
  krexx.readSettings = function (krexxDebugSettings) {
    var cookieName = krexxDebugSettings + "=";
    var cookieArray = document.cookie.split(';');
    var result = {};

    for (var i = 0; i < cookieArray.length; i++) {
      var c = cookieArray[i];
      while (c.charAt(0) === ' ') {
        c = c.substring(1, c.length);
      }
      if (c.indexOf(cookieName) === 0) {
        try {
          // Return json, if possible.
          result = JSON.parse(c.substring(cookieName.length, c.length));
        }
        catch (error) {
          // Return the value.
          result = c.substring(cookieName.length, c.length);
        }
      }
    }
    return result;
  };

  /**
   * Adds the value from a html element to the local cookie settings.
   *
   * @param {HTMLElement} el
   *   The html element with the value.
   */
  krexx.setSetting = function (el) {
    // Get the old value.
    var settings = krexx.readSettings('KrexxDebugSettings');

    // Get new settings from element.
    var $this = $krexxQuery(el);
    var newValue = $this.val();
    var valueName = $this.prop('name');
    settings[valueName] = newValue;

    // Save it.
    var date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    var expires = 'expires=' + date.toUTCString();
    // Remove a possible old value from a previous version.
    document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    // Set the new one.
    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
    // console.log(document.cookie);
    // Feedback about update.
    alert(valueName + ' --> ' + newValue + '\n\nPlease reload the page to use the new local settings.');
  };

  /**
   * Resets all values in the local cookie settings.
   */
  krexx.resetSetting = function () {
     // We do not delete the cookie, we simply remove all settings in it.
    var settings = {};
    var date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    var expires = 'expires=' + date.toUTCString();
    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';

    alert('All local configuration have been reset.\n\nPlease reload the page to use the global settings.');
  };

  /**
   * Shows a "fast" closing animation and then removes the krexx window from the markup.
   *
   * @param {HTMLElement} el
   *   The closing button.
   */
  krexx.close = function (el) {
    // Remove it nice and "slow".
    var $instance = $krexxQuery('#' + $krexxQuery(el).data('instance'));

    $instance.parent('.kheadnote').off('mousedown');

    $instance.hide("fast", function () {
      $instance.remove();
    });
  };

  /**
   * Disables the editing functions, when a krexx output is loaded as a file.
   *
   * These local settings would actually do
   * nothing at all, because they would land inside a cookie
   * for that file, and not for the server.
   */
  krexx.disableForms = function () {
    $krexxQuery('.kwrapper .editable').children().prop('disabled', true);
    $krexxQuery('.kwrapper .resetbutton').prop('disabled', true);
  };
})();
