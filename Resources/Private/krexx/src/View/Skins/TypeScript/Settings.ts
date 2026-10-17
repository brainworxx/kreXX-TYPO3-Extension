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

class Settings {
  /**
   * Our translations class.
   */
  public kdt: Kdt;

  /**
   * Init the translations and tell the frontend that we are ready.
   */
  constructor() {
    this.kdt = new Kdt();
  }

  /**
   * Reads the values from a cookie.
   *
   * @param {string} cookieName
   *   Name of the cookie.
   *
   * @return {Record<string, string>}
   *   The value, set in the cookie.
   */
  protected read(cookieName: string): Record<string, string> {
    const match: RegExpMatchArray | null = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
    let result: Record<string, string> = {};
    if (match === null) {
      return result;
    }

    try {
      result = JSON.parse(match[2]);
    } catch (error) {
      // Do nothing, we already have a fallback.
    }

    return result;
  }

  /**
   * Adds the value from a html element to the local cookie settings.
   *
   * @event change
   * @param {Event} event
   */
  public set = (event: Event): void => {
    // Prevents the default event behavior (ie: click).
    event.preventDefault();
    // Prevents the event from propagating (ie: "bubbling").
    event.stopPropagation();

    // Get the old value.
    const settings: Record<string, string> = this.read('KrexxDebugSettings');

    // Get new settings from element.
    const newValue: string | number = (event.target as HTMLInputElement).value.replace('"', '').replace("'", '');
    const valueName: string = (event.target as HTMLInputElement).name.replace('"', '').replace("'", '');
    settings[valueName] = newValue;

    // Save it.
    const date: Date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    const expires: string = 'expires=' + date.toUTCString();

    // Remove a possible old value from a previous version.
    document.cookie = 'KrexxDebugSettings=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    // Set the new one.

    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
    // Feedback about update.
    alert(valueName + ' --> ' + newValue + '\n\n' + this.kdt.translations.translate('tsPleaseReload'));
  };

  /**
   * Resets all values in the local cookie settings.
   */
  public reset = (): void => {
    // We do not delete the cookie, we simply remove all settings in it.
    const date: Date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    const expires: string = 'expires=' + date.toUTCString();

    document.cookie = 'KrexxDebugSettings={}; ' + expires + '; path=/';
    alert(this.kdt.translations.translate('tsConfigReset') + '\n\n' + this.kdt.translations.translate('tsPleaseReload'));
  }
}
