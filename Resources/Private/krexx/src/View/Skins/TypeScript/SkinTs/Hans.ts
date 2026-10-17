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

class Hans {
  /**
   * kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  protected CodeGen: CodeGen;

  /**
   * Our dragable lib.
   *
   * @var {Draxx}
   */
  protected draxx!: Draxx;

  protected copyFrom: CopyFrom;

  /**
   * Out DOM search.
   *
   * @var {Search}
   */
  protected search: Search | undefined;

  /**
   * The event handler.
   *
   * @var {Eventhandler}
   */
  protected eventHandler: Eventhandler | undefined;

  /**
   * Here we store the selectors for the ruin initialisation.
   *
   * @var {Selectors}
   */
  protected selectors: Selectors;

  /**
   * The last jumpTo to interval.
   *
   * @var {number}
   */
  protected jumpToInterval: number = 0;

  protected settings: Settings;

  /**
   * Defining all selectors for the run method.
   */
  constructor() {
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
    // Init our libs before usage.
    this.kdt = new Kdt();
    this.CodeGen = new CodeGen();
    this.settings = new Settings();
    this.copyFrom = new CopyFrom();
  }

  /**
   * Initialize the draggable.
   */
  protected initDraxx = (): void => {
    this.draxx = new Draxx(
      '.kwrapper',
      '.kheadnote',
      function () {
        const searchWrapper: NodeList = document.querySelectorAll('.search-wrapper');
        let viewportOffset: DOMRect;
        for (let i = 0; i < searchWrapper.length; i++) {
          viewportOffset = (searchWrapper[i] as HTMLElement).getBoundingClientRect();
          (searchWrapper[i] as HTMLElement).style.position = 'fixed';
          (searchWrapper[i] as HTMLElement).style.top = viewportOffset.top + 'px';
        }
      },
      function () {
        const searchWrapper = document.querySelectorAll('.search-wrapper');
        for (let i = 0; i < searchWrapper.length; i++) {
          (searchWrapper[i] as HTMLElement).style.position = 'absolute';
          (searchWrapper[i] as HTMLElement).style.top = '';
        }
      }
    );
  }

  /**
   * Getting our act together.
   */
  public run(): void {

    if (this.kdt.beenHere()) {
      // We only do this once.
      return;
    }

    // Initialize the draggable.
    this.initDraxx();

    this.kdt.setJumpTo(this.jumpTo);
    this.eventHandler = new Eventhandler(this.selectors.eventHandler);
    this.search = new Search(this.eventHandler, this.jumpTo);

    // In case we are handling a broken html structure, we must move everything
    // to the bottom.
    this.kdt.moveToBottom(this.selectors.moveToBottom);

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
    this.eventHandler.addEvent(this.selectors.setSetting, 'change', this.settings.set);

    /**
     * Register cookie reset function on the reset button.
     *
     * @event click
     *   Resets the local settings in the settings cookie,
     *   when the reset button ic clicked.
     */
    this.eventHandler.addEvent(this.selectors.resetSetting, 'click', this.settings.reset);

    /**
     * Register the recursions resolving.
     *
     * @event click
     *   When a recursion is clicked, kreXX tries to locate the
     *   first output of the object and highlight it.
     */
    this.eventHandler.addEvent(this.selectors.copyFrom, 'click', this.copyFrom.copyFrom);

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
    this.eventHandler.addEvent(this.selectors.generateCode, 'click', this.CodeGen.generateCode);

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
   * Hides or displays the nest under an expandable element.
   *
   * @event click
   * @param {Event} event
   *   The click event.
   * @param {Node} element
   *   The element that was clicked.
   */
  protected toggle = (event: Event, element: Element): void => {
    element.classList.toggle('kopened');

    // Toggle all siblings.
    let sibling: Element | null = element.nextElementSibling;
    if (sibling === null) {
      return;
    }

    do {
      sibling.classList.toggle('khidden');
      sibling = sibling.nextElementSibling;
    } while (sibling);
  };

  /**
   * Removes the old highlight and sets the new one.
   *
   * @param {Element} el
   * @param {boolean} noHighlight
   */
  protected setHighlighting(el: Element, noHighlight: boolean): void {
    const nests: Node[] = this.kdt.getParents(el, '.knest');

    // Show them.
    this.kdt.removeClass(nests, 'khidden');
    // We need to expand them all.
    for (let i = 0; i < nests.length; i++) {
      const prevSibling: Element | null = (nests[i] as Element).previousElementSibling;
      if (prevSibling === null) {
        continue;
      }
      this.kdt.addClass([prevSibling], 'kopened');
    }

    if (!noHighlight) {
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
  protected jumpTo = (el: Element, noHighlight: boolean): void => {
    this.setHighlighting(el, noHighlight);

    // Getting our scroll container
    let destination: number;
    let container: HTMLElement | null = document.querySelector('html');
    if (container === null) {
      return;
    }
    // The html container may not accept any scrollTop value.
    ++container.scrollTop;
    if (container.scrollTop === 0 || container.scrollHeight <= container.clientHeight) {
      container = document.querySelector('body');
    }
    if (container === null) {
      return;
    }
    --container.scrollTop;
    destination = el.getBoundingClientRect().top + container.scrollTop - 200;

    const diff: number = Math.abs(container.scrollTop - destination);
    if (diff < 150) {
      // No need to jump there
      return;
    }

    this.scrollContainer(container, destination, diff);
  };

  /**
   * Scrolls the container to the destination.
   *
   * @param {HTMLElement} container
   *   The container to scroll.
   * @param {number} destination
   *   The destination to scroll to.
   * @param {number} diff
   *  The difference between the current scroll position and the destination.
   */
  protected scrollContainer = (container: HTMLElement, destination: number, diff: number): void => {
    // Getting the direction
    let step: number;
    if (container.scrollTop < destination) {
      // Forward.
      step = Math.round(diff / 12);
    } else {
      // Backward.
      step = Math.round(diff / 12) * -1;
    }

    // We also need to check if the setting of the new value was successful.
    let lastValue: number = container.scrollTop;

    // Make sure to end the last interval before starting a new one.
    clearInterval(this.jumpToInterval);
    const interval: number = this.jumpToInterval = setInterval(function () {
      container.scrollTop += step;
      if (Math.abs(container.scrollTop - destination) <= Math.abs(step) || container.scrollTop === lastValue) {
        // We are here now, the next step would take us too far.
        // So we jump there right now and then clear the interval.
        container.scrollTop = destination;
        clearInterval(interval);
      }
      lastValue = container.scrollTop;
    }, 10);
  }

  /**
   * Shows a "fast" closing animation and then removes the kreXX window from the markup.
   *
   * @event click
   * @param {Event} event
   *   The click event.
   * @param {Element} element
   *   The element that was clicked.
   */
  protected close = (event: Event, element: Element): void => {
    const instance: string = this.kdt.getDataset(element, 'instance');
    const elInstance: HTMLElement | null = document.querySelector('#' + instance);

    // Remove it nice and "slow".
    let opacity: number = 1;
    const interval: number = setInterval(function () {
      if (opacity < 0) {
        // It's invisible now, so we clear the timer and remove it from the DOM.
        clearInterval(interval);
        if (elInstance === null || elInstance.parentNode === null) {
          return;
        }
        elInstance.parentNode.removeChild(elInstance);
        return;
      }
      opacity -= 0.1;
      if (elInstance === null) {
        return;
      }
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
  protected disableForms(): void {
    const elements: NodeList = document.querySelectorAll('.kwrapper .kconfiguration .keditable input, .kwrapper .kconfiguration .keditable select');
    for (let i = 0; i < elements.length; i++) {
      (elements[i] as HTMLInputElement).disabled = true;
    }
  }

  /**
   * Toggle the display of the infobox.
   *
   * @param {Event} event
   * @param {Element} element
   *
   * @event keyUp
   */
  protected displayInfoBox = (event: Event, element: Element): void => {
    // We don't want to bubble the click any further.
    (event as StoppableEvent).stop = true;

    // Find the corresponding info box.
    const box: HTMLElement = (element.nextElementSibling as HTMLElement);

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
  protected displaySearch = (event: Event, element: Node): void => {
    const instance: string = this.kdt.getDataset((element as Element), 'instance');
    const search: HTMLElement | null = document.querySelector('#search-' + instance);
    if (search === null) {
      return;
    }
    let viewportOffset;

    // Toggle display / hidden.
    if (search.classList.contains('khidden')) {
      // Display it.
      search.classList.remove('khidden');
      (search.querySelector('.ksearchfield') as HTMLElement).focus();
      search.style.position = 'absolute';
      search.style.top = '';
      viewportOffset = search.getBoundingClientRect();
      search.style.position = 'fixed';
      search.style.top = viewportOffset.top + 'px';
    } else {
      // Hide it.
      search.classList.add('khidden');
      this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');
      search.style.position = 'absolute';
      search.style.top = '';
    }
  };
}
