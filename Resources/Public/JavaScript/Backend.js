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

(function () {
    "use strict";

    if (typeof document.observe === "function") {
        // Looks like we can use the good old backend library
        document.observe("dom:loaded", function () {
            formSupport.formReady();
        });
    } else {
        // Looks like we are handling a 7.4+ Backend.
        // IE9 should be able to swallow this.
        document.addEventListener("DOMContentLoaded", function () {
            formSupport.formReady();
        });
    }

    /**
     * @namespace formSupport
     */
    function formSupport() {
    }

    formSupport.formReady = function () {
        var checkboxes = document.getElementsByName('tx_includekrexx_tools_includekrexxkrexxconfiguration[factory]');
        var element;

        for (var i = 0, n = checkboxes.length; i < n; i++) {
            formSupport.toggle(checkboxes[i]);
            checkboxes[i].addEventListener('click', function () {
                formSupport.toggle(this);
            }, true);
        }

        var saveButton = document.getElementById('editconfig_save');
        if (typeof saveButton === 'object' && saveButton != 'undefined' && saveButton != null) {
            saveButton.addEventListener('click', function () {
                var form = document.getElementById('editconfig');
                if (typeof form === 'object' && form != 'undefined' && form != null) {
                    form.submit();
                }
            }, true);
        }

        var refreshButton = document.getElementById('editconfig_refresh');
        if (typeof refreshButton === 'object' && refreshButton != 'undefined' && refreshButton != null) {
            refreshButton.addEventListener('click', function () {
                location.reload();
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
        var id = checkbox.id.split('.');
        if (id[1] != 'undefined') {
            var element = document.getElementById(id[1]);
        }

        if (typeof element === 'object' && element != 'undefined' && element != null) {
            if (checkbox.checked) {
                element.disabled = true;
            } else {
                element.disabled = false;
            }
        }
    };
})();