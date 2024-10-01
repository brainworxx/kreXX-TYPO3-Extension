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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

class Draxx
{
    /**
     * The selector, where we initialize the draXX
     *
     * @var {string}
     */
    protected selector:string;

    /**
     * Callback, when mouse up.
     *
     * @var {Function}
     */
    protected callbackUp:Function;

    /**
     * Callback, when dragging is happening.
     *
     * @var {Function}
     */
    protected callbackDrag:Function;

    /**
     * The style element of the stuff we are dragging around.
     */
    protected elContentStyle:CSSStyleDeclaration;

    /**
     * The current x-axis offset.
     *
     * @var {number}
     */
    protected offSetX:number;

    /**
     * The current y-axis offset.
     *
     * @var {number}
     */
    protected offSetY:number;

    /**
     * The kreXX dom tools
     *
     * @var {Kdt}
     */
    protected kdt:Kdt;

    /**
     * Register the dragging on the handle, and storing the callbacks.
     *
     * @param {string} selector
     * @param {string} handle
     * @param {Function} callbackUp
     * @param {Function} callbackDrag
     */
    constructor(selector:string, handle:string, callbackUp:Function, callbackDrag:Function)
    {
        this.selector = selector;
        this.callbackUp = callbackUp;
        this.callbackDrag = callbackDrag;
        this.kdt = new Kdt();

        let elements:NodeList = document.querySelectorAll(handle);
        for (let i = 0; i < elements.length; i++) {
            elements[i].addEventListener('mousedown', this.startDraxx);
        }
    }

    /**
     * Move the draXX element into the viewport. Should be called onDocumentReady.
     *
     * @param {string} selector
     */
    public moveToViewport(selector:string): void
    {
        // Meh, we need to use the timeout to make this work on MS-Edge.
        // Edge remembers the last scrolling position *after* the onDocumentReady
        // event. 500 ms should be enough time to do this.
        setTimeout(function (){
            // Get the current viewport top value.
            /** @type {number} */
            let viewportTop:number = document.documentElement.scrollTop;
            // Fallback for Chrome.
            if (viewportTop === 0 ) {
                viewportTop = document.body.scrollTop;
            }

            // Get the elements we need to move
            /** @type {NodeList} */
            let elements:NodeList = document.querySelectorAll(selector);
            /** @type {number} */
            let oldOffset:number = 0;

            for (let i:number = 0; i < elements.length; i++) {
                // Get it's old offset.
                oldOffset = parseInt((elements[i] as HTMLElement).style.top.slice(0, -2), 10);
                // Set the new offset.
                (elements[i] as HTMLElement).style.top = (oldOffset + viewportTop) + 'px';
            }
        }, 500);
    }

    /**
     * Starts the dragging on a mousedown.
     *
     * @event mousedown
     * @param {MouseEvent} event
     */
    protected startDraxx = (event:MouseEvent): void =>
    {
        // The selector has an ID, we only have one of them.
        let elContent:HTMLElement = (this.kdt.getParents((event.target as Node), this.selector)[0] as HTMLElement);
        let offset:Offset = this.getElementOffset(elContent);

        // Calculate original offset.
        this.offSetY = offset.top + elContent.offsetHeight - event.pageY - elContent.offsetHeight;
        this.offSetX = offset.left + this.outerWidth(elContent) - event.pageX - this.outerWidth(elContent);
        this.elContentStyle = elContent.style;

        // We might need to add a special offset, in case that:
        // - body is position: relative;
        // and there are elements above that have
        // - margin: top or
        // - margin: bottom
        let bodyStyle:CSSStyleDeclaration = getComputedStyle(document.querySelector('body'));
        if (bodyStyle.position === 'relative') {
            let relOffsetY:number;
            let relOffsetX:number;

            // We need to check the body.
            relOffsetY = parseInt(bodyStyle.marginTop, 10);
            relOffsetX = parseInt(bodyStyle.marginLeft, 10);
            if (relOffsetY > 0) {
                // We take the body offset.
            } else {
                // We need to look for another offset.
                // Now we need to get all elements above the current kreXX element and
                // get their margins (top and button)
                let prev:Element = elContent.previousElementSibling;
                do {
                    relOffsetY = parseInt(getComputedStyle(prev).marginTop, 10);
                    prev = prev.previousElementSibling;
                    // We will stop if we ran out of elements or if we have found the
                    // first offset.
                } while (prev && relOffsetY === 0);
            }

            // Correct our initial offset.
            this.offSetY -= relOffsetY;
            this.offSetX -= relOffsetX;
        }

        document.addEventListener("mousemove", this.drag);
        document.addEventListener("mouseup", this.mouseUp);

        event.preventDefault();
        event.stopPropagation();
    };

    /**
     * Stops the dragging process.
     *
     * @event mouseup
     * @param {MouseEvent} event
     */
    protected mouseUp = (event:MouseEvent): void =>
    {
        event.preventDefault();
        event.stopPropagation();

        // Unregister to prevent slowdown.
        document.removeEventListener("mousemove", this.drag);
        document.removeEventListener("mouseup", this.mouseUp);

        // Calling the callback for the mouseup.
        this.callbackUp();
    };

    /**
     * Drags the DOM element around.
     *
     * @event mouseDown
     * @param {MouseEvent} event
     */
    protected drag = (event:MouseEvent): void =>
    {
        event.preventDefault();
        event.stopPropagation();

        this.elContentStyle.left = (event.pageX + this.offSetX) + "px";
        this.elContentStyle.top = (event.pageY + this.offSetY) + "px";

        // Calling the callback for the dragging.
        this.callbackDrag();
    };

    /**
     * Gets the top and left offset of a DOM element.
     *
     * @param {Element} element
     *
     * @returns {Offset}
     */
    protected getElementOffset(element:Element): Offset
    {
        let de:Element = document.documentElement;
        let box:DOMRect = element.getBoundingClientRect();
        let top:number = box.top + window.pageYOffset - de.clientTop;
        let left:number = box.left + window.pageXOffset - de.clientLeft;
        return {top: top, left: left};
    }

    /**
     * Gets the outer width of an element.
     *
     * @param {HTMLElement} element
     *
     * @returns {number}
     */
    protected outerWidth(element:HTMLElement): number
    {
        let width:number = element.offsetWidth;
        let style:CSSStyleDeclaration = getComputedStyle(element);
        width += parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
        return width;
    }
}

interface Offset {
    top:number;
    left:number;
}