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
class SearchEventBinder {
  /**
   * The kreXX dom tools.
   *
   * @var {Eventhandler}
   */
  protected eventHandler: Eventhandler;

  /**
   * The result bucket.
   *
   * @var {SearchResultBucket}
   */
  protected resultBucket: SearchResultBucket;

  /**
   * The kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  /**
   * Bind  the events from the form to the search function.
   *
   * @return void
   */
  constructor(eventHandler: Eventhandler, resultBucket: SearchResultBucket) {
    this.resultBucket = resultBucket;
    this.eventHandler = eventHandler;
    this.kdt = new Kdt();

    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchcase', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchkeys', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchshort', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchlong', 'change', this.clearSearch);
    // Clear our search results, because we now have new options.
    this.eventHandler.addEvent('.kwrapper .ksearchwhole', 'change', this.clearSearch);
    // Clear the search results, because we now have a new tab selected.
    this.eventHandler.addEvent('.kwrapper .ktab', 'click', this.clearSearch);
    // Display our search options.
    this.eventHandler.addEvent('.kwrapper .koptions', 'click', this.displaySearchOptions);
    // Listen for a return key in the seach field.
    this.eventHandler.addEvent('.kwrapper .ksearchfield', 'keyup', this.searchfieldReturn);
  }

  /**
   * Reset the search results, because we now have new search options.
   *
   * @var {Event} event
   *
   * @event change
   */
  protected clearSearch = (event: Event): void => {
    // Wipe our instance data, nothing more
    const config = new SearchConfig();
    config.instance = this.kdt.getDataset((event.target as Element), 'instance');
    this.resultBucket.clearSearchResults(config);
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
  protected displaySearchOptions = (event: Event, element: Node): void => {
    // Get the options and switch the display class.
    if (element.parentNode === null) {
      return;
    }
    const nextElementSibling = (element.parentNode as Element).nextElementSibling;
    if (nextElementSibling === null) {
      return;
    }
    nextElementSibling.classList.toggle('khidden');
  };

  /**
   * Listens for a <RETURN> in the search field.
   *
   * @param {KeyboardEvent} event
   * @event keyUp
   */
  public searchfieldReturn = (event: KeyboardEvent): void => {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // If this is no <RETURN> key, do nothing.
    if (event.key !== 'Enter') {
      return;
    }

    if (event.target === null) {
      return;
    }
    const parentNode = (event.target as Node).parentNode;
    if (parentNode === null) {
      return;
    }
    this.eventHandler.triggerEvent(parentNode.querySelectorAll('.ksearchnow')[1], 'click');
  };
}
