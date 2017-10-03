/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

(function () {
    "use strict";

    /**
     * kreXX JS Class.
     *
     * @namespace kdt
     *   Collection of js functions.
     */
    function kdt() {}

    /**
     * Gets all parents of an element which has the specified class.
     *
     * @param {Element} el
     * @param {string} selector
     * @returns {Array}
     */
    kdt.getParents = function (el, selector) {
        /** @type {Array} */
        var result = [];
        /** @type {Node} */
        var parent = el.parentNode;

        while (parent !== null) {

            // Check for classname
            if (kdt.matches(parent, selector)) {
                result.push(parent);
            }
            // Get the next one.
            parent = parent.parentNode;
        }
        return result;

    };

    /**
     * X-Browser implementation of the matchesSelector.
     *
     * @param {Node} element
     * @param {string} selector
     * @return {*}
     */
    kdt.matches = function(element, selector) {
        return (
            // Normal implementation.
            element.matches ||
            // Whatever.
            element.matchesSelector ||
            // IE.
            element.msMatchesSelector ||
            // Firefox.
            element.mozMatchesSelector ||
            // Chrome.
            element.webkitMatchesSelector ||
            // Opera.
            element.oMatchesSelector ||
            function () {
                // Fallback to false, this is not the element you are lookking for.
                return false;
            }
        ).call(element, selector);
    };

    /**
     * Triggers an event on an element.
     *
     * @param {Element} el
     * @param {string} eventName
     */
    kdt.trigger = function (el, eventName) {
        /** @type {Event} */
        var event = document.createEvent('HTMLEvents');
        event.initEvent(eventName, true, false);
        el.dispatchEvent(event);
    };

    /**
     * Determines if an element has a class.
     *
     * @param {Node} el
     * @param {string} className
     * @returns {boolean}
     */
    kdt.hasClass = function (el, className) {
        if (el.classList) {
            return el.classList.contains(className);
        }
        else {
            return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
        }
    };

    /**
     * Gets the first element from a list which hat that class.
     *
     * @param {NodeList} elements
     * @param {string} className
     *
     * @returns {Element|null} the element
     */
    kdt.findInDomlistByClass = function (elements, className) {

        className = " " + className + " ";
        for (var i = 0; i < elements.length; i++) {
            if ((" " + elements[i].className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
                return elements[i];
            }
        }
        return null;
    };

    /**
     * Adds a class to elements.
     *
     * @param {NodeList|string|Array} selector
     * @param {string} className
     */
    kdt.addClass = function (selector, className) {
        /** @type {NodeList|null|Array} */
        var elements;

        if (typeof selector === 'string') {
            // Get our elements.
            elements = document.querySelectorAll(selector);
        }
        else {
            // We already have our list that we will use.
            elements = selector;
        }

        for (var i = 0; i < elements.length; i++) {
            if (elements[i].classList) {
                elements[i].classList.add(className);
            }
            else {
                elements[i].className += ' ' + className;
            }
        }
    };

    /**
     * Removes a class from elements
     *
     * @param {NodeList|string} selector
     * @param {string} className
     */
    kdt.removeClass = function (selector, className) {
        /** @type {NodeList|null} */
        var elements;

        if (typeof selector === 'string') {
            // Get our elements.

            elements = document.querySelectorAll(selector);
        }
        else {
            // We already have our list that we will use.
            elements = selector;
        }

        for (var i = 0; i < elements.length; i++) {
            if (elements[i].classList) {
                elements[i].classList.remove(className);
            }
            else {
                elements[i].className = elements[i].className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
            }
        }
    };

    /**
     * Toggles the class of an element
     *
     * @param {Element} el
     * @param {string} className
     */
    kdt.toggleClass = function (el, className) {
        if (el.classList) {
            // Just toggle it.
            el.classList.toggle(className);
        } else {
            // no class list there, we need to do this by hand.
            /** @type {Array} */
            var classes = el.className.split(' ');
            /** @type {number} */
            var existingIndex = classes.indexOf(className);

            if (existingIndex >= 0)
                classes.splice(existingIndex, 1);
            else
                classes.push(className);

            el.className = classes.join(' ');
        }
    };

    /**
     * Adds a event listener to a list of elements.
     *
     * @param {string} selector
     * @param {string} eventName
     * @param {Function} callBack
     *
     */
    kdt.addEvent = function (selector, eventName, callBack) {
        // We use the clickHandler instead.
        if (eventName === 'click') {
            kdt.clickHandler.add(selector, callBack);
        } else {
            /** @type {NodeList} */
            var elements = document.querySelectorAll(selector);

            for (var i = 0; i < elements.length; i++) {
                elements[i].addEventListener(eventName, callBack);
            }
        }
    };

    /**
     * Click handler for kreXX, to get out of the event-hell.
     */
    kdt.clickHandler =  {};

    /**
     * The storage object, with an array of callbacks behind it.
     *
     * @type {{}}
     */
    kdt.clickHandler.storage = {};

    /**
     *
     * @param {string} selector
     * @param {function} callback
     */
    kdt.clickHandler.add = function(selector, callback) {
        if (!(selector in kdt.clickHandler.storage)) {
            kdt.clickHandler.storage[selector] = [];
        }
        kdt.clickHandler.storage[selector].push(callback);
    };

    /**
     * Registers the eventhandler, prefereably on the kreXX instance window.
     *
     * @param selector
     */
    kdt.clickHandler.register = function(selector) {
        /** @type {NodeList} */
        var elements = document.querySelectorAll(selector);

        for (var i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', kdt.clickHandler.handle);
        }
    };

    /**
     * Whenever a click is bubbeled on a kreXX instance, we try to find
     * the according callback, and sinply call it.
     *
     * @param {Event} event
     */
    kdt.clickHandler.handle = function(event) {
        // We stop the event in it's tracks.
        event.stopPropagation();
        // event.preventDefault();

        var element = event.target;
        var finished = false;

        do {
            // We need to test the element on all selectors.
            for (var selector in kdt.clickHandler.storage) {
                if (kdt.matches(element, selector)) {
                    // Got to call them all.
                    for (var i = 0; i < kdt.clickHandler.storage[selector].length; i++) {
                        kdt.clickHandler.storage[selector][i](event, element);
                    }
                    // We did what we came for.
                    // Time to exit the while loop.
                    finished = true;
                }
            }

            if (finished) {
                // Exit the while.
                element = null;
            } else {
                // Time to test the parent.
                element = element.parentNode;
            }
        } while (element !== null);

    };

    /**
     * Gets the dataset from en element.
     *
     * @param {Element} el
     * @param {string} what
     * @param {boolean|null} mustEscape
     *
     * @returns {string|*}
     */
    kdt.getDataset = function (el, what, mustEscape) {

        /** @type {string|*} */
        var result;

        if (typeof el === 'undefined') {
            // No el, no data!
            return '';
        }

        if (typeof el.getAttribute !== 'function') {
            // No attribute, no data!
            return '';
        }

        result = el.getAttribute('data-' + what);

        if (result !== null) {
            if (mustEscape === false) {
                return result;
            } else {
                return result.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;")
                    // <small> is allowed. Parameters are better readable
                    // this way.
                    .replace('&lt;small&gt;', '<small>')
                    .replace('&lt;/small&gt;', '</small>');
            }
        }

        // Still here?!? No data fount, hence an empty string.
        return '';
    };

    /**
     * Sets the dataset from en element.
     *
     * @param {Element} el
     * @param {string} what
     * @param {string} value
     */
    kdt.setDataset = function (el, what, value) {
        if (typeof el !== 'undefined') {
            el.setAttribute('data-' + what, value);
        }
    };

    /**
     * Selects some text
     *
     * @see http://stackoverflow.com/questions/985272/selecting-text-in-an-element-akin-to-highlighting-with-your-mouse
     * @author Jason
     *
     * @param {Element} element
     */
    kdt.selectText = function (element) {

        /** @type {Range|TextRange} */
        var range;
        /** @type {Selection} */
        var selection;

        if (document.body.createTextRange) {
            range = document.body.createTextRange();
            range.moveToElementText(element);
            range.select();
        } else if (window.getSelection) {
            selection = window.getSelection();
            range = document.createRange();
            range.selectNodeContents(element);
            selection.removeAllRanges();
            selection.addRange(range);
        }
    };

    /**
     * Our dragable function (formerly a jQuery plugin)
     *
     * @param {string} selector
     * @param {string} handle
     * @param {Function} callbackUp
     * @param {Function} callbackDrag
     */
    kdt.draXX = function (selector, handle, callbackUp, callbackDrag) {

        kdt.addEvent(selector + ' ' + handle, 'mousedown', startDraxx);

        /**
         * Starts the dragging on a mousedown.
         *
         * @param {Event} event
         */
        function startDraxx(event) {

            // The selector has an ID, we only have one of them.
            /** @type {Element} */
            var elContent = kdt.getParents(this, selector)[0];
            /** @type {{top, left}|{top: number, left: number}} */
            var offset = getElementOffset(elContent);

            // Calculate original offset.
            /** @type {number} */
            var offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
            /** @type {number} */
            var offSetX = offset.left + outerWidth(elContent) - event.pageX - outerWidth(elContent);

            // We might need to add a special offset, in case that:
            // - body is position: relative;
            // and there are elements above that have
            // - margin: top or
            // - margin: bottom
            /** @type {CSSStyleDeclaration} */
            var bodyStyle = getComputedStyle(document.querySelector('body'));
            if (bodyStyle.position === 'relative') {

                /** @type {number} */
                var relOffsetY;
                /** @type {number} */
                var relOffsetX;

                // We need to check the body.
                relOffsetY = parseInt(bodyStyle.marginTop, 10);
                relOffsetX = parseInt(bodyStyle.marginLeft, 10);
                if (relOffsetY > 0) {
                    // We take the body offset.
                }
                else {
                    // We need to look for another offset.
                    // Now we need to get all elements above the current kreXX element and
                    // get their margins (top and button)
                    /** @type {Element} */
                    var prev = elContent.previousElementSibling;
                    do {
                        relOffsetY = parseInt(getComputedStyle(prev).marginTop, 10);
                        prev = prev.previousElementSibling;
                        // We will stop if we ran out of elements or if we have found the
                        // first offset.
                    } while (prev && relOffsetY === 0);
                }

                // Correct our initial offset.
                offSetY -= relOffsetY;
                offSetX -= relOffsetX;
            }

            // Prevents the default event behavior (ie: click).
            event.preventDefault();
            // Prevents the event from propagating (ie: "bubbling").
            event.stopPropagation();

            document.addEventListener("mousemove", drag);

            /**
             * Stops the dragging process
             *
             * @event mouseup
             */
            document.addEventListener("mouseup", function () {
                // Prevents the default event behavior (ie: click).
                event.preventDefault();
                // Prevents the event from propagating (ie: "bubbling").
                event.stopPropagation();
                // Unregister to prevent slowdown.
                document.removeEventListener("mousemove", drag);

                // Calling the callback for the mouseup.
                if (typeof  callbackUp === 'function') {
                    callbackUp();
                }
            });

            /**
             * Drags the DOM element around.
             *
             * @event mouseDown
             * @param {Event} event
             */
            function drag(event) {
                // Prevents the default event behavior (ie: click).
                event.preventDefault();
                // Prevents the event from propagating (ie: "bubbling").
                event.stopPropagation();

                /** @type {number} */
                var left = event.pageX + offSetX;
                /** @type {number} */
                var top = event.pageY + offSetY;

                elContent.style.left = left + "px";
                elContent.style.top = top + "px";

                // Calling the callback for the dragging.
                if (typeof  callbackDrag === 'function') {
                    callbackDrag();
                }
            }
        }

        /**
         * Gets the top and left offset of a DOM element.
         *
         * @param {Element} element
         *
         * @returns {{top: number, left: number}}
         */
        function getElementOffset(element) {
            /** @type {Element} */
            var de = document.documentElement;
            /** @type {ClientRect} */
            var box = element.getBoundingClientRect();
            /** @type {number} */
            var top = box.top + window.pageYOffset - de.clientTop;
            /** @type {number} */
            var left = box.left + window.pageXOffset - de.clientLeft;
            return {top: top, left: left};
        }

        /**
         * Gets the outer width of an element.
         *
         * @param {Element} el
         *
         * @returns {number}
         */
        function outerWidth(el) {
            /** @type {number} */
            var width = el.offsetWidth;
            /** @type {CSSStyleDeclaration} */
            var style = getComputedStyle(el);
            width += parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
            return width;
        }

    };

    /**
     * Reads the values from a cookie.
     *
     * @param {string} krexxDebugSettings
     *   Name of the cookie.
     *
     * @return {*}
     *   The value, set in the cookie.
     */
    kdt.readSettings = function (krexxDebugSettings) {
        /** @type {string} */
        var cookieName = krexxDebugSettings + "=";
        /** @type {Array} */
        var cookieArray = document.cookie.split(';');
        /** @type {*} */
        var result = {};

        for (var i = 0; i < cookieArray.length; i++) {
            /** @type {string} */
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
     * @param {Event} event
     */
    kdt.setSetting = function (event) {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();

        // Get the old value.
        /** @type {*} */
        var settings = kdt.readSettings('KrexxDebugSettings');
        // Get new settings from element.
        /** @type {string|Number} */
        var newValue = this.value.replace('"', '').replace("'", '');
        /** @type {string} */
        var valueName = this.name.replace('"', '').replace("'", '');
        settings[valueName] = newValue;

        // Save it.
        /** @type {Date} */
        var date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        /** @type {string} */
        var expires = 'expires=' + date.toUTCString();
        // Remove a possible old value from a previous version.
        document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        // Set the new one.
        document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
        // Feedback about update.
        alert(valueName + ' --> ' + newValue + '\n\nPlease reload the page to use the new local settings.');
    };

    /**
     * Resets all values in the local cookie settings.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    kdt.resetSetting = function (event, element) {

        // We do not delete the cookie, we simply remove all settings in it.
        /** @type {Object} */
        var settings = {};
        /** @type {Date} */
        var date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        /** @type {string} */
        var expires = 'expires=' + date.toUTCString();
        document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';

        alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
    };

    /**
     * Wrapper to parse a json, without the danger of an error.
     *
     * @param {string} string
     * @returns {*}
     */
    kdt.parseJson = function (string) {
        /** @type {*} */
        var result;

        try {
            result = JSON.parse(string);
        } catch (error) {
            // No json, no data!
            return false;
        }
        // Return the parsed result.
        return result;

    };

    /**
     * Prevents the bubbeling of en event, nothing more.
     *
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    kdt.preventBubble = function (event, element) {
        event.stopPropagation();
    };

    /**
     * Get all elements with the provided selector and
     * move them to the bottom of the dom, right before
     * the </body> end tag.
     *
     * @param {string} selector
     */
    kdt.moveToBottom = function (selector) {
        // Get all elements.
        /** @type {NodeList} */
        var elements = document.querySelectorAll(selector);
        /** @type {Element} */
        var body = document.querySelector('body');

        for (var i = 0; i < elements.length; i++) {
            // Check if their parent is the body tag.
            if (elements[i].parentNode.nodeName.toUpperCase() !== 'BODY') {
                // Meh, we are handling some broken DOM. We need to move it
                // to the bottom.
                body.appendChild(elements[i]);
            }
        }
    };

    /**
     * Simply merges object 2 into object 1, overwriting or adding its properties.
     * object1 and object2 stay as they are.
     *
     * @param {object} object1
     *   The starting one.
     * @param {object} object2
     *   We merge this one into the first one, possibly overwriting it's attributes.
     *
     * @returns {object}
     *   A new object.
     */
    kdt.simpleMerge = function(object1, object2){
        var object3 = {};
        var attribute;

        for (attribute in object1) {
            object3[attribute] = object1[attribute];
        }
        for (attribute in object2) {
            object3[attribute] = object2[attribute];
        }
        return object3;
    };

    /**
     * Move the draXX element into the viewport. Should be called onDocumentReady.
     *
     * @param {string} selector
     */
    kdt.moveToViewport = function (selector) {
        // Meh, we need to use the timeout to make this work on MS-Edge.
        // Edge remembers the last scrolling position *after* the onDocumentReady
        // event. 500 ms should be enough time to do this.
        setTimeout(function(){
            // Get the current viewport top value.
            /** @type {number} */
            var viewportTop = document.documentElement.scrollTop;
            // Fallback for Chrome.
            if (viewportTop === 0 ) {
                viewportTop = document.body.scrollTop;
            }

            // Get the elements we need to move
            /** @type {NodeList} */
            var elements = document.querySelectorAll(selector);
            /** @type {number} */
            var oldOffset = 0;

            for (var i = 0; i < elements.length; i++) {
                // Get it's old offset.
                oldOffset = parseInt(elements[i].style.top.slice(0, -2), 10);
                // Set the new offset.
                elements[i].style.top = (oldOffset + viewportTop) + 'px';
            }
        }, 500);
    };

    window.kreXXdomTools = kdt;

})();