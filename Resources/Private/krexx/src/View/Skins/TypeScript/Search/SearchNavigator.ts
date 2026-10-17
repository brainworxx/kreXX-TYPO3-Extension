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

class SearchNavigator {
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
  constructor(resultBucket: SearchResultBucket) {
    this.resultBucket = resultBucket;
    this.kdt = new Kdt();
  }
  /**
   * Moves the pointer to the next or previous element.
   *
   * @param config
   * @param element
   */
  public movePointer = (config: SearchConfig, element: Element): void => {
    const result = this.resultBucket.getResultBucket(config);
    // Set the pointer to the next or previous element
    const direction: string = this.kdt.getDataset(element, 'direction');
    if (direction === 'forward') {
      result.pointer++;
    } else {
      result.pointer--;
    }

    // Do we have an element? We may need to adjust the pointer.
    if (typeof result.data[result.pointer] === "undefined") {
      if (direction === 'forward') {
        // There is no next element, we go back to the first one.
        result.pointer = 0;
      } else {
        // There is no previous element, we go forward to the last one.
        result.pointer = result.data.length - 1;
      }
    }
  }

  /**
   * Updates the search state display with the current pointer and the total number of results.
   *
   * @param config
   */
  public updateSearchState = (config: SearchConfig): void => {
    const result = this.resultBucket.getResultBucket(config);
    const searchStateElement = this.getSearchStateElement(config);
    if (!searchStateElement) {
      return;
    }
    searchStateElement.textContent = (result.pointer + 1) + ' / ' + (result.data.length);
  }

  /**
   * Check if we have enough information to perform a search.
   *
   * @param config
   *
   * @return {boolean}
   */
  public isSearchable = (config: SearchConfig): boolean => {
    const searchStateElement = this.getSearchStateElement(config);
    if (!searchStateElement) {
      return false;
    }
    if (config.searchtext.length === 0) {
      // Not enough chars as a searchtext!
      searchStateElement.textContent = this.kdt.translations.translate('tsEnterText');
      return false;
    }

    // We only search for more than 3 chars.
    if (config.searchtext.length < 3 && !config.searchWhole) {
      // Not enough chars as a searchtext!
      searchStateElement.textContent = this.kdt.translations.translate('tsTooSmall');
      return false;
    }

    return true;
  }

  /**
   * Retrieves the search state element from the configuration.
   *
   * @param config
   * @protected
   */
  protected getSearchStateElement(config: SearchConfig): Element | null {
    return config.node?.querySelector('.ksearch-state') ?? null;
  }
}
