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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

class Hans
{
    /**
     * kreXX dom tools.
     *
     * @var {Kdt}
     */
    protected kdt:Kdt;

    /**
     * Our dragable lib.
     *
     * @var {Draxx}
     */
    protected draxx:Draxx;

    /**
     * Out DOM search.
     *
     * @var {Search}
     */
    protected search:Search;

    /**
     * The event handler.
     *
     * @var {Evenhandler}
     */
    protected eventHandler:Eventhandler;

    /**
     * Here we store the selectors for the ruin initialization.
     *
     * @var {Selectors}
     */
    protected selectors:Selectors;

    /**
     * The last jumpTo to interval.
     *
     * @var {number}
     */
    protected jumpToInterval:number = 0;

    /**
     * Defining all selectors for the run method.
     */
    constructor()
    {
        this.selectors = new Selectors();
        this.selectors.eventHandler = '.kwrapper.kouterwrapper, .kfatalwrapper-outer';
        this.selectors.moveToBottom = '.kouterwrapper';
        this.selectors.close = '.kwrapper .kheadnote-wrapper .kclose, .kwrapper .kfatal-headnote .kclose';
        this.selectors.toggle = '.kwrapper .kexpand';
        this.selectors.setSetting = '.kwrapper .keditable select, .kwrapper .keditable input:not(.ksearchfield)';
        this.selectors.resetSetting = '.kwrapper .kresetbutton';
        this.selectors.copyFrom = '.kwrapper .kcopyFrom';
        this.selectors.displaySearch = '.kwrapper .ksearchbutton, .kwrapper .ksearch .kclose';
        this.selectors.performSearch = '.kwrapper .ksearchnow';
        this.selectors.collapse = '.kwrapper .kolps';
        this.selectors.generateCode = '.kwrapper .kgencode';
        this.selectors.preventBubble = '.kodsp';
        this.selectors.displayInfoBox = '.kwrapper .kchild .kinfobutton';
        this.selectors.moveToViewport = '.kouterwrapper';
    }

    /**
     * Getting our act together.
     */
    public run(): void
    {
        // Init our libs before usage.
        this.kdt = new Kdt();
        if (this.kdt.beenHere()) {
            // We only do this once.
            return;
        }
        this.kdt.setJumpTo(this.jumpTo);
        this.eventHandler = new Eventhandler(this.selectors.eventHandler);
        this.search = new Search(this.eventHandler, this.jumpTo);

        // In case we are handling a broken html structure, we must move everything
        // to the bottom.
        this.kdt.moveToBottom(this.selectors.moveToBottom);

        // Initialize the draggable.
        this.initDraxx();

        /**
         * Register kreXX close button function.
         *
         * @event click
         *   Displays a closing animation of the corresponding
         *   kreXX output "window" and then removes it from the markup.
         */
        this.eventHandler.addEvent(this.selectors.close, 'click', this.close);

        /**
         * Register toggling to the elements.
         *
         * @event click
         *   Expands a kreXX node when it is not expanded.
         *   When it is already expanded, it closes it.
         */
        this.eventHandler.addEvent(this.selectors.toggle, 'click', this.toggle);

        /**
         * Register functions for the local dev-settings.
         *
         * @event change
         *   Changes on the kreXX html forms.
         *   All changes will automatically be written to the browser cookies.
         */
        this.eventHandler.addEvent(this.selectors.setSetting, 'change', this.kdt.setSetting);

        /**
         * Register cookie reset function on the reset button.
         *
         * @event click
         *   Resets the local settings in the settings cookie,
         *   when the reset button ic clicked.
         */
        this.eventHandler.addEvent(this.selectors.resetSetting, 'click', this.kdt.resetSetting);

        /**
         * Register the recursions resolving.
         *
         * @event click
         *   When a recursion is clicked, kreXX tries to locate the
         *   first output of the object and highlight it.
         */
        this.eventHandler.addEvent(this.selectors.copyFrom, 'click', this.kdt.copyFrom);

        /**
         * Register the displaying of the search menu
         *
         * @event click
         *   When the button is clicked, kreXX will display the
         *   search menu associated this the same output window.
         */
        this.eventHandler.addEvent(this.selectors.displaySearch, 'click', this.displaySearch);

        /**
         * Register the search event on the next button.
         *
         * @event click
         *   When the button is clicked, kreXX will start searching.
         */
        this.eventHandler.addEvent(this.selectors.performSearch, 'click', this.search.performSearch);

        /**
         * Register the Collapse-All functions on its symbol
         *
         * @event click
         */
        this.eventHandler.addEvent(this.selectors.collapse, 'click', this.kdt.collapse);

        /**
         * Register the code generator on the P symbol.
         *
         * @event click
         */
        this.eventHandler.addEvent(this.selectors.generateCode, 'click', this.generateCode);

        /**
         * Prevents the click-event-bubbling on the generated code.
         *
         * @event click
         */
        this.eventHandler.addEvent(this.selectors.preventBubble, 'click', this.eventHandler.preventBubble);

        /**
         * Display the content of the info box.
         *
         * @event click
         */
        this.eventHandler.addEvent(this.selectors.displayInfoBox, 'click', this.displayInfoBox);

        // Disable form-buttons in case a logfile is opened local.
        if (window.location.protocol === 'file:') {
            this.disableForms();
        }

        // Move the output into the viewport. Debugging onepager is so annoying, otherwise.
        this.draxx.moveToViewport(this.selectors.moveToViewport);
    }

    /**
     * Initialize the draggable.
     */
    protected initDraxx(): void
    {
        this.draxx = new Draxx(
            '.kwrapper',
            '.kheadnote',
            function () {
                let searchWrapper:NodeList = document.querySelectorAll('.search-wrapper');
                let viewportOffset:DOMRect;
                for (let i = 0; i < searchWrapper.length; i++) {
                    viewportOffset = (searchWrapper[i] as HTMLElement).getBoundingClientRect();
                    (searchWrapper[i] as HTMLElement).style.position = 'fixed';
                    (searchWrapper[i] as HTMLElement).style.top = viewportOffset.top + 'px';
                }
            },
            function () {
                let searchWrapper = document.querySelectorAll('.search-wrapper');
                for (let i = 0; i < searchWrapper.length; i++) {
                    (searchWrapper[i] as HTMLElement).style.position = 'absolute';
                    (searchWrapper[i] as HTMLElement).style.top = '';
                }
            }
        );
    }

    /**
     * Hides or displays the nest under an expandable element.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected toggle = (event:Event, element:Element): void =>
    {
        this.kdt.toggleClass(element, 'kopened');

        // Toggle all siblings.
        let sibling:Element = element.nextElementSibling;
        do {
           this.kdt.toggleClass(sibling, 'khidden');
           sibling = sibling.nextElementSibling;
        } while (sibling);
    };

    /**
     * Removes the old highlight and sets the new one.
     *
     * @param {Element} el
     * @param {boolean} noHighlight
     */
    protected setHighlighting(el:Element, noHighlight:boolean): void
    {
        let nests:Node[] = this.kdt.getParents(el, '.knest');

        // Show them.
        this.kdt.removeClass(nests, 'khidden');
        // We need to expand them all.
        for (let i = 0; i < nests.length; i++) {
            this.kdt.addClass([(nests[i] as Element).previousElementSibling], 'kopened');
        }

        if (noHighlight !== true) {
            // Remove old highlighting.
            this.kdt.removeClass('.highlight-jumpto', 'highlight-jumpto');
            // Highlight new one.
            this.kdt.addClass([el], 'highlight-jumpto');
        }
    }

    /**
     * "Jumps" to an element in the markup and highlights it.
     *
     * It is used when we are facing a recursion in our analysis.
     *
     * @event search
     * @param {Element} el
     *   The element you want to focus on.
     * @param {boolean} noHighlight
     *   Do we need to highlight the element we are jumping to?
     */
    protected jumpTo = (el:Element, noHighlight:boolean): void =>
    {
        this.setHighlighting(el, noHighlight);

        // Getting our scroll container
        let destination:number;
        let container:Element|null = document.querySelector('.kfatalwrapper-outer');
        if (container === null) {
            // Normal scrolling
            container = document.querySelector('html');
            // The html container may not accept any scrollTop value.
            ++container.scrollTop;
            if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
                container = document.querySelector('body');
            }
            --container.scrollTop;
            destination = el.getBoundingClientRect().top + container.scrollTop - 50;
        } else {
            // Fatal Error scrolling.
            destination = el.getBoundingClientRect().top - container.getBoundingClientRect().top + container.scrollTop - 50;
        }

        let diff:number = Math.abs(container.scrollTop - destination);
        if (diff < 250) {
            // No need to jump there
            return;
        }

        // Getting the direction
        let step:number;
        if (container.scrollTop < destination) {
            // Forward.
            step = Math.round(diff / 12);
        } else {
            // Backward.
            step = Math.round(diff / 12) * -1;
        }

        // We also need to check if the setting of the new value was successful.
        let lastValue:number = container.scrollTop;

        // Make sure to end the last interval before starting a new one.
        clearInterval(this.jumpToInterval);
        let interval:number = this.jumpToInterval = setInterval(function () {
            container.scrollTop += step;
            if (Math.abs(container.scrollTop - destination) <= Math.abs(step) || container.scrollTop === lastValue) {
                // We are here now, the next step would take us too far.
                // So we jump there right now and then clear the interval.
                container.scrollTop = destination;
                clearInterval(interval);
            }
            lastValue = container.scrollTop;
        }, 10);
    };

    /**
     * Shows a "fast" closing animation and then removes the kreXX window from the markup.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    protected close = (event:Event, element:Element): void =>
    {
        let instance:string = this.kdt.getDataset(element, 'instance');
        let elInstance:HTMLElement = document.querySelector('#' + instance);

        // Remove it nice and "slow".
        let opacity:number = 1;
        let interval:number = setInterval(function () {
            if (opacity < 0) {
                // It's invisible now, so we clear the timer and remove it from the DOM.
                clearInterval(interval);
                elInstance.parentNode.removeChild(elInstance);
                return;
            }
            opacity -= 0.1;
            elInstance.style.opacity = opacity.toString();
        }, 20);
    };

    /**
     * Disables the editing functions, when a kreXX output is loaded as a file.
     *
     * These local settings would actually do
     * nothing at all, because they would land inside a cookie
     * for that file, and not for the server.
     */
    protected disableForms(): void
    {
        let elements:NodeList = document.querySelectorAll('.kwrapper .keditable input, .kwrapper .keditable select');
        for (let i = 0; i < elements.length; i++) {
            (elements[i] as HTMLInputElement).disabled = true;
        }
    }

    /**
     * The kreXX code generator.
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Element} element
     *   The element that was clicked.
     */
    protected generateCode = (event:Event, element:Element): void =>
    {
        // We don't want to bubble the click any further.
        event.stop = true;

        let codedisplay:HTMLElement = (element.nextElementSibling as HTMLElement);
        let resultArray:string[] = [];
        let resultString:string = '';
        let sourcedata:string;
        let domid:string;
        let wrapperLeft:string = '';
        let wrapperRight:string = '';

        // Get the first element
        let el:Element|Node = (this.kdt.getParents(element, 'li.kchild')[0] as Element);

        // Start the loop to collect all the date
        while (el) {
            // Get the domid
            domid = this.kdt.getDataset((el as Element), 'domid');
            sourcedata = this.kdt.getDataset((el as Element), 'source');

            wrapperLeft = this.kdt.getDataset((el as Element), 'codewrapperLeft');
            wrapperRight = this.kdt.getDataset((el as Element), 'codewrapperRight');

            if (sourcedata === '. . .') {
                if (domid !== '') {
                    // We need to get a new el, because we are facing a recursion, and the
                    // current path is not really reachable.
                    el = document.querySelector('#' + domid).parentNode;
                    // Get the source, again.
                    resultArray.push(this.kdt.getDataset((el as Element), 'source'));
                }
            }
            if (sourcedata !== '') {
                resultArray.push(sourcedata);
            }
            // Get the next el.
            el = this.kdt.getParents(el, 'li.kchild')[0];
        }
        // Now we reverse our result, so that we can resolve it from the beginning.
        resultArray.reverse();

        for (let i = 0; i < resultArray.length; i++) {
            // We must check if our value is actually reachable.
                // '. . .' means it is not reachable,
                // we will stop right here and display a comment stating this.
            if (resultArray[i] === '. . .') {
                resultString = '// Value is either protected or private.<br /> // Sorry . . ';
                break;
            }

            // Check if we are facing a ;stop; instruction
            if (resultArray[i] === ';stop;') {
                resultString = '';
                resultArray[i] = '';
            }

            // We're good, value can be reached!
            if (resultArray[i].indexOf(';firstMarker;') !== -1) {
                // We add our result so far into the "source template"
                resultString = resultArray[i].replace(';firstMarker;', resultString);
            } else {
                // Normal concatenation.
                resultString = resultString + resultArray[i];
            }
        }

        // Add the wrapper that we collected so far
        resultString = wrapperLeft + resultString + wrapperRight;

        // 3. Add the text
        codedisplay.innerHTML = '<div class="kcode-inner">' + resultString + '</div>';
        if (codedisplay.style.display === 'none') {
            codedisplay.style.display = '';
            this.kdt.selectText(codedisplay);
        } else {
            codedisplay.style.display = 'none';
        }
    };

    /**
     * Checks if the search form is inside the viewport. If not, fixes it on top.
     * Gets triggered on,y when scolling the fatal error handler.
     */
    protected checkSearchInViewport = (): void =>
    {
        // Get the search
        let search:HTMLElement = document.querySelector('.kfatalwrapper-outer .search-wrapper');
        // Reset the inline styles
        search.style.position = '';
        search.style.top = '';

        // Measure it!
        let rect = search.getBoundingClientRect();
        if (rect.top < 0) {
            // Set it to the top
            search.style.position = 'fixed';
            search.style.top = '0px';
        }
    };

    /**
     * Toggle the display of the infobox.
     *
     * @param {Event} event
     * @param {Element} element
     *
     * @event keyUp
     */
    protected displayInfoBox = (event:Event, element:Element): void =>
    {
        // We don't want to bubble the click any further.
        event.stop = true;

        // Find the corresponding info box.
        let box:HTMLElement = (element.nextElementSibling as HTMLElement);

        if (box.style.display === 'none') {
            box.style.display = '';
        } else {
            box.style.display = 'none';
        }
    };

    /**
     * Display the search dialog
     *
     * @event click
     * @param {Event} event
     *   The click event.
     * @param {Node} element
     *   The element that was clicked.
     */
    protected displaySearch = (event:Event, element:Node): void =>
    {
        let instance:string = this.kdt.getDataset((element as Element), 'instance');
        let search:HTMLElement = document.querySelector('#search-' + instance);
        let viewportOffset;

        // Toggle display / hidden.
        if (this.kdt.hasClass(search, 'khidden')) {
            // Display it.
            this.kdt.toggleClass(search, 'khidden');
            (search.querySelector('.ksearchfield') as HTMLElement).focus();
            search.style.position = 'absolute';
            search.style.top = '';
            viewportOffset = search.getBoundingClientRect();
            search.style.position = 'fixed';
            search.style.top = viewportOffset.top + 'px';
        } else {
            // Hide it.
            this.kdt.toggleClass(search, 'khidden');
            this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
            search.style.position = 'absolute';
            search.style.top = '';
        }
    };
}
