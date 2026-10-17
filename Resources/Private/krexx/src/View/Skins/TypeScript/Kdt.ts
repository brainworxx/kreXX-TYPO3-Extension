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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

type StoppableEvent = Event & { stop?: boolean };

class Kdt {
  /**
   * The jump-to implementation.
   */
  protected jumpTo: Function = function () {
  };

  /**
   * Our translations class.
   */
  public translations: Translations;

  /**
   * Init the translations and tell the frontend that we are ready.
   */
  constructor() {
    this.translations = new Translations('.krdata-structure.krtrans', this);
    this.addClass('.kwrapper .knoscript', 'khidden');
  }

  /**
   * Make sure to execute the JS only once.
   */
  public beenHere(): boolean {
    if (typeof window['krexxDone'] === 'undefined') {
      window['krexxDone'] = true;
      return false;
    }

    return true;
  }

  /**
   * Set the currently used jump to callback.
   *
   * @param {Function} jumpTo
   */
  public setJumpTo = (jumpTo: Function): void => {
    this.jumpTo = jumpTo;
  };

  /**
   * Gets all parents of an element which has the specified class.
   *
   * @param {Node} el
   * @param {string} selector
   */
  public getParents(el: Node, selector: string): Node[] {
    const result: Node[] = [];
    let parent: Node | null = el.parentNode;
    const body: Node | null = document.querySelector('body');

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
   * Gets the first element from a list which hat that class.
   *
   * @param {NodeList|HTMLCollection} elements
   * @param {string} className
   *
   * @returns {Element|null} the element
   */
  public findInDomlistByClass(elements: NodeList | HTMLCollection, className: string): Node | null {
    for (let i = 0; i < elements.length; i++) {
      if ((elements[i] as Element).classList.contains(className.trim())) {
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
  public addClass(selector: NodeList | string | Node[], className: string): void {
    /** @type {NodeList|null|Array} */
    let elements: NodeList | null | Node[];

    if (typeof selector === 'string') {
      // Get our elements.
      elements = document.querySelectorAll(selector);
    } else {
      // We already have our list that we will use.
      elements = selector;
    }

    for (let i = 0; i < elements.length; i++) {
      (elements[i] as Element).classList.add(className);
    }
  }

  /**
   * Removes a class from elements
   *
   * @param {NodeList|string} selector
   * @param {string} className
   */
  public removeClass(selector: NodeList | string | Node[], className: string): void {
    let elements: any;

    if (typeof selector === 'string') {
      // Get our elements.
      elements = document.querySelectorAll(selector);
    } else {
      // We already have our list that we will use.
      elements = selector;
    }

    for (let i = 0; i < elements.length; i++) {
      (elements[i] as Element).classList.remove(className);
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
  public getDataset(el: Element | null, what: string, mustEscape: boolean = false): string {
    let result: string | null;

    if (
      el === null
      || typeof el === 'undefined'
      || typeof el.getAttribute !== 'function'
    ) {
      // No el or no attribute, no data!
      return '';
    }

    result = el.getAttribute('data-' + what);

    if (result === null) {
      return '';
    }

    if (mustEscape) {
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
  public setDataset(el: Element, what: string, value: string): void {
    if (typeof el !== 'undefined') {
      el.setAttribute('data-' + what, value);
    }
  }

  /**
   * Selects some text
   *
   * @param {Element} el
   */
  public selectText(el: Element): void {
    const range: Range = document.createRange();
    const selection: Selection | null = window.getSelection();

    if (selection === null) {
      return;
    }

    range.selectNodeContents(el);
    selection.removeAllRanges();
    selection.addRange(range);
  }

  /**
   * Wrapper to parse a json, without the danger of an error.
   *
   * @param {string} string
   * @returns {Object|boolean}
   */
  public parseJson(string: string): Record<string, string> | false {
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
  public moveToBottom(selector: string): void {
    // Get all elements.
    const elements: NodeList = document.querySelectorAll(selector);

    for (let i = 0; i < elements.length; i++) {
      // Check if their parent is the body tag.
      if (elements[i]?.parentNode?.nodeName.toUpperCase() !== 'BODY') {
        // Meh, we are handling some broken DOM. We need to move it
        // to the bottom.
        document.querySelector('body')?.appendChild(elements[i]);
      }
    }
  }

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
  public collapse = (event: Event, element: Element): void => {
    (event as StoppableEvent).stop = true;

    const wrapper: Node = this.getParents(element, '.kwrapper')[0];

    // Remove all old classes within this debug "window"
    this.removeClass((wrapper as Element).querySelectorAll('.kfilterroot'), 'kfilterroot');
    this.removeClass((wrapper as Element).querySelectorAll('.krootline'), 'krootline');
    this.removeClass((wrapper as Element).querySelectorAll('.ktopline'), 'ktopline');

    // Here we start the hiding, only when clicked on a
    // none-collapsed button.
    if (!element.classList.contains('kcollapsed')) {
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

    const jumpTo = this.jumpTo;
    setTimeout(
      function () {
        // Move the element into the viewport.
        jumpTo(element, true);
      }, 100
    );
  };
}
