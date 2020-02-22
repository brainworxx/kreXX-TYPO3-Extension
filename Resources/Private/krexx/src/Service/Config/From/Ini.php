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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Config\From;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;

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
     * @var \Brainworxx\Krexx\Service\Config\Validation
     */
    protected $validation;

    /**
     * The content of the ini file we have loaded.
     *
     * @var array
     */
    protected $iniSettings = [];

    /**
     * Inject the pool, create the security handler, load the ini file.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->validation = $pool->config->validation;
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
    public function loadIniFile(string $path): Ini
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
     *
     * @return bool
     *   Well? is it editable?
     */
    public function getFeIsEditable(string $name): bool
    {
        // Load it from the file.
        $filevalue = $this->getFeConfigFromFile($name);

        // Do we have a value?
        if (empty($filevalue) === true) {
            // Use the fallback.
            return $this->feConfigFallback[$name][static::RENDER][static::RENDER_EDITABLE] === static::VALUE_TRUE;
        }

        return $filevalue[static::RENDER_EDITABLE] === static::VALUE_TRUE;
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
    public function getFeConfigFromFile(string $parameterName)
    {
        // Get the human readable stuff from the ini file.
        $value = $this->getConfigFromFile(static::SECTION_FE_EDITING, $parameterName);

        if (empty($value) === true) {
            // Sorry, no value stored.
            return null;
        }

        // Get the rendering type.
        $type = $this->feConfigFallback[$parameterName][static::RENDER][static::RENDER_TYPE];

        // Stitch together the setting.
        switch ($value) {
            case static::RENDER_TYPE_INI_DISPLAY:
                $editable = static::VALUE_FALSE;
                break;

            case static::RENDER_TYPE_INI_FULL:
                $editable = static::VALUE_TRUE;
                break;

            default:
                // Unknown setting, or render type none.
                // Fallback to no display, just in case.
                $type = static::RENDER_TYPE_NONE;
                $editable = static::VALUE_FALSE;
                break;
        }

        return [
            static::RENDER_TYPE => $type,
            static::RENDER_EDITABLE => $editable,
        ];
    }

    /**
     * Returns settings from the ini file, if it is validated.
     *
     * @param string $group
     *   The group name inside of the ini.
     * @param string $name
     *   The name of the setting.
     *
     * @return string|null
     *   The value from the file. Null, when not available or not validated.
     */
    public function getConfigFromFile(string $group, string $name)
    {
        // Do we have a value in the ini?
        // Does it validate?
        if (
            isset($this->iniSettings[$group][$name]) === true &&
            $this->validation->evaluateSetting($group, $name, $this->iniSettings[$group][$name]) === true
        ) {
            return $this->iniSettings[$group][$name];
        }

        return null;
    }
}
