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

class Search {
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
   * The kreXX dom tools.
   *
   * @var {Eventhandler}
   */
  protected eventHandler: Eventhandler;

  /**
   * The jump-to implementation.
   */
  protected jumpTo: Function;

  protected navigation: SearchNavigator;

  /**
   * Inject the event handler.
   *
   * @param {Eventhandler} eventHandler
   * @param {Function} jumpTo
   */
  constructor(eventHandler: Eventhandler, jumpTo: Function) {
    this.kdt = new Kdt();
    this.eventHandler = eventHandler;
    this.jumpTo = jumpTo;
    this.resultBucket = new SearchResultBucket();
    this.navigation = new SearchNavigator(this.resultBucket);
    new SearchEventBinder(eventHandler, this.resultBucket);
  }

  /**
   * Prepare the search configuration.
   *
   * @param {Element} element
   * @param {Element} instanceElement
   *
   * @return {SearchConfig}
   */
  protected prepareConfig = (element: Element, instanceElement: Element): SearchConfig => {
    const config: SearchConfig = new SearchConfig();
    config.node = element;
    config.searchtext = (element.querySelector('.ksearchfield') as HTMLInputElement).value;
    config.caseSensitive = (element.querySelector('.ksearchcase') as HTMLInputElement).checked;
    config.searchKeys = (element.querySelector('.ksearchkeys') as HTMLInputElement).checked;
    config.searchShort = (element.querySelector('.ksearchshort') as HTMLInputElement).checked;
    config.searchLong = (element.querySelector('.ksearchlong') as HTMLInputElement).checked;
    config.searchWhole = (element.querySelector('.ksearchwhole') as HTMLInputElement).checked;
    config.instance = this.kdt.getDataset(instanceElement, 'instance');

    // Apply our configuration.
    if (!config.caseSensitive) {
      config.searchtext = config.searchtext.toLowerCase();
    }

    return config;
  }

  /**
   * Prepare the nodes for the search.
   *
   * @param element
   *
   * @return {Element|null}
   */
  protected prepareNodes = (element: Element): Element | null => {
    const parentNode: Element = element.parentNode as Element;
    if (parentNode === null) {
      return null;
    }
    const grandParentNode = parentNode.parentNode as Element;
    if (grandParentNode === null) {
      return null;
    }
    const parentSibling = (parentNode as HTMLElement).nextElementSibling
    if (parentSibling !== null) {
      // Hide the search options.
      this.kdt.addClass([parentSibling], 'khidden');
    }

    return grandParentNode;
  }

  /**
   * Initiates the search.
   *
   * @param {Event} event
   *   The click event.
   * @param {Element} element
   *   The element that was clicked.
   */
  public performSearch = (event: Event, element: Element): void => {
    const root = this.prepareNodes(element);
    if (root === null) {
      return;
    }

    // Stitching together our configuration.
    const config = this.prepareConfig(root, element);

    // Nothing to search for.
    if (!this.navigation.isSearchable(config)) {
      return;
    }

    this.retrievePayload(config);
    this.expandCollapsedNodes(config);
    this.refreshResultlist(config);
    this.navigation.movePointer(config, element);

    const result = this.resultBucket.getResultBucket(config);
    const current = result.data[result.pointer];
    if (current) {
      // Now we simply jump to the element in the array.
      this.jumpTo(current);
    }

    this.navigation.updateSearchState(config);
  };

  /**
   * Expands all collapsed nodes, so that we can search through them.
   *
   * @param config
   */
  protected expandCollapsedNodes = (config: SearchConfig): void => {
    const collapsed: NodeList | undefined = config.payload?.querySelectorAll('.kcollapsed');
    if (collapsed !== undefined) {
      for (let i: number = 0; i < collapsed.length; i++) {
        this.eventHandler.triggerEvent((collapsed[i] as Element), 'click');
      }
    }
  }

  /**
   * Retrieve the payload for the search.
   *
   * @param {SearchConfig} config
   */
  protected retrievePayload = (config: SearchConfig): void => {
    // We may need to search in a specific part of the payload.
    const tab: Element | null = document.querySelector('#' + config.instance + ' .ktab.kactive');
    let additionalClasses: string = '';

    if (tab !== null) {
      additionalClasses = ' .' + this.kdt.getDataset(tab, 'what');
    }

    config.payload = document.querySelector('#' + config.instance + ' .kbg-wrapper' + additionalClasses);
  }

  /**
   * Resets our search list and fills it with results.
   *
   * @param {SearchConfig} config
   */
  protected refreshResultlist = (config: SearchConfig): void => {
    if (this.resultBucket.hasSearchResults(config)) {
      return;
    }
    // Remove all previous highlights
    this.kdt.removeClass('.ksearch-found-highlight', 'ksearch-found-highlight');

    // Poll out payload for elements to search
    const list: NodeList | undefined = config.payload?.querySelectorAll(this.buildSelector(config));
    if (list === undefined) {
      return;
    }

    // Get a new list of elements
    const result = this.resultBucket.getResultBucket(config);
    for (let i: number = 0; i < list.length; ++i) {
      this.updateResult(list[i] as Element, config, result);
    }

    // Reset our index.
    // When nothing is found, the pointer is toggeling -1, to show that there is something happening.
    result.pointer = -1;
  };

  /**
   * Updates the search result with the current node if it matches the search criteria.
   *
   * @param node
   * @param config
   * @param result
   * @protected
   */
  protected updateResult(node: Element, config: SearchConfig, result: SearchResult): void {
    // Does it contain our search string?
    let textContent = node.textContent ?? '';
    if (!config.caseSensitive) {
      textContent = textContent.toLowerCase();
    }

    if (
      (config.searchWhole && textContent === config.searchtext)
      || (!config.searchWhole && textContent.indexOf(config.searchtext) > -1)
    ) {
      node.classList.toggle('ksearch-found-highlight');
      result.data.push(node);
    }
  }

  /**
   * Builds a selector string based on the search configuration.
   *
   * @param {SearchConfig} config
   * @returns {string}
   */
  protected buildSelector(config: SearchConfig): string {
    const selector: string[] = [];

    if (config.searchKeys) {
      selector.push('li.kchild span.kname');
    }
    if (config.searchShort) {
      selector.push('li.kchild span.kshort');
    }
    if (config.searchLong) {
      selector.push('li div.kpreview');
    }

    return selector.join(', ');
  }
}
