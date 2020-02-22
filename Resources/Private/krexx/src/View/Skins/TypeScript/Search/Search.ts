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

class Search
{
    /**
     * Here we save the search results
     *
     * This is multidimensional array:
     * results[kreXX-instance][search text][search results]
     *                                     [pointer]
     * The [pointer] is the key of the [search result] where
     * you would jump to when you click "next"
     *
     */
    protected results = [];

    /**
     * The kreXX dom tools.
     *
     * @var {Kdt}
     */
    protected kdt:Kdt;

    /**
     * The kreXX dom tools.
     *
     * @var {Eventhandler}
     */
    protected eventHandler:Eventhandler;

    /**
     * The jump-to implementation.
     */
    protected jumpTo:Function;

    /**
     * Inject the event handler.
     *
     * @param {Eventhandler} eventHandler
     * @param {Function} jumpTo
     */
    constructor(eventHandler:Eventhandler, jumpTo:Function)
    {
        this.kdt = new Kdt();
        this.eventHandler = eventHandler;
        this.jumpTo = jumpTo;

        // Clear our search results, because we now have new options.
        this.eventHandler.addEvent('.ksearchcase', 'change', this.clearSearch);
        // Clear our search results, because we now have new options.
        this.eventHandler.addEvent('.ksearchkeys', 'change', this.clearSearch);
        // Clear our search results, because we now have new options.
        this.eventHandler.addEvent('.ksearchshort', 'change', this.clearSearch);
        // Clear our search results, because we now have new options.
        this.eventHandler.addEvent('.ksearchlong', 'change', this.clearSearch);
        // Clear our search results, because we now have new options.
        this.eventHandler.addEvent('.ksearchwhole', 'change', this.clearSearch);
        // Display our search options.
        this.eventHandler.addEvent('.koptions', 'click', this.displaySearchOptions);
        // Listen for a return key in the seach field.
        this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
    }

    /**
     * Reset the search results, because we now have new search options.
     *
     * @event change
     */
    protected clearSearch = (event:Event): void =>
    {
        // Wipe our instance data, nothing more
        this.results[this.kdt.getDataset((event.target as Element), 'instance')] = [];
    };

    /**
     * Toggle the display of the search options.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected displaySearchOptions = (event:Event, element:Node): void =>
    {
        // Get the options and switch the display class.
        this.kdt.toggleClass((element.parentNode as Element).nextElementSibling, 'khidden');
    };

    /**
     * Initiates the search.
     *
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    public performSearch = (event:Event, element:Element): void =>
    {
        // Hide the search options.
        this.kdt.addClass([(element.parentNode as HTMLElement).nextElementSibling], 'khidden');

        // Stitching together our configuration.
        let config:SearchConfig = new SearchConfig();
        config.searchtext = (element.parentNode.querySelector('.ksearchfield') as HTMLInputElement).value;
        config.caseSensitive = (element.parentNode.parentNode.querySelector('.ksearchcase') as HTMLInputElement).checked;
        config.searchKeys = (element.parentNode.parentNode.querySelector('.ksearchkeys') as HTMLInputElement).checked;
        config.searchShort = (element.parentNode.parentNode.querySelector('.ksearchshort') as HTMLInputElement).checked;
        config.searchLong = (element.parentNode.parentNode.querySelector('.ksearchlong') as HTMLInputElement).checked;
        config.searchWhole = (element.parentNode.parentNode.querySelector('.ksearchwhole') as HTMLInputElement).checked;

        // Apply our configuration.
        if (config.caseSensitive === false) {
            config.searchtext = config.searchtext.toLowerCase();
        }

        // Nothing to search for.
        if (config.searchtext.length === 0) {
            // Not enough chars as a searchtext!
            element.parentNode.querySelector('.ksearch-state').textContent = '<- Please enter a search text.';
            return
        }

        // We only search for more than 3 chars.
        if (config.searchtext.length > 2 || config.searchWhole) {
            config.instance = this.kdt.getDataset(element, 'instance');
            let direction:string = this.kdt.getDataset(element, 'direction');
            config.payload = document.querySelector('#' + config.instance + ' .kbg-wrapper');

            // We need to un-collapse everything, in case it it collapsed.
            let collapsed:NodeList = config.payload.querySelectorAll('.kcollapsed');
            for (let i:number = 0; i < collapsed.length; i++) {
                this.eventHandler.triggerEvent((collapsed[i] as Element), 'click');
            }

            // Are we already having some results?
            if (typeof this.results[config.instance] !== "undefined") {
                if (typeof this.results[config.instance][config.searchtext] === "undefined") {
                    this.refreshResultlist(config);
                }
            } else {
                this.refreshResultlist(config);
            }

            let pointer:number = this.results[config.instance][config.searchtext]['pointer'];

            // Set the pointer to the next or previous element
            if (direction === 'forward') {
                pointer++;
            } else {
                pointer--;
            }

            // Do we have an element? We may need to adjust the pointer.
            if (typeof this.results[config.instance][config.searchtext]['data'][pointer] === "undefined") {
                if (direction === 'forward') {
                    // There is no next element, we go back to the first one.
                    pointer = 0;
                } else {
                    // There is no previous element, we go forward to the last one.
                    pointer = this.results[config.instance][config.searchtext]['data'].length - 1;
                }
            }
            // Check again.
            if (this.results[config.instance][config.searchtext]['data'][pointer]) {
                // Now we simply jump to the element in the array.
                this.jumpTo(this.results[config.instance][config.searchtext]['data'][pointer]);
            }

            // Feedback about where we are
            element.parentNode.querySelector('.ksearch-state').textContent =
                (pointer + 1) + ' / ' + (this.results[config.instance][config.searchtext]['data'].length);

            this.results[config.instance][config.searchtext]['pointer'] = pointer;
        } else {
            // Not enough chars as a searchtext!
            element.parentNode.querySelector('.ksearch-state').textContent = '<- must be bigger than 3 characters';
        }
    };

    /**
     * Resets our searchlist and fills it with results.
     *
     * @param {SearchConfig} config
     */
    protected refreshResultlist = (config:SearchConfig): void =>
    {
        // Remove all previous highlights
        this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');

        // Apply our configuration.
        let selector = [];
        if (config.searchKeys === true) {
            selector.push('li.kchild span.kname');
        }
        if (config.searchShort === true) {
            selector.push('li.kchild span.kshort')
        }
        if (config.searchLong === true) {
            selector.push('li div.kpreview');
        }

        // Get a new list of elements
        this.results[config.instance] = [];
        this.results[config.instance][config.searchtext] = [];
        this.results[config.instance][config.searchtext]['data'] = [];
        this.results[config.instance][config.searchtext]['pointer'] = [];

        // Poll out payload for elements to search
        if (selector.length > 0) {
            let list:NodeList;
            list = config.payload.querySelectorAll(selector.join(', '));
            let textContent:string = '';
            for (let i:number = 0; i < list.length; ++i) {
                // Does it contain our search string?
                textContent = list[i].textContent;
                if (config.caseSensitive === false) {
                    textContent = textContent.toLowerCase();
                }
                if ((config.searchWhole === true && textContent === config.searchtext) ||
                    (config.searchWhole === false && textContent.indexOf(config.searchtext) > -1)
                ) {
                    this.kdt.toggleClass((list[i] as Element), 'ksearch-found-highlight');
                    this.results[config.instance][config.searchtext]['data'].push(list[i]);
                }
            }
        }

        // Reset our index.
        // When nothing is found, the pointer is toggeling -1, to show that there is something happening.
        this.results[config.instance][config.searchtext]['pointer'] = -1;
    };

    /**
     * Listens for a <RETURN> in the search field.
     *
     * @param {KeyboardEvent} event
     * @event keyUp
     */
    public searchfieldReturn = (event:KeyboardEvent): void =>
    {
        // Prevents the default event behavior (ie: click).
        event.preventDefault();
        // Prevents the event from propagating (ie: "bubbling").
        event.stopPropagation();

        // If this is no <RETURN> key, do nothing.
        if (event.which !== 13) {
            return;
        }

        this.eventHandler.triggerEvent((event.target as Node).parentNode.querySelectorAll('.ksearchnow')[1], 'click');
    };
}
