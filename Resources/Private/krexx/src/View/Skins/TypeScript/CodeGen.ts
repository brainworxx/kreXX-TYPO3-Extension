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

class CodeGen {
  /**
   * kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  /**
   * The result array.
   *
   * @var {string[]}
   */
  protected resultArray: string[] = [];

  /**
   * The result string.
   *
   * @var {string}
   */
  protected resultString: string = '';

  /**
   * The source data.
   *
   * @var {string}
   */
  protected sourcedata: string = '';

  /**
   * The domid.
   *
   * @var {string}
   */
  protected domid: string = '';

  /**
   * The wrapper left.
   *
   * @var {string}
   */
  protected wrapperLeft: string = '';

  /**
   * The wrapper right.
   *
   * @var {string}
   */
  protected wrapperRight: string = '';

  /**
   * Not much to do, we simply grab the KDT.
   */
  constructor() {
    this.kdt = new Kdt();
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
  public generateCode = (event: Event, element: Element): void => {
    // We don't want to bubble the click any further.
    (event as StoppableEvent).stop = true;

    this.reset();

    // Get the first element
    let el: Element | Node = this.kdt.getParents(element, 'li.kchild')[0] as Element;

    // Start the loop to collect all the date
    while (el) {
      if (!this.processElement(el as Element)) {
        break;
      }

      // Get the next el.
      el = this.kdt.getParents(el, 'li.kchild')[0];
    }

    this.processResultArray();

    // Add the wrapper that we collected so far
    this.resultString = this.wrapperLeft + this.resultString + this.wrapperRight;

    this.displayCode(element);
  };

  /**
   * Process the result array to generate the final result string.
   */
  protected processResultArray(): void
  {
    // Now we reverse our result, so that we can resolve it from the beginning.
    this.resultArray.reverse();

    for (let i = 0; i < this.resultArray.length; i++) {
      // We must check if our value is actually reachable.
      // '. . .' means it is not reachable,
      // we will stop right here and display a comment stating this.
      if (this.resultArray[i] === '. . .') {
        this.resultString = this.kdt.translations.translate('tsNoCodeGen');
        break;
      }

      // Check if we are facing a ;stop; instruction
      if (this.resultArray[i] === ';stop;') {
        this.resultString = '';
        this.resultArray[i] = '';
      }

      // We're good, value can be reached!
      if (this.resultArray[i].indexOf(';firstMarker;') !== -1) {
        // We add our result so far into the "source template"
        this.resultString = this.resultArray[i].replace(';firstMarker;', this.resultString);
      } else {
        // Normal concatenation.
        this.resultString = this.resultString + this.resultArray[i];
      }
    }
  }

  /**
   * Process the element to extract the necessary data attributes.
   *
   * @param el
   * @protected
   */
  protected processElement (el: Element): boolean
  {
    // Get the domid
    this.domid = this.kdt.getDataset((el as Element), 'domid');
    this.sourcedata = this.kdt.getDataset((el as Element), 'source');
    this.wrapperLeft = this.kdt.getDataset((el as Element), 'codewrapperLeft');
    this.wrapperRight = this.kdt.getDataset((el as Element), 'codewrapperRight');

    if (this.sourcedata === '. . .') {
      if (this.domid !== '') {
        // We need to get a new el, because we are facing a recursion, and the
        // current path is not really reachable.
        const parentEl = document.querySelector('#' + this.domid)?.parentNode;
        if (!parentEl) {
          return false;
        }
        // Get the source, again.
        this.resultArray.push(this.kdt.getDataset((parentEl as Element), 'source'));
      }
    }

    if (this.sourcedata !== '') {
      this.resultArray.push(this.sourcedata);
    }

    return true;
  }

  /**
   * Display the generated code in the next sibling element of the clicked element.
   *
   * @param {Element} element
   */
  protected displayCode (element: Element): void
  {
    // 3. Add the text
    let codedisplay: HTMLElement = (element.nextElementSibling as HTMLElement);
    codedisplay.innerHTML = '<div class="kcode-inner">' + this.resultString + '</div>';
    if (codedisplay.style.display === 'none') {
      codedisplay.style.display = '';
      this.kdt.selectText(codedisplay);
    } else {
      codedisplay.style.display = 'none';
    }
  }

  /**
   * Reset the internal state of the CodeGen instance.
   *
   * @protected
   */
  protected reset(): void
  {
    this.resultArray = [];
    this.resultString = '';
    this.sourcedata = '';
    this.domid = '';
    this.wrapperLeft = '';
    this.wrapperRight = '';
  }
}
