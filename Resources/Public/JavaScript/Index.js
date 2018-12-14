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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

    document.addEventListener("DOMContentLoaded", function () {
        tabs.onDocumentReady();
        formSupport.onDocumentReady();
        ajaxRefresh.onDocumentReady();
    });

    /**
     * @namespace tabs
     */
    function tabs() {}

    /**
     * @event DOMContentLoaded
     */
    tabs.onDocumentReady = function () {
        var elements = document.querySelectorAll('.nav-tabs li');

        for (var i = 0; i < elements.length; i++) {
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
        var activeTab = document.querySelector('.nav-tabs li.active');
        var activeContent = document.querySelector('.tab-content .tab-pane.active');

        if (activeTab !== null) {
            activeTab.classList.remove('active');
        }

        if (activeContent !== null) {
            activeContent.classList.remove('active');
        }

        var li = event.target.parentNode;

        if (li !== null) {
            li.classList.add('active');
            document.getElementById(li.getAttribute('data-tab')).classList.add('active');
        }
    };

    /**
     * @namespace formSupport
     */
    function formSupport(){}

    formSupport.onDocumentReady = function () {
        var checkboxes = document.getElementsByName('tx_includekrexx_tools_includekrexxkrexxconfiguration[settings][factory]');

        for (var i = 0, n = checkboxes.length; i < n; i++) {
            formSupport.toggle(checkboxes[i]);
            checkboxes[i].addEventListener('click', function () {
                formSupport.toggle(this);
            }, true);
        }

        var saveButton = document.getElementById('main-save');
        if (typeof saveButton === 'object' && saveButton !== 'undefined' && saveButton != null) {
            saveButton.addEventListener('click', function () {
                var form = document.getElementById('save-form');
                if (typeof form === 'object' && form !== 'undefined' && form != null) {
                    form.submit();
                }
            }, true);
        }
    };

    /**
     * When activating the checkbox behind the form element, we deactivat the
     * form element, belonging to the checkbox.
     *
     * @param {HTMLInputElement} checkbox
     */
    formSupport.toggle = function (checkbox) {
        // Find the corresponding element.
        var id = checkbox.id.split('.');
        if (typeof id[1] !== 'undefined') {
            var element = document.getElementById(id[1]);
        }

        if (typeof element === 'object' && typeof element !== 'undefined' && element != null) {
            if (checkbox.checked) {
                element.disabled = true;
                element.parentNode.classList.remove('active');
                checkbox.parentNode.querySelector('.chkwrapper').classList.remove('checked');
            } else {
                element.disabled = false;
                element.parentNode.classList.add('active');
                checkbox.parentNode.querySelector('.chkwrapper').classList.add('checked');
            }
        }
    };

    /**
     * The ajax related stuff.
     *
     * @namespace ajaxRefresh
     */
    function ajaxRefresh() {}

    ajaxRefresh.onDocumentReady = function () {
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
     * Refresh the logfile list.
     */
    ajaxRefresh.call = function () {
        var request = new XMLHttpRequest();
        request.open(
            "GET",
            TYPO3.settings.ajaxUrls['includekrexx::RefreshLoglist'],
            true
        );

        request.onload = function (event) {
            var result = JSON.parse(request.responseText);
            var html = '';
            var table = document.querySelector('table.krexx-logs tbody');

            for (var key in result) {
                if (!result.hasOwnProperty(key)) {
                    continue;
                }
                html += '<tr>';
                var file = result[key];
                html += '<td><a target="_blank" href="' + file.dispatcher + '"><div class="krexx-icon"></div></a></td>'
                html += '<td><a target="_blank" href="' + file.dispatcher + '">  ' + file.name + '</a></td>';

                html += '<td>';
                for (var i = 0; i < file.meta.length; i++) {
                    html += 'Analysis of <b>' + file.meta[i].varname + '</b> in ' + file.meta[i].filename + '<br />';
                    html += 'in line ' + file.meta[i].line + '<br />';
                }
                if (file.meta.length > 0) {
                    html += '<div class="krexx-spacer"></div>'
                }
                html += '</td>';

                html += '<td>' + '<div class="delete" data-id="' + file.id + '"></div>' + '</td>';
                html += '<td>' + file.time + '</td>';
                html += '<td>' + file.size + '</td>';
                html += '</tr>';
            }
            if (table.innerHTML !== html) {
                table.innerHTML = html;
            }
        };

        request.send();
    };

    /**
     * Delete click handler for the delete list.
     *
     * @param event
     */
    ajaxRefresh.deleteHandling = function (event) {
        var target = event.target;
        var id;

        // Retrieve the id.
        if (target.hasAttribute('data-id')) {
            id = target.getAttribute('data-id')
        } else {
            if (target.parentElement.hasAttribute('data-id')) {
                id = target.parentElement.getAttribute('data-id')
            }
        }
        // Found anything?
        if (typeof id === 'undefined' || id === null) {
            // No id found. Early return.
            return;
        }


        var request = new XMLHttpRequest();
        request.open(
            "GET",
            TYPO3.settings.ajaxUrls['includekrexx::DeleteLogFile'] + '&id=' + id,
            true
        );

        request.onload = function (event) {
            var result = JSON.parse(request.responseText);
            // @todo add popup to tell the user that the file ws deleted
            // Refresh the table
            ajaxRefresh.call();
        };

        request.send();

    };

})(TYPO3);