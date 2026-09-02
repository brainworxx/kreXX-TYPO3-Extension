import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';
import RegularEvent from '@typo3/core/event/regular-event.js';
import Notification from '@typo3/backend/notification.js';
import Modal from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';

class KrexxBackend {
  /**
   * @type {AjaxRequest}
   */
  request = {};

  /**
   * @type {HTMLButtonElement}
   */
  buttonModeToggle = {};

  /**
   * @type {HTMLButtonElement}
   */
  buttonCookie = {};

  /**
   * @type {HTMLAnchorElement}
   */
  anchorModeSimple = {};

  /**
   * @type {HTMLAnchorElement}
   */
  anchorModeExpert = {};

  /**
   * @type {NodeListOf<HTMLElement>}
   */
  expertElements = {};

  /**
   * @type {HTMLButtonElement}
   */
  cookieButton = {};

  /**
   * @type {NodeListOf<HTMLInputElement>}
   */
  factorySettingsElements = {};

  /**
   * @type {HTMLBodyElement}
   */
  debugTableBody = {};

  /**
   * @type {number}
   */
  ajaxTimeout = 0;

  /**
   * @type {string}
   */
  ajaxLastAnswer = ''

  /**
   * Constructor.
   */
	constructor() {
    DocumentService.ready().then(this.onDocumentReady.bind(this));
	}

  /**
   * Load all elements and then register events.
   */
  onDocumentReady() {
    this.loadElements();
    this.registerEvents();
    this.initFormElements();
    this.ajaxRefresh();
  }

  /**
   * Load all class elements from the DOM.
   */
  loadElements() {
    this.request = new AjaxRequest(TYPO3.settings.ajaxUrls['includekrexx_refresh']);
    this.buttonModeToggle = document.querySelector('button.dropdown-toggle');
    this.anchorModeExpert = document.querySelector('a[data-mode="expert"]');
    this.anchorModeSimple = document.querySelector('a[data-mode="simple"]');
    this.cookieButton = document.querySelector('button.cookies');
    this.expertElements = document.querySelectorAll('.expert');
    this.buttonCookie = document.querySelector('button.cookies');
    this.factorySettingsElements = document.querySelectorAll('[id^="factory."]');
    this.debugTableBody = document.querySelector('table.krexx-logs tbody');
  }

  /**
   * Register all events for the class.
   */
  registerEvents() {
    new RegularEvent('click', this.toggleModeEasy.bind(this)).bindTo(this.anchorModeSimple);
    new RegularEvent('click', this.toggleModeExpert.bind(this)).bindTo(this.anchorModeExpert);
    new RegularEvent('click', this.clearCookie.bind(this)).bindTo(this.buttonCookie);
    for (const element of this.factorySettingsElements) {
      new RegularEvent('change', this.toggleFactorySetting.bind(this)).bindTo(element);
    }
    new RegularEvent('click', this.deleteLogFile.bind(this)).bindTo(this.debugTableBody);
  }

  /**
   * Initialise form elements on page load.
   */
  initFormElements() {
    this.toggleModeEasy();
    for (const element of this.factorySettingsElements) {
      this.toggleFactorySetting({ target: element });
    }
  }

  /**
   * Delete a log file after confirmation.
   *
   * @param event
   */
  deleteLogFile(event) {
    let target = event.target;
    let id;

    // Retrieve the id.
    if (target.hasAttribute('data-id')) {
      id = target.getAttribute('data-id')
    } else if (target.parentElement.hasAttribute('data-id')) {
      id = target.parentElement.getAttribute('data-id')
    }

    // Found anything?
    if (typeof id === 'undefined' || id === null) {
      // No id found. Early return.
      return;
    }

    Modal.confirm(
      TYPO3.lang.warning,
      TYPO3.lang.deletefile,
      Severity.warning,
      [
        {
          text: TYPO3.lang.yes,
          active: true,
          trigger: function() {
            let deleteRequest = new AjaxRequest(TYPO3.settings.ajaxUrls['includekrexx_delete'] + '&fileid=' + id);
            deleteRequest.get().then(this.updateDeleteFile.bind(this)).catch(this.onAjaxError);
            this.ajaxTimeout = 0;
            this.ajaxRefresh();
            Modal.dismiss();
          }.bind(this)
        },
        {
          text: TYPO3.lang.no,
          trigger: function() {
            Modal.dismiss();
          }
        }
      ]
    );
  }

  async updateDeleteFile(response) {
    let result = JSON.parse(await response.resolve());

    Notification.success(TYPO3.lang.ajaxSuccess, result.text, 5);
  }

  /**
   * Refresh the debug table every 5 seconds.
   */
  ajaxRefresh() {
    setTimeout(function () {
      this.request.get().then(this.updateDebugTable.bind(this)).catch(this.onAjaxError);
      this.ajaxRefresh();
      this.ajaxTimeout = 5000;
    }.bind(this), this.ajaxTimeout);
  }

  /**
   * Display an error notification when the ajax request fails.
   *
   * @returns void
   */
  async onAjaxError() {
    Notification.error(TYPO3.lang.ajaxError, TYPO3.lang.parsingError, 4);
  }

  /**
   * Update the debug table with the response from the server.
   *
   * @param response
   * @returns void
   */
  async updateDebugTable(response) {
    let result = JSON.parse(await response.resolve());

    // Are there any logiles, at all?
    if (result.length === 0) {
      document.querySelector('#tab-1 .table-wrapper').classList.add('d-none');
      document.querySelector('#tab-1 .noresult').classList.remove('d-none');
      // Nothing more to do here.
      return;
    }

    document.querySelector('#tab-1 .table-wrapper').classList.remove('d-none');
    document.querySelector('#tab-1 .noresult').classList.add('d-none');

    let html = this.generateDebugTableContent(result);
    if (html === this.ajaxLastAnswer) {
      // No changes, do not update the table.
      return;
    }
    this.ajaxLastAnswer = html;
    // Inform the user that the table has been updated.
    Notification.success(TYPO3.lang.ajaxSuccess, TYPO3.lang.updatedLoglist, 5);

    this.debugTableBody.innerHTML = html;
  }

  /**
   * Generate the content of the debug table.
   *
   * @param result
   * @returns {string}
   */
  generateDebugTableContent(result) {
    let html = '';
    let i;

    for (let key in result) {
      if (!result.hasOwnProperty(key)) {
        continue;
      }

      let file = result[key];
      html += '<tr ' + this.generateBackgroundStyle(file.name) + '>';
      html += '<td class="align-top"><a target="_blank" href="' + file.dispatcher + '">  ' + file.name + '</a></td><td class="meta">';
      for (i = 0; i < file.meta.length; i++) {
        html += '<div class="border-bottom mb-2 pb-2">' + this.generateIcon(file.meta[i].level) + '<div class="d-inline-block align-middle">';
        html += '<b>' + file.meta[i].type + '</b><br />';
        html += TYPO3.lang.in + ' ' + file.meta[i].filename + ', ' + TYPO3.lang.line + ' ' + file.meta[i].line;
        html += '</div></div>'
      }
      if (file.meta.length > 0) {
        html += '<div class="krexx-spacer"></div>'
      }
      html += '</td>';

      html += '<td class="align-top d-none d-lg-table-cell">' + file.time + '</td><td class="align-top d-none d-lg-table-cell">' + file.size + '</td>';
      html += '<td class="align-top"><div class="btn btn-default delete" data-id="' + file.id + '"><typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon></div></td></tr>';
    }

    return html;
  }

  /**
   * Generate an icon based on the log level.
   *
   * @param level
   * @returns {string}
   */
  generateIcon(level) {
    let icon = '';
    switch (level) {
      case 'debug':
        icon = 'actions-debug';
        break;
      case 'timer':
        icon = 'actions-clock';
        break;
      case 'backtrace':
        icon = 'actions-viewmode-list';
        break;
      default:
        icon = 'actions-file';
    }
    return '<div class="d-none d-lg-inline-block align-middle me-2"><typo3-backend-icon identifier="' + icon + '" size="medium"></typo3-backend-icon></div>';
  }

  /**
   * Generate a background color based on the filename.
   *
   * @param filename
   * @returns {string}
   */
  generateBackgroundStyle(filename) {
    let chr, hash, i, values, light, dark;

    for (i = 0; i < filename.length; i++) {
      chr = filename.charCodeAt(i);
      hash = ((hash << 5) - hash) + chr;
      // Convert to 32bit integer
      hash |= 0;
    }

    values = Math.abs(hash).toString().match(/.{1,2}/g);
    dark = '--krexx-row-bg-dark:   rgba(' + values[3] + ', ' + values[2] + ', ' + values[1] + ', 0.7' + values[0] + ');';
    light = '--krexx-row-bg-light: rgba(' + values[3] + ', ' + values[2] + ', ' + values[1] + ', 0.2' + values[0] + ');';

    return 'style="' + dark + '; ' + light + '"';
  }

  /**
   * Toggle factory setting for the associated form element.
   *
   * @param {Event} event
   */
  toggleFactorySetting(event) {
    let checkbox = event.target;
    // Find the corresponding element.
    let id = checkbox.id.split('.');
    let element = document.getElementById(id[1]);

    if (typeof element === 'object') {
      if (checkbox.checked) {
        element.parentNode.classList.add('active');
        element.disabled = false;
        element.value = element.dataset.value;
      } else {
        element.disabled = true;
        element.parentNode.classList.remove('active');
        element.dataset.value = element.value;
        element.value = element.dataset.fallback;
      }
    }
  }

  /**
   * We do not delete the cookie, we simply remove all settings in it.
   */
  clearCookie() {
    let settings = {};
    let date = new Date();
    date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
    let expires = 'expires=' + date.toUTCString();
    document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';
    Notification.success(TYPO3.lang.ajaxSuccess, TYPO3.lang.deletedCookies, 5);
  }

  /**
   * Toggle to easy mode.
   */
  toggleModeEasy() {
    this.anchorModeSimple.dataset.dropdowntoggleStatus = 'active';
    this.anchorModeExpert.dataset.dropdowntoggleStatus = '';
    this.buttonModeToggle.textContent = this.anchorModeSimple.title
    for (const element of this.expertElements) {
      element.style.display = 'none';
    }
  }

  /**
   * Toggle to expert mode.
   */
  toggleModeExpert() {
    this.anchorModeSimple.dataset.dropdowntoggleStatus = '';
    this.anchorModeExpert.dataset.dropdowntoggleStatus = 'active';
    this.buttonModeToggle.textContent = this.anchorModeExpert.title
    for (const element of this.expertElements) {
      element.style.display = '';
    }
  }
}

new KrexxBackend();
