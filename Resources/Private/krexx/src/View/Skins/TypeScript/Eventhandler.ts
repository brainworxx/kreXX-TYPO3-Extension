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

class Eventhandler {
  /**
   * Here we store our callbacks.
   *
   * @var Record<string, Function[]>
   */
  protected storage: Record<string, Function[]> = {};

  /**
   * Instance of the kreXX dom tools class.
   */
  protected kdt: Kdt;

  /**
   * Creating a Kdt instance, and registering of our event handler
   *
   * @param {string} selector
   */
  constructor(selector: string) {
    this.kdt = new Kdt();

    // Register the event handler.
    const elements: NodeList = document.querySelectorAll(selector);
    this.registerEventsOnElements(elements, 'click', this.handle);
  }

  /**
   * Registers an event listener on a list of elements.
   *
   * @param {NodeList} elements
   * @param {string} eventName
   * @param {EventListener} callback
   * @protected
   */
  protected registerEventsOnElements(elements: NodeList, eventName: string, callback: EventListener): void {
    for (let i = 0; i < elements.length; i++) {
      elements[i].addEventListener(eventName, callback);
    }
  }

  /**
   * Adds an event listener to a list of elements.
   *
   * @param {string} selector
   * @param {string} eventName
   * @param {Function} callBack
   *
   */
  public addEvent(selector: string, eventName: string, callBack: EventListener | Function): void {
    // We use the clickHandler instead.
    if (eventName === 'click') {
      this.addToStorage(selector, callBack);
    } else {
      /** @type {NodeList} */
      const elements: NodeList = document.querySelectorAll(selector);
      this.registerEventsOnElements(elements, eventName, (callBack as EventListener));
    }
  }

  /**
   * Prevent the bubbling of an event in the kdt event handler.
   *
   * @param {Event} event
   */
  public preventBubble(event: Event): void {
    (event as StoppableEvent).stop = true;
  }

  /**
   * Add another event to the storage.
   *
   * @param {string} selector
   * @param {Function} callback
   */
  protected addToStorage(selector: string, callback: Function): void {
    if (!(selector in this.storage)) {
      this.storage[selector] = [];
    }
    this.storage[selector].push(callback);
  }

  /**
   * Calls the events for a given event and element.
   *
   * @param event
   * @param element
   * @protected
   */
  protected callEvents(event: Event, element: Node): boolean {
    for (let selector in this.storage) {
      if (!(element as Element).matches(selector)) {
        continue;
      }
      let callbackArray = this.storage[selector];
      // Got to call them all.
      for (let i = 0; i < callbackArray.length; i++) {
        callbackArray[i](event, element);
        if ((event as StoppableEvent).stop) {
          // Our "implementation" of stopPropagation().
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Whenever a click is bubbled on a kreXX instance, we try to find
   * the according callback, and simply call it.
   *
   * @param {Event} event
   * @event click
   */
  protected handle = (event: Event): void => {
    // We stop the event in its tracks.
    event.stopPropagation();
    (event as StoppableEvent).stop = false;

    let element: Node | null = (event.target as Node);

    do {
      // We need to test the element on all selectors.
      this.callEvents(event, element);

      // Time to test the parent.
      element = element.parentNode;
      // Test if we have reached the top of the rabbit hole.
      if (element === event.currentTarget) {
        element = null;
      }

    } while (element !== null && typeof (element as Element).matches === 'function');
  };

  /**
   * Triggers an event on an element.
   *
   * @param {Element} el
   * @param {string} eventName
   */
  public triggerEvent(el: Element, eventName: string): void {
    /** @type {Event} */
    const event: Event = new Event(eventName, {bubbles: true, cancelable: false});
    el.dispatchEvent(event);
  }
}
