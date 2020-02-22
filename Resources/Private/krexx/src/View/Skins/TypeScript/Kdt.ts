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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

class Kdt
{
    /**
     * The jump-to implementation.
     */
    protected jumpTo:Function;

    /**
     * Set the currently used jump to callback.
     *
     * @param {Function} jumpTo
     */
    public setJumpTo = (jumpTo:Function): void =>
    {
        this.jumpTo = jumpTo;
    };

    /**
     * Gets all parents of an element which has the specified class.
     *
     * @param {Node} el
     * @param {string} selector
     */
    public getParents(el:Node, selector:string): Node[]
    {
        let result:Node[] = [];
        let parent:Node = el.parentNode;
        let body:Node = document.querySelector('body');

        while (parent !== null) {
            // Check for classname
            if ((parent as Element).matches(selector)) {
                result.push(parent);
            }
            // Get the next one.
            parent = parent.parentNode;
            // check if we have reached the top of the rabbit hole.
            if (parent === body) {
                // Exit the while.
                parent = null;
            }
        }

        return result;
    }

    /**
     * Determines if an element has a class.
     *
     * @param {Element} el
     * @param {string} className
     */
    public hasClass(el:Element, className:string): boolean
    {
        if (el.classList) {
            return el.classList.contains(className);
        } else {
            return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
        }
    }

    /**
     * Gets the first element from a list which hat that class.
     *
     * @param {NodeList|HTMLCollection} elements
     * @param {string} className
     *
     * @returns {Element|null} the element
     */
    public findInDomlistByClass(elements:NodeList|HTMLCollection, className:string): Node | null
    {
        className = " " + className + " ";
        for (let i = 0; i < elements.length; i++) {
            if ((" " + (elements[i] as Element).className + " ").replace(/[\n\t]/g, " ").indexOf(className) > -1) {
                return elements[i];
            }
        }
        return null;
    }

    /**
     * Adds a class to elements.
     *
     * @param {NodeList|string|Array} selector
     * @param {string} className
     */
    public addClass(selector:NodeList|string|Node[], className:string): void
    {
        /** @type {NodeList|null|Array} */
        let elements:NodeList|null|Node[];

        if (typeof selector === 'string') {
            // Get our elements.
            elements = document.querySelectorAll(selector);
        } else {
            // We already have our list that we will use.
            elements = selector;
        }

        for (let i = 0; i < elements.length; i++) {
            (elements[i] as Element).className += ' ' + className;
        }
    }

    /**
     * Removes a class from elements
     *
     * @param {NodeList|string} selector
     * @param {string} className
     */
    public removeClass(selector:NodeList|string|Node[], className:string): void
    {
        let elements:any;

        if (typeof selector === 'string') {
            // Get our elements.
            elements = document.querySelectorAll(selector);
        } else {
            // We already have our list that we will use.
            elements = selector;
        }

        for (let i = 0; i < elements.length; i++) {
            (elements[i] as Element).className = (elements[i] as Element).className.replace(
                new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' '
            );
        }
    }

    /**
     * Toggles the class of an element
     *
     * @param {Element} el
     * @param {string} className
     */
    public toggleClass(el:Element, className:string): void
    {
        if (el.classList) {
            // Just toggle it.
            el.classList.toggle(className);
        } else {
            // no class list there, we need to do this by hand.
            /** @type {Array} */
            let classes = el.className.split(' ');
            /** @type {number} */
            let existingIndex = classes.indexOf(className);

            if (existingIndex >= 0) {
                classes.splice(existingIndex, 1);
            } else {
                classes.push(className);
            }

            el.className = classes.join(' ');
        }
    }

    /**
     * Gets the dataset from en element.
     *
     * @param {Element} el
     * @param {string} what
     * @param {boolean} mustEscape
     *
     * @returns {string}
     */
    public getDataset(el:Element, what:string, mustEscape:boolean = false): string
    {
        let result:string|null;

        if (typeof el === 'undefined' ||
            typeof el.getAttribute !== 'function'
        ) {
            // No el or no attribute, no data!
            return '';
        }

        result = el.getAttribute('data-' + what);

        if (result === null) {
            return '';
        }

        if (mustEscape === true) {
            return result.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")
                // <small> is allowed. Parameters are better readable this way.
                .replace('&lt;small&gt;', '<small>')
                .replace('&lt;/small&gt;', '</small>');
        }

        return result;
    }

    /**
     * Sets the dataset from en element.
     *
     * @param {Element} el
     * @param {string} what
     * @param {string} value
     */
    public setDataset(el:Element, what:string, value:string): void
    {
        if (typeof el !== 'undefined') {
            el.setAttribute('data-' + what, value);
        }
    }

    /**
     * Selects some text
     *
     * @param {Element} el
     */
    public selectText(el:Element): void
    {
        let range:Range = document.createRange();
        let selection:Selection = window.getSelection();

        range.selectNodeContents(el);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    /**
     * Reads the values from a cookie.
     *
     * @param {string} cookieName
     *   Name of the cookie.
     *
     * @return {object}
     *   The value, set in the cookie.
     */
    public readSettings(cookieName:string): string|object
    {
        /** @type {string} */
        cookieName = cookieName + "=";
        let cookieArray:string[] = document.cookie.split(';');
        let result:object = {};
        let cookieString:string;

        for (let i = 0; i < cookieArray.length; i++) {
            cookieString = cookieArray[i];
            while (cookieString.charAt(0) === ' ') {
                cookieString = cookieString.substring(1, cookieString.length);
            }
            if (cookieString.indexOf(cookieName) === 0) {
                try {
                    // Return json, if possible.
                    result = JSON.parse(cookieString.substring(cookieName.length, cookieString.length));
                }
                catch (error) {
                    // Do nothing, we already have a fallback.
                }
            }
        }

        return result;
    }

    /**
     * Adds the value from a html element to the local cookie settings.
     *
     * @event change
     * @param {Event} event
     */
    public setSetting = (event:Event): void =>
    {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();

        // Get the old value.
        let settings = this.readSettings('KrexxDebugSettings');

        // Get new settings from element.
        let newValue:string|number = (event.target as HTMLInputElement).value.replace('"', '').replace("'", '');
        let valueName:string = (event.target as HTMLInputElement).name.replace('"', '').replace("'", '');
        settings[valueName] = newValue;

        // Save it.
        let date:Date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        let expires:string = 'expires=' + date.toUTCString();

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
    public resetSetting(event:Event, element:Node): void
    {
        // We do not delete the cookie, we simply remove all settings in it.
        let date:Date = new Date();
        date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
        let expires:string = 'expires=' + date.toUTCString();

        document.cookie = 'KrexxDebugSettings={}; ' + expires + '; path=/';
        alert('All local configuration have been reset.\n\nPlease reload the page to use the these settings.');
    }

    /**
     * Wrapper to parse a json, without the danger of an error.
     *
     * @param {string} string
     * @returns {Object|boolean}
     */
    public parseJson(string:string): Object|boolean
    {
        try {
            return JSON.parse(string);
        } catch (error) {
            // No json, no data!
            return false;
        }
    }

    /**
     * Get all elements with the provided selector and
     * move them to the bottom of the dom, right before
     * the </body> end tag.
     *
     * @param {string} selector
     */
    public moveToBottom(selector:string): void
    {
        // Get all elements.
        let elements:NodeList = document.querySelectorAll(selector);

        for (let i = 0; i < elements.length; i++) {
            // Check if their parent is the body tag.
            if (elements[i].parentNode.nodeName.toUpperCase() !== 'BODY') {
                // Meh, we are handling some broken DOM. We need to move it
                // to the bottom.
                document.querySelector('body').appendChild(elements[i]);
            }
        }
    };

    /**
     * Collapses elements for a breadcrumb
     *
     * Hides all other elements, except the one with
     * the button. This way, we can get a breadcrumb
     * to the element we want to look at.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    public collapse = (event:Event, element:Element): void =>
    {
        event.stop = true;

        let wrapper:Node = this.getParents(element, '.kwrapper')[0];

        // Remove all old classes within this debug "window"
        this.removeClass((wrapper as Element).querySelectorAll('.kfilterroot'), 'kfilterroot');
        this.removeClass((wrapper as Element).querySelectorAll('.krootline'), 'krootline');
        this.removeClass((wrapper as Element).querySelectorAll('.ktopline'), 'ktopline');

        // Here we start the hiding, only when clicked on a
        // none-collapsed button.
        if (!this.hasClass(element, 'kcollapsed')) {
            this.addClass(this.getParents(element, 'div.kbg-wrapper > ul'), 'kfilterroot');
            // Add the "rootline" to all elements between the button and the filterroot
            this.addClass(this.getParents(element, 'ul.knode, li.kchild'), 'krootline');
            // Add the "topline" to the highest element in the rootline
            this.addClass([this.getParents(element, '.krootline')[0]], 'ktopline');
            // Reset the old collapse button.
            this.removeClass((wrapper as Element).querySelectorAll('.kcollapsed'), 'kcollapsed');

            // Highlight the new collapse button.
            this.addClass([element], 'kcollapsed');
        } else {
            // Reset the button, since we are un-collapsing nodes here.
            this.removeClass((wrapper as Element).querySelectorAll('.kcollapsed'), 'kcollapsed');
        }

        let jumpTo =  this.jumpTo;
        setTimeout(
            function () {
                // Move the element into the viewport.
                jumpTo(element, true);
            }, 100
        );
    };

    /**
     * When clicked on s recursion, this function will
     * copy the original analysis result there and delete
     * the recursion.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {HTMLElement} element
     *   The element that was clicked.
     */
    public copyFrom = (event:Event, element:HTMLElement): void =>
    {
        let i:number;

        // Get the DOM id of the original analysis.
        let domid:string = this.getDataset((element as Element), 'domid');
        if (domid === '') {
            // Do nothing.
            return;
        }
        // Get the analysis data.
        let orgNest:Node = document.querySelector('#' + domid);

        // Does the element exist?
        if (orgNest) {
            // Get the EL of the data (element with the arrow).
            let orgEl:Node = (orgNest as HTMLElement).previousElementSibling;
            // Clone the analysis data and insert it after the recursion EL.
            element.parentNode.insertBefore(orgNest.cloneNode(true), element.nextSibling);
            // Clone the EL of the analysis data and insert it after the recursion EL.
            let newEl:Element = (orgEl.cloneNode(true) as Element);
            element.parentNode.insertBefore(newEl, element.nextSibling);

            // Change the key of the just cloned EL to the one from the recursion.
            (this.findInDomlistByClass(newEl.children, 'kname') as HTMLElement).innerHTML = (this.findInDomlistByClass(element.children, 'kname') as HTMLElement).innerHTML;
            // We  need to remove the ids from the copy to avoid double ids.
            let allChildren = newEl.nextElementSibling.getElementsByTagName("*");
            for (i = 0; i < allChildren.length; i++) {
                allChildren[i].removeAttribute('id');
            }
            newEl.nextElementSibling.removeAttribute('id');

            // Now we add the dom-id to the clone, as a data-field. this way we can
            // make sure to always produce the right path to this value during source
            // generation.
            this.setDataset((newEl.parentNode as Element), 'domid', domid);

            // Remove the infobox from the copy, if available and add the one from the
            // recursion.
            let newInfobox = newEl.querySelector('.khelp');
            let newButton = newEl.querySelector('.kinfobutton');
            let realInfobox = element.querySelector('.khelp');
            let realButton = element.querySelector('.kinfobutton');

            // We don't need the infobox on newEl, so we will remove it.
            if (newInfobox !== null) {
                newInfobox.parentNode.removeChild(newInfobox);
            }
            if (newButton !== null) {
                newButton.parentNode.removeChild(newButton);
            }

            // We copy the Infobox from the recursion to the newEl, if it exists.
            if (realInfobox !== null) {
                newEl.appendChild(realButton);
                newEl.appendChild(realInfobox);
            }

            // Remove the recursion EL.
            element.parentNode.removeChild(element);
        }
    };
}
