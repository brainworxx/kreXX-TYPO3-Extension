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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Config\From;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Config\Security;

/**
 * Reads the config from the ini file, as well as the fe editing config.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Ini extends Fallback
{
    /**
     * Our security handler.
     *
     * @var Security
     */
    public $security;

    /**
     * The content of the ini file we have loaded.
     *
     * @var array
     */
    protected $iniSettings = array();

    /**
     * Inject the pool, create the security handler, load the ini file.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->security = $pool->config->security;
    }

    /**
     * Setter for the ini path.
     *
     * @param string $path
     *   The path to the ini file.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function loadIniFile($path)
    {

        $this->iniSettings = (array)parse_ini_string(
            $this->pool->fileService->getFileContents($path, false),
            true
        );

        return $this;
    }

    /**
     * Get the configuration of the frontend config form.
     *
     * @param string $name
     *   The parameter name you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public function getFeConfig($name)
    {
        // Load it from the file.
        $filevalue = $this->getFeConfigFromFile($name);

        // Do we have a value?
        if (empty($filevalue) === true) {
            // Fallback to factory settings.
            if (isset($this->feConfigFallback[$name]) === true) {
                return array(
                    ($this->feConfigFallback[$name][static::RENDER][static::RENDER_EDITABLE] === static::VALUE_TRUE),
                    $this->feConfigFallback[$name][static::RENDER][static::RENDER_TYPE]
                );
            }
            // Unknown parameter and nothing in the fallback!
            // This should never happen, btw.
            return array(false, static::RENDER_TYPE_NONE);
        }

        return array(
            ($filevalue[static::RENDER_EDITABLE] === static::VALUE_TRUE),
            $filevalue[static::RENDER_TYPE]
        );
    }

    /**
     * Get the config of the frontend config form from the file.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array|null
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public function getFeConfigFromFile($parameterName)
    {
        // Get the human readable stuff from the ini file.
        $value = $this->getConfigFromFile('feEditing', $parameterName);

        if (empty($value) === true) {
            // Sorry, no value stored.
            return null;
        }

        // Get the rendering type.
        $type = $this->feConfigFallback[$parameterName][static::RENDER][static::RENDER_TYPE];

        // Stitch together the setting.
        switch ($value) {
            case static::RENDER_TYPE_NONE:
                $type = static::RENDER_TYPE_NONE;
                $editable = static::VALUE_FALSE;
                break;

            case static::RENDER_TYPE_INI_DISPLAY:
                $editable = static::VALUE_FALSE;
                break;

            case static::RENDER_TYPE_INI_FULL:
                $editable = static::VALUE_TRUE;
                break;

            default:
                // Unknown setting.
                // Fallback to no display, just in case.
                $type = static::RENDER_TYPE_NONE;
                $editable = static::VALUE_FALSE;
                break;
        }

        return array(
            static::RENDER_TYPE => $type,
            static::RENDER_EDITABLE => $editable,
        );
    }

    /**
     * Returns settings from the ini file.
     *
     * @param string $group
     *   The group name inside of the ini.
     * @param string $name
     *   The name of the setting.
     *
     * @return string
     *   The value from the file.
     */
    public function getConfigFromFile($group, $name)
    {
        // Do we have a value in the ini?
        // Does it validate?
        if (isset($this->iniSettings[$group][$name]) === true &&
            $this->security->evaluateSetting($group, $name, $this->iniSettings[$group][$name]) === true
        ) {
            return $this->iniSettings[$group][$name];
        }

        return null;
    }
}
