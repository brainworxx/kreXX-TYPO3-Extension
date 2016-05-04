/**
 * @file
 *   Template js functions for kreXX.
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

(function (kdt) {
  "use strict";

  /**
   * Register the frontend functions.
   *
   * @event onDocumentReady
   *   All events are getting registered as soon as the
   *   document is complete.
   */
  document.addEventListener("DOMContentLoaded", function() {
    krexx.onDocumentReady();
  });

  /**
   * kreXX JS Class.
   *
   * @namespace
   *   It a just a collection of used js routines.
   */
  function krexx() {}

  /**
   * Executed on document ready
   *
   * @event documentready
   */
  krexx.onDocumentReady = function () {

    // Initialize the draggable.
    kdt.draXX(
      '.kwrapper',
      '.kheadnote',
      function() {
        var searchWrapper = document.querySelectorAll('.search-wrapper');
        for (var i = 0; i < searchWrapper.length; i++) {
          searchWrapper[i].style.position = 'fixed';
        }
      },
      function() {
        var searchWrapper = document.querySelectorAll('.search-wrapper');
        for (var i = 0; i < searchWrapper.length; i++) {
          searchWrapper[i].style.position = 'absolute';
        }
      }
    );

    /**
     * Register krexx close button function.
     *
     * @event click
     *   Displays a closing animation of the corresponding
     *   krexx output "window" and then removes it from the markup.
     */
    kdt.addEvent('.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose', 'click', krexx.close);

    /**
     * Register toggling to the elements.
     *
     * @event click
     *   Expands a krexx node when it is not expanded.
     *   When it is already expanded, it closes it.
     */
    kdt.addEvent('.kwrapper .kexpand', 'click', krexx.toggle);

    /**
     * Register the click on the tabs.
     *
     * @event click
     */
    kdt.addEvent('.ktool-tabs .ktab:not(.ksearchbutton)', 'click', krexx.switchTab);

    /**
     * Register functions for the local dev-settings.
     *
     * @event change
     *   Changes on the krexx html forms.
     *   All changes will automatically be written to the browser cookies.
     */
    kdt.addEvent('.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)', 'change', kdt.setSetting);

    /**
     * Register cookie reset function on the reset button.
     *
     * @event click
     *   Resets the local settings in the settings cookie,
     *   when the reset button ic clicked.
     */
    kdt.addEvent('.kwrapper .resetbutton', 'click', kdt.resetSetting);

    /**
     * Register the recursions resolving.
     *
     * @event click
     *   When a recursion is clicked, krexx tries to locate the
     *   first output of the object and highlight it.
     */
    kdt.addEvent('.kwrapper .kcopyFrom', 'click', krexx.copyFrom);

    /**
     * Register the displaying of the search menu
     *
     * @event click
     *   When the button is clicked, krexx will display the
     *   search menu associated this the same output window.
     */
    kdt.addEvent('.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose', 'click', krexx.displaySearch);

    /**
     * Register the search event on the next button.
     *
     * @event click
     *   When the button is clicked, krexx will start searching.
     */
    kdt.addEvent('.kwrapper .ksearchnow', 'click', krexx.performSearch);

    /**
     * Listens for a <RETURN> in the search field.
     *
     * @event keyup
     *   A <RETURN> will initiate the search.
     */
    kdt.addEvent('.kwrapper .ksearchfield', 'keyup', krexx.searchfieldReturn);

    /**
     * Register the Collapse-All funfions on it's symbol
     *
     * @event click
     */
    kdt.addEvent('.kwrapper .kcollapse-me', 'click', krexx.collapse);

    /**
     * Register the code generator on the P symbol.
     *
     * @event click
     */
    kdt.addEvent('.kwrapper .kgencode', 'click', krexx.generateCode);

    /**
     * Prevents the click-event-bubbling on the generated code.
     *
     * @event click
     */
    kdt.addEvent('.kcodedisplay', 'click', kdt.preventBubble);

    // Disable form-buttons in case a logfile is opened local.
    if (window.location.protocol === 'file:') {
      krexx.disableForms();
    }
  };

  /**
   * When clicked on s recursion, this function will
   * copy the original analysis result there and delete
   * the recursion.
   *
   * @param event
   */
  krexx.copyFrom = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var i;

    // Get the DOM id of the original analysis.
    var domid = kdt.getDataset(this, 'domid');
    // Get the analysis data.
    var orgNest = document.querySelector('#' + domid);

    // Does the element exist?
    if (orgNest) {
      // Get the EL of the data (element with the arrow).
      var orgEl = orgNest.previousElementSibling;
      // Clone the analysis data and insert it after the recursion EL.
      this.parentNode.insertBefore(orgNest.cloneNode(true), this.nextSibling);
      // Clone the EL of the analysis data and insert it after the recursion EL.
      var newEl = orgEl.cloneNode(true);
      this.parentNode.insertBefore(newEl, this.nextSibling);
      // Register the events on the new element.
      newEl.addEventListener('click', krexx.toggle);

      // The code generation may not be possible here, so we need to check this.
      var kgencode = newEl.querySelector('.kgencode');
      if (kgencode !== null) {
        kgencode.addEventListener('click', krexx.generateCode);
      }

      newEl.querySelector('.kcollapse-me').addEventListener('click', krexx.collapse);

      // Register the toggel function.
      var newExpand = newEl.nextElementSibling.querySelectorAll('.kexpand');
      for (i = 0; i < newExpand.length; i++) {
        newExpand[i].addEventListener('click', krexx.toggle);
      }
      // Register the Collapse function.
      var hideEverythingElse = newEl.nextElementSibling.querySelectorAll('.kcollapse-me');
      for (i = 0; i < hideEverythingElse.length; i++) {
        hideEverythingElse[i].addEventListener('click', krexx.collapse);
      }
      // Register the Code-Generation function.
      var codegen = newEl.nextElementSibling.querySelectorAll('.kgencode');
      for (i = 0; i < codegen.length; i++) {
        codegen[i].addEventListener('click', krexx.generateCode);
      }
      // Register the recursion resolving (this function) on possible recursions
      var recursions = newEl.nextElementSibling.querySelectorAll('.kwrapper .kcopyFrom');
       for (i = 0; i < recursions.length; i++) {
          recursions[i].addEventListener('click', krexx.copyFrom);
      }
      // Prevent the event bubbling on the code generation display.
      var codedisplay = newEl.nextElementSibling.querySelectorAll('.kwrapper .kcodedisplay');
      for (i = 0; i < codedisplay.length; i++) {
        codedisplay[i].addEventListener('click', kdt.preventBubble);
      }
      codedisplay = newEl.querySelectorAll('.kwrapper .kcodedisplay');
      for (i = 0; i < codedisplay.length; i++) {
        codedisplay[i].addEventListener('click', kdt.preventBubble);
      }

      // Change the key of the just cloned EL to the one from the recursion.
      kdt.findInDomlistByClass(newEl.children, 'kname').innerHTML = kdt.findInDomlistByClass(this.children, 'kname').innerHTML;
      // We  need to remove the ids from the copy to avoid double ids.
      var allChildren = newEl.nextElementSibling.getElementsByTagName("*");
      for (i = 0; i < allChildren.length; i++) {
        allChildren[i].removeAttribute('id');
      }
      newEl.nextElementSibling.removeAttribute('id');

      // Now we add the dom-id to the clone, as a data-field. this way we can
      // make sure to always produce the right path to this value during source
      // generation.
      kdt.setDataset(newEl.parentNode, 'domid', domid);

      // Remove the recursion EL.
      this.parentNode.removeChild(this);
    }

  };

  /**
   * Collapses elements for a breadcrumb
   *
   * Hides all other elements, except the one with
   * the button. This way, we can get a breadcrumb
   * to the element we want to look at.
   *
   * @param event
   */
  krexx.collapse = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var button = event.target;
    var wrapper = kdt.getParents(button, '.kwrapper')[0];

    // Remove all old classes within this debug "window"
    kdt.removeClass(wrapper.querySelectorAll('.kfilterroot'), 'kfilterroot');
    kdt.removeClass(wrapper.querySelectorAll('.krootline'), 'krootline');
    kdt.removeClass(wrapper.querySelectorAll('.ktopline'), 'ktopline');

    // Here we start the hiding, only when clicked on a
    // none-collapsed button.
    if(!kdt.hasClass(button, 'kcollapsed')) {
      kdt.addClass(kdt.getParents(button, 'div.kbg-wrapper > ul'), 'kfilterroot');
      // Add the "rootline" to all elements between the button and the filterroot
      kdt.addClass(kdt.getParents(button, 'ul.knode, li.kchild'), 'krootline');
      // Add the "topline" to the highest element in the rootline
      kdt.addClass([kdt.getParents(button, '.krootline')[0]], 'ktopline');
      // Reset the old collapse button.
      kdt.removeClass(wrapper.querySelectorAll('.kcollapsed'), 'kcollapsed');

      // Highlight the new collapse button.
      kdt.addClass([button], 'kcollapsed');
    }
    else {
      // Reset the button, since we are un-collapsing nodes here.
      kdt.removeClass('.kcollapsed', 'kcollapsed');
    }
  };

  /**
   * Here we save the search results
   *
   * This is multidimensional array:
   * results[kreXX-instance][search text][search results]
   *                                     [pointer]
   * The [pointer] is the key of the [search result] where
   * you would jump to when you click "next"
   */
  var results = [];

  /**
   * Initiates the search.
   *
   * The results are saved in the var results.
   *
   * @param event
   */
  krexx.performSearch = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var searchtext = this.parentNode.querySelector('.ksearchfield').value.toLowerCase();

    // we only search for more than 3 chars.
    if (searchtext.length > 2) {
      var instance = kdt.getDataset(this, 'instance') ;
      var direction = kdt.getDataset(this, 'direction');
      var payload =  document.querySelector('#' + instance + ' .kbg-wrapper');

      // We need to un-collapse everything, in case it it collapsed.
      var collapsed = payload.querySelectorAll('.kcollapsed');
      for (var i = 0; i < collapsed.length; i++) {
        kdt.trigger(collapsed[i], 'click');
      }

      // Are we already having some results?
      if (typeof results[instance] != "undefined") {
        if (typeof results[instance][searchtext] == "undefined") {
          refreshResultlist();
        }
      }
      else {
        refreshResultlist();
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
          results[instance][searchtext]['pointer'] = results[instance][searchtext]['data'].length - 1;
        }
      }

      // Feedback about where we are
      this.parentNode.querySelector('.ksearch-state').textContent = results[instance][searchtext]['pointer'] + ' / ' + (results[instance][searchtext]['data'].length - 1);
      // Now we simply jump to the element in the array.
      if (typeof results[instance][searchtext]['data'][results[instance][searchtext]['pointer']] !== 'undefined') {
        // We got another one!
        krexx.jumpTo(results[instance][searchtext]['data'][results[instance][searchtext]['pointer']]);
      }
    }
    else {
      // Not enough chars as a searchtext!
      this.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
    }

    /**
     * Resets our searchlist and fills it with results.
     */
    function refreshResultlist() {
      // Remove all previous highlights
      kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
      // Get a new list of elements
      results[instance] = [];
      results[instance][searchtext] = [];
      results[instance][searchtext]['data'] = [];
      // Poll out payload for elements to search
      var list = payload.querySelectorAll("li span, li div.kpreview");
      for (var i = 0; i < list.length; ++i) {
        // Does it contain our search string?
        if (list[i].textContent.toLowerCase().indexOf(searchtext) > -1) {
          kdt.toggleClass(list[i], 'ksearch-found-highlight');
          results[instance][searchtext]['data'].push(list[i]);
        }
      }
      // Reset our index.
      results[instance][searchtext]['pointer'] = -1;
    }


  };

  /**
   * Display the search dialog
   *
   * @param event
   */
  krexx.displaySearch = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = kdt.getDataset(this, 'instance');
    var search = document.querySelector('#search-' + instance);
    var searchtab = document.querySelector('#' + instance + ' .ksearchbutton');

    // Toggle display / hidden.
    if (kdt.hasClass(search, 'hidden')) {
      // Display it.
      kdt.toggleClass(search, 'hidden');
      search.querySelector('.ksearchfield').focus();
      search.style.position = 'fixed';
    }
    else {
      // Hide it.
      kdt.toggleClass(search, 'hidden');
      kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
      search.style.position = 'fixed';
      // Clear the results.
      results = [];
    }
  };

  /**
   * Hides or displays the nest under an expandable element.
   *
   * @param event
   */
  krexx.toggle = function (event) {
    // Prevents the default event behavior (ie: click).
    // event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    kdt.toggleClass(this, 'kopened');
    kdt.toggleClass(this.nextElementSibling, 'khidden');

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

    var nests = kdt.getParents(el, '.knest');
    var container;
    var destination;

    // Show them.
    kdt.removeClass(nests, 'khidden');
    // We need to expand them all.
    for (var i = 0; i < nests.length; i++) {
      kdt.addClass([nests[i].previousElementSibling], 'kopened');
    }

    // Remove old highlighting.
    kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
    // Highlight new one.
    kdt.addClass([el], 'highlight-jumpto');

    // Getting our scroll container
    container = document.querySelectorAll('.kfatalwrapper-outer');

    if (container.length == 0) {
      // Normal scrolling
      container = document.querySelectorAll('html');
      destination = el.getBoundingClientRect().top + container[0].scrollTop - 50;
    }
    else {
      // Fatal Error scrolling.
      destination = el.getBoundingClientRect().top - container[0].getBoundingClientRect().top + container[0].scrollTop - 50;
    }

    var diff = Math.abs(container[0].scrollTop - destination);

    if (container.length > 0) {
      var step;

      if (container[0].scrollTop < destination) {
        // Forward.
        step = Math.round(diff / 12);
      }
      else {
        // Backward.
        step = Math.round(diff / 12) * -1;
      }

      // We stop scrolling, since we have a new target;
      clearInterval(interval);
      // We also need to check if the setting of the new valkue was successful.
      var lastValue = container[0].scrollTop;
      var interval = setInterval(function() {
        container[0].scrollTop +=  step;
        if (Math.abs(container[0].scrollTop - destination) <= Math.abs(step) || container[0].scrollTop == lastValue) {
          // We are here now, the next step would take us too far.
          // So we jump there right now and then clear the interval.
          container[0].scrollTop = destination;
          clearInterval(interval);
        }
        lastValue = container[0].scrollTop;
      }, 1);
    }
  };

  /**
   * Shows a "fast" closing animation and then removes the krexx window from the markup.
   *
   * @param event
   */
  krexx.close = function (event) {

    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = kdt.getDataset(event.target, 'instance');
    var elInstance = document.querySelector('#' + instance);

    // Remove it nice and "slow".
    var opacity = 1;
    var interval = setInterval(function() {
      if (opacity < 0) {
        // It's invisible now, so we clear the timer and remove it from the DOM.
        clearInterval(interval);
        elInstance.parentNode.removeChild(elInstance);
        return;
      }
      opacity -= 0.1;
      elInstance.style.opacity = opacity;
    }, 20);
  };

  /**
   * Disables the editing functions, when a krexx output is loaded as a file.
   *
   * These local settings would actually do
   * nothing at all, because they would land inside a cookie
   * for that file, and not for the server.
   */
  krexx.disableForms = function () {
    var elements = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
    for (var i = 0; i < elements.length; i++) {
      elements[i].disabled = true;
    }
  };

  /**
   * The kreXX code generator.
   *
   * @event click
   * @param event
   */
  krexx.generateCode = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var codedisplay = event.target.nextElementSibling;
    var result = '';
    var sourcedata;
    var domid;
    // Get the first element
    var el = kdt.getParents(event.target, 'li.kchild')[0];


    // Start the loop to collect all the date
    while (el) {
      // Get the domid
      domid = kdt.getDataset(el, 'domid');
      sourcedata = kdt.getDataset(el, 'source');

      if (typeof sourcedata !== 'undefined' && sourcedata == '. . .') {
        if (typeof domid !== 'undefined') {
          // We need to get a new el, because we are facing a recursion, and the
          // current path is not really reachable.
          el = document.querySelector('#' + domid).parentNode;
          // Get the source, again.
          sourcedata = kdt.getDataset(el, 'source');
        }
      }

      // Recheck everything.
      if (typeof sourcedata !== 'undefined') {
        // We must check if our value is actually reachable.
        // '. . .' means it is not reachable,
        // we will stop right here and display a comment stating this.
        if (sourcedata == '. . .') {
          result = '// Value is either protected or private.<br /> // Sorry . . ';
          break;
        }
        else {
          // We're good, value can be reached!
          result = sourcedata + result;
        }
      }
      // Get the next el.
      el = kdt.getParents(el, 'li.kchild')[0];
    }

    // 3. Add the text
    codedisplay.innerHTML ='<div class="kcode-inner">' + result + ';</div>';
    if (codedisplay.style.display == 'none') {
      codedisplay.style.display = '';
      kdt.selectText(codedisplay);
    }
    else {
      codedisplay.style.display = 'none';
    }
  };

  /**
   * Sets the kactive on the clicked element and removes it from the others.
   *
   * @event click
   * @param event
   */
  krexx.switchTab = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    var instance = kdt.getDataset(this.parentNode, 'instance');
    var what = kdt.getDataset(this, 'what');

    // Toggle the highlighting.
    kdt.removeClass('#' + instance + ' .kactive:not(.ksearchbutton)', 'kactive');

    if (this.classList) {
      this.classList.add('kactive');
    }
    else {
      this.className += ' kactive';
    }

    // Toggle what is displayed
    kdt.addClass('#' + instance + ' .kpayload', 'khidden');
    kdt.removeClass('#' + instance + ' .' + what, 'khidden');
  };

  /**
   * Sets the max-height on the payload elements, depending on the viewport.
   *
   * @event document ready
   */
  krexx.setPayloadMaxHeight = function () {
    // Get the height.
    var height = Math.round(Math.max(document.documentElement.clientHeight, window.innerHeight || 0) * 0.60);

    if (height > 0) {
      var elements = document.querySelectorAll('.krela-wrapper .kpayload');
      for (var i = 0; i < elements.length; i++) {
        elements[i].style.maxHeight = height + 'px';
      }
    }
  };

  /**
   * Checks if the search form is inside the viewport. If not, fixes it on top.
   * Gets triggered on,y when scolling the fatel error handler.
   *
   * @event scroll
   */
  krexx.checkSeachInViewport = function (event) {
    // Get the search
    var search = document.querySelector('.kfatalwrapper-outer .search-wrapper');
    // Reset the inline styles
    search.style.position = '';
    search.style.top = '';

    // Measure it!
    var rect = search.getBoundingClientRect();
    if (rect.top < 0) {
      // Set it to the top
      search.style.position = 'fixed';
      search.style.top = '0px';
    }
  };

  /**
   * Listens for a <RETURN> in the search field.
   *
   * @event keyup
   * @param event
   */
  krexx.searchfieldReturn = function (event) {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // If this is no <RETURN> key, do nothing.
    if (event.which != 13) {
      return;
    }

    kdt.trigger(this.parentNode.querySelectorAll('.ksearchnow')[1], 'click');
  };

})(kreXXdomTools);
