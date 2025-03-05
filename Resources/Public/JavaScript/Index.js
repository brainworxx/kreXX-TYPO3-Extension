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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

(function (TYPO3) {
    "use strict";

    /**
     * @namespace tabs
     */
    function tabs() {}

    /**
     * Initializing the tabs handling.
     *
     * @event DOMContentLoaded
     */
    tabs.onDocumentReady = function () {
        let elements = document.querySelectorAll('.nav-tabs li');

        for (let i = 0; i < elements.length; i++) {
            elements[i].addEventListener('click', tabs.toggle);
        }
    };

    /**
     * Toggle the tab on click.
     *
     * @event click
     * @param {Event} event
     */
    tabs.toggle = function (event) {
        let activeTab = document.querySelector('.nav-tabs li.active');
        let activeContent = document.querySelector('.tab-content .tab-pane.active');

        if (activeTab !== null) {
            activeTab.classList.remove('active');
        }

        if (activeContent !== null) {
            activeContent.classList.remove('active');
        }

        let li = event.target.parentNode;

        if (li !== null) {
            li.classList.add('active');
            document.getElementById(li.getAttribute('data-tab')).classList.add('active');
        }
    };

    /**
     * @namespace formSupport
     */
    function formSupport(){}

    /**
     * Initializing the form support.
     *
     * @event DOMContentLoaded
     */
    formSupport.onDocumentReady = function () {
        let checkboxes = document.querySelectorAll('[id^="factory."]');

        for (let i = 0, n = checkboxes.length; i < n; i++) {
            formSupport.toggle(checkboxes[i]);
            checkboxes[i].addEventListener('click', function () {
                formSupport.toggle(this);
            }, true);
        }


        document.getElementById('main-save').addEventListener('click', function () {
            let form = document.getElementById('save-form');
            if (typeof form === 'object' && form != null) {
                form.submit();
            }
        }, true);

        document.getElementById('mode-switch').addEventListener('change', formSupport.changeMode);

        document.getElementById('clear-cookies').addEventListener('click', function () {
            // We do not delete the cookie, we simply remove all settings in it.
            /** @type {Object} */
            let settings = {};
            /** @type {Date} */
            let date = new Date();
            date.setTime(date.getTime() + (99 * 24 * 60 * 60 * 1000));
            /** @type {string} */
            let expires = 'expires=' + date.toUTCString();
            document.cookie = 'KrexxDebugSettings=' + JSON.stringify(settings) + '; ' + expires + '; path=/';

            ajaxRefresh.message({
                class: 'success',
                text: 'All cookie settings were removed!'
            })
        }, true);
    };

    /**
     * When activating the checkbox behind the form element, we deactivat the
     * form element, belonging to the checkbox.
     *
     * @param {HTMLInputElement} checkbox
     */
    formSupport.toggle = function (checkbox) {
        // Find the corresponding element.
        let id = checkbox.id.split('.');
        let element = document.getElementById(id[1]);
        let fallback = document.getElementById(id[1] + '-fallback');

        if (typeof element === 'object') {
            if (checkbox.checked) {
                element.disabled = true;
                element.parentNode.classList.remove('active');
                fallback.classList.remove('disabled');
            } else {
                element.parentNode.classList.add('active');
                element.disabled = false;
                fallback.classList.add('disabled');
            }
        }
    };

    /**
     * We simply set a class on the higher dom elements to show the expert settings, if desired.
     *
     * @param {Event} event
     */
    formSupport.changeMode = function (event) {
        let modeSwitch = document.getElementById('mode-switch');
        let value = modeSwitch.options[modeSwitch.selectedIndex].value;

        if (value === 'expert') {
            document.getElementById('tabpanel').classList.add('expert-mode');
        } else {
            document.getElementById('tabpanel').classList.remove('expert-mode');
        }
    };

    /**
     * The ajax related stuff.
     *
     * @namespace ajaxRefresh
     */
    function ajaxRefresh() {}

    /**
     * The ajax translation texts.
     *
     * @type {{}}
     */
    ajaxRefresh.ajaxTranslate = {}

    /**
     * Initializing the ajax handling
     *
     * @event DOMContentLoaded
     */
    ajaxRefresh.onDocumentReady = function () {
        // Read the translation texts.
        ajaxRefresh.ajaxTranslate = window.ajaxTranslate;

        // Get a first impression.
        ajaxRefresh.call();

        // Fetch a new list every 5 seconds.
        ajaxRefresh.timeout();

        // Register the bubbelung eventhandler for the delete button.
        document.querySelector('table.krexx-logs').addEventListener('click', ajaxRefresh.deleteHandling);
    };

    /**
     * Timeout method for refreshing the logfile list.
     */
    ajaxRefresh.timeout = function () {
        setTimeout(function () {
            ajaxRefresh.call();
            ajaxRefresh.timeout();
        }, 5000);
    };

    /**
     * We store the last answer, to check if there was an update.
     *
     * @type {string}
     */
    ajaxRefresh.lastAnswer = '';

    /**
     * Refresh the logfile list.
     */
    ajaxRefresh.call = function () {
        let request = new XMLHttpRequest();
        request.open("GET", TYPO3.settings.ajaxUrls['includekrexx_refresh'], true);

        let table = document.querySelector('table.krexx-logs tbody');
        request.onload = function (event) {
            let result = JSON.parse(request.responseText);

            // Are there any logiles, at all?
            if (result.length === 0) {
                document.querySelector('#tab-1 .table-wrapper').classList.add('display-none');
                document.querySelector('#tab-1 .noresult').classList.remove('display-none');
                // Nothing more to do here.
                return;
            }

            document.querySelector('#tab-1 .table-wrapper').classList.remove('display-none');
            document.querySelector('#tab-1 .noresult').classList.add('display-none');

            let html = ajaxRefresh.generateHtml(result);

            if (ajaxRefresh.lastAnswer !== html) {
                table.innerHTML = html;
                ajaxRefresh.message({
                    text: ajaxRefresh.ajaxTranslate.updatedLoglist,
                    class: 'success'
                });
            }

            ajaxRefresh.lastAnswer = html;

        };

        request.send();
    };

    /**
     * Generate the HTML for the file list.
     *
     * @param result
     * @return {string}
     */
    ajaxRefresh.generateHtml = function (result) {
        let html = '';
        let i;

        for (let key in result) {
            if (!result.hasOwnProperty(key)) {
                continue;
            }

            let file = result[key];
            html += '<tr ' + ajaxRefresh.generateBackgroundStyle(file.name) + '>';
            html += '<td><a target="_blank" href="' + file.dispatcher + '">  ' + file.name + '</a></td><td class="meta">';
            for (i = 0; i < file.meta.length; i++) {
                html += '<div class="krexx-data-wrapper"><div class="krexx-icon-wrapper"><div class="krexx-icon ' + file.meta[i].level + '" title="' + file.meta[i].level + '"></div></div><div class="krexx-data">';
                html += '<b>' + file.meta[i].type + '</b><br />';
                html += ajaxRefresh.ajaxTranslate.in + ' ' + file.meta[i].filename + ', ' + ajaxRefresh.ajaxTranslate.line + ' ' + file.meta[i].line;
                html += '</div></div>'
            }
            if (file.meta.length > 0) {
                html += '<div class="krexx-spacer"></div>'
            }
            html += '</td>';

            html += '<td class="time">' + file.time + '</td><td class="size">' + file.size + '</td>';
            html += '<td><div class="button delete" data-id="' + file.id + '"></div></td></tr>';
        }

        return html;
    };

    /**
     * Generate the background style from the time string.
     *
     * @param string string
     *
     * @return string
     *   style=" background-color: rgba(255, 0, 0, 0.1);"
     */
    ajaxRefresh.generateBackgroundStyle = function (string) {
        let chr, hash, i, values;

        for (i = 0; i < string.length; i++) {
            chr   = string.charCodeAt(i);
            hash  = ((hash << 5) - hash) + chr;
            // Convert to 32bit integer
            hash |= 0;
        }

        values = Math.abs(hash).toString().match(/.{1,2}/g);

        return 'style=" background-color: rgba(' + values[3] + ', ' + values[2] + ', ' + values[1] + ', 0.2' + values[0] + ');"';
    };

    /**
     * Delete click handler for the delete list.
     *
     * @param event
     */
    ajaxRefresh.deleteHandling = function (event) {
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

        let confirmed = confirm(ajaxRefresh.ajaxTranslate.deletefile);

        if (confirmed === false) {
            return;
        }

        let request = new XMLHttpRequest();
        request.open("GET", TYPO3.settings.ajaxUrls['includekrexx_delete'] + '&fileid=' + id, true);

        request.onload = function (event) {
            try {
                ajaxRefresh.message(JSON.parse(request.responseText));
            } catch (e) {
                // We are not going to output the answer from the server, although
                // it is visible in the browser inspector.
                ajaxRefresh.message({class: 'error', text: ajaxRefresh.ajaxTranslate.error});
            }

            ajaxRefresh.call();
        };

        request.send();
    };

    /**
     * Ajax related messaging.
     *
     * @param {{}} json
     */
    ajaxRefresh.message = function (json) {
        let messagebox = document.querySelector('.message-container-outer');
        let message = document.createElement("div");

        message.classList.add('ajax-msg');
        message.classList.add(json.class);
        message.innerHTML = ajaxRefresh.generateIcon(json.class) + '<div class="text">' + json.text + '</div>';
        messagebox.appendChild(message);

        setTimeout(function () {
            message.classList.add('fade');
            setTimeout(function () {
                message.classList.add('remove');
                setTimeout(function () {
                    messagebox.removeChild(message);
                }, 600);
            }, 600);
        }, 4000);
    }

    ajaxRefresh.generateIcon = function (level) {
        if (level === 'success') {
            return '<span class="icon icon-size-small icon-state-default"><span class="icon-markup">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="var(--ajax-font-color)"><path d="m13.3 4.8-.7-.7c-.2-.2-.5-.2-.7 0L6.5 9.5 4 6.9c-.2-.2-.5-.2-.7 0l-.6.7c-.2.2-.2.5 0 .7l3.6 3.6c.2.2.5.2.7 0l6.4-6.4c.1-.2.1-.5-.1-.7z"/></g></svg>' +
                '</span></span>';
        }

        if (level === 'error') {
            return '<span class="icon icon-size-small icon-state-default"><span class="icon-markup">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="var(--ajax-font-color)"><path d="M11.9 5.5 9.4 8l2.5 2.5c.2.2.2.5 0 .7l-.7.7c-.2.2-.5.2-.7 0L8 9.4l-2.5 2.5c-.2.2-.5.2-.7 0l-.7-.7c-.2-.2-.2-.5 0-.7L6.6 8 4.1 5.5c-.2-.2-.2-.5 0-.7l.7-.7c.2-.2.5-.2.7 0L8 6.6l2.5-2.5c.2-.2.5-.2.7 0l.7.7c.2.2.2.5 0 .7z"/></g></svg>' +
                '</span></span>'
        }

        return '';
    }

    if (document.readyState !== 'loading') {
        tabs.onDocumentReady();
        formSupport.onDocumentReady();
        ajaxRefresh.onDocumentReady();
    } else {
        // @deprecated
        // Will be removed as soon as we drop TYPO3 11 support.
        document.addEventListener("DOMContentLoaded", function () {
            tabs.onDocumentReady();
            formSupport.onDocumentReady();
            ajaxRefresh.onDocumentReady();
        });
    }

})(TYPO3);