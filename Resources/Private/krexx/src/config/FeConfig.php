<?php
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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Config;

/**
 * Frontend editing configuration.
 *
 * @package Brainworxx\Krexx\Config
 */
class FeConfig extends Tools
{
    /**
     * Get the configuration of the frontend config form.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public static function getFeConfig($parameterName)
    {
        static $config = array();

        if (!isset($config[$parameterName])) {
            // Load it from the file.
            $filevalue = self::getFeConfigFromFile($parameterName);
            if (!is_null($filevalue)) {
                $config[$parameterName] = $filevalue;
            }
        }

        // Do we have a value?
        if (isset($config[$parameterName])) {
            $type = $config[$parameterName]['type'];
            $editable = $config[$parameterName]['editable'];
        } else {
            // Fallback to factory settings.
            if (isset(self::$feConfigFallback[$parameterName])) {
                $type = self::$feConfigFallback[$parameterName]['type'];
                $editable = self::$feConfigFallback[$parameterName]['editable'];
            } else {
                // Unknown parameter.
                $type = 'None';
                $editable = 'false';
            }
        }
        if ($editable === 'true') {
            $editable = true;
        } else {
            $editable = false;
        }

        return array($editable, $type);
    }
}
