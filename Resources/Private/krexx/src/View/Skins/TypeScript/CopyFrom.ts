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

class CopyFrom {
  /**
   * Not much to do, we simply grab the KDT.
   */
  constructor() {
    this.kdt = new Kdt();
  }

  /**
   * kreXX dom tools.
   *
   * @var {Kdt}
   */
  protected kdt: Kdt;

  /**
   * This function retrieves the main elements needed for the copyFrom function.
   *
   * @param {Element} element
   */
  protected retrieveMainElements = (element: Element): {
    orgNest: HTMLElement;
    orgEl: Node;
    parentElement: Element;
    domid: string
  } | false => {
    // Get the DOM id of the original analysis.
    const domid: string = this.kdt.getDataset(element, 'domid');
    if (domid === '' || element.parentNode === null) {
      // Do nothing.
      return false;
    }

    // Get the analysis data.
    const orgNest: HTMLElement | null = document.querySelector('#' + domid);
    // Get the EL of the data (element with the arrow).
    const orgEl: Node | null | undefined = orgNest?.previousElementSibling;
    // Get the parent element of the recursion.
    const parentElement: Element | null = element.parentNode as Element;

    if (orgNest && orgEl && parentElement) {
      return {orgNest, orgEl, parentElement,  domid};
    }

    return false;
  }

  /**
   * This function prepares the copy of the analysis data and the element
   * with the arrow. It changes the key of the just cloned EL to the one from
   * the recursion and removes all ids from the copy to avoid double ids.
   *
   * @param element
   * @param newEl
   */
  protected prepareCopy = (element: Element, newEl: Element): void => {
    // Change the key of the just cloned EL to the one from the recursion.
    (this.kdt.findInDomlistByClass(newEl.children, 'kname') as HTMLElement).innerHTML =
      (this.kdt.findInDomlistByClass(element.children, 'kname') as HTMLElement).innerHTML;


    // We  need to remove the ids from the copy to avoid double ids.
    const allChildren = newEl.nextElementSibling?.getElementsByTagName("*");
    if (allChildren) {
      for (let i = 0; i < allChildren.length; i++) {
        allChildren[i].removeAttribute('id');
      }
    }
    newEl.nextElementSibling?.removeAttribute('id');
  }

  /**
   * This function prepares the buttons of the copy of the analysis data and the element
   * with the arrow. It removes the infobox from the copy, if available and adds
   * the one from the recursion.
   *
   * @param element
   * @param newEl
   */
  protected prepareButtons = (element: Element, newEl: Element): void => {
    // Remove the infobox from the copy, if available and add the one from the
    // recursion.
    const newInfobox = newEl.querySelector('.khelp');
    const newButton = newEl.querySelector('.kinfobutton');
    const realInfobox = element.querySelector('.khelp');
    const realButton = element.querySelector('.kinfobutton');

    // We don't need the infobox on newEl, so we will remove it.
    if (newInfobox !== null) {
      newInfobox.parentNode?.removeChild(newInfobox);
    }
    if (newButton !== null) {
      newButton.parentNode?.removeChild(newButton);
    }

    // We copy the Infobox from the recursion to the newEl, if it exists.
    if (realInfobox === null) {
      return;
    }

    if (realButton !== null) {
      newEl.appendChild(realButton);
    }

    newEl.appendChild(realInfobox);
  }

  /**
   * When clicked on s recursion, this function will copy the original analysis result there and delete the recursion.
   *
   * @event click
   * @param {Event} event
   *   The click event.
   * @param {Element} element
   *   The element that was clicked.
   */
  public copyFrom = (event: Event, element: Element): void => {
    const mainElements = this.retrieveMainElements(element);
    if (!mainElements) {
      return;
    }
    const {orgNest, orgEl, parentElement, domid} = mainElements;

    // Clone the analysis data and insert it after the recursion EL.
    parentElement.insertBefore(orgNest.cloneNode(true), element.nextSibling);
    // Clone the EL of the analysis data and insert it after the recursion EL.
    const newEl: Element = (orgEl.cloneNode(true) as Element);
    parentElement.insertBefore(newEl, element.nextSibling);
    // Now we add the dom-id to the clone, as a data-field. this way we can
    // make sure to always produce the right path to this value during source
    // generation.
    this.kdt.setDataset((newEl.parentNode as Element), 'domid', domid);

    this.prepareCopy(element, newEl);
    this.prepareButtons(element, newEl);

    // Remove the recursion EL.
    parentElement.removeChild(element);
  };
}
