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

  // We might have several jQuery versions present, so we must make sure to use the right one.
  var $krexxQuery = jQuery.noConflict();

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
        $content.offset({top : event.pageY + offSetY, left : event.pageX + offSetX})
      });
      $content.on("mouseup", function () {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();
        // Unregister to prevent slowdown.
        $krexxQuery(document).off("mousemove");
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
    $krexxQuery('.Krexx-wrapper').draXX('.Krexx-headnote');

    /*
     * Register functions for the local dev-settings.
     *
     * @event change
     *   Changes on the krexx html forms.
     *   All changes will automatically be written to the browser cookies.
     */
    $krexxQuery('.Krexx-editable select, .Krexx-editable input').on("change", function (event) {
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
    $krexxQuery('.resetbutton').on('click', function (event) {
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
    $krexxQuery('.Krexx-close').on('click', function (event) {
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
    $krexxQuery('.Krexx-expand').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.toggle(this);
    });

    /*
     * Register the jumping and highlighting for recursions.
     *
     * @event click
     *   When a recursion is clicked, krexx tries to locate the
     *   first output of the object and highlight it.
     */
    $krexxQuery('.krexx-jumpTo').on('click', function (event) {
      // Prevents the default event behavior (ie: click).
      event.preventDefault();
      // Prevents the event from propagating (ie: "bubbling").
      event.stopPropagation();

      krexx.jumpTo(this)
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
    var $nests = $krexxQuery('#' + domId).parents('.Krexx-nest');
    var $expandableElement = $krexxQuery('#' + domId).prev();
    var $container;

    if ($expandableElement.length) {
      // Show them, we need to expand them all.
      $nests.show();
      $nests.prev().addClass('Krexx-opened');

      // Highlight it.
      $expandableElement.addClass('Krexx-highlight');
      // Register a function to un-highlight it after 5 seconds.
      $expandableElement.delay(5000).queue(function (next) {
        $expandableElement.removeClass('Krexx-highlight');
        next();
      });
      // The mainproblem here is, I might have 2 different container:
      // '.Krexx-wrapper' in case of the error handler or
      // 'html, body' in case of a normal output.
      $container = $krexxQuery('.Krexx-wrapper');
      // Fatal errorhandler scrolling.
      if ($container.length > 0) {
        $container.animate({
          scrollTop : $expandableElement.offset().top - $container.offset().top + $container.scrollTop()
        }, 500);
      }
      // Normal scrolling.
      $container = $krexxQuery('html, body');
      if ($container.length > 0) {
        $container.animate({
          scrollTop : $expandableElement.offset().top
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
   * Shows a "fast" closing animation and the nremoves the krexx window from the markup.
   *
   * @param {HTMLElement} el
   *   The closing button.
   */
  krexx.close = function (el) {
    // Remove it nice and "slow".
    var $instance = $krexxQuery('#' + $krexxQuery(el).data('instance'));

    $instance.parent('Krexx-headnote').off('mousedown');

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
    $krexxQuery('.Krexx-wrapper .editable').children().prop('disabled', true);
    $krexxQuery('.Krexx-wrapper .resetbutton').prop('disabled', true);
  };
})();
