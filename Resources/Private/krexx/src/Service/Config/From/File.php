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

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Config\From;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Reads the config from the configuration file, as well as the fe editing config.
 */
class File extends Fallback
{
    /**
     * Our security handler.
     *
     * @var \Brainworxx\Krexx\Service\Config\Validation
     */
    protected $validation;

    /**
     * The content of the file we have loaded.
     *
     * @var string[][]
     */
    protected $settings = [];

    /**
     * Inject the pool, create the security handler, load the file.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->validation = $pool->config->validation;
    }

    /**
     * Setter for the file path.
     *
     * @param string $path
     *   The path to the  file.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function loadFile(string $path): File
    {
        $this->settings = [];

        foreach (['ini' => 'parse_ini_string', 'json' => 'json_decode'] as $extension => $decoder) {
            $completePath = $path . $extension;
            if ($this->pool->fileService->fileIsReadable($completePath)) {
                $this->settings = (array)$decoder(
                    $this->pool->fileService->getFileContents($completePath, false),
                    true
                );
                // Feedback about the file name.
                $this->pool->config->setPathToConfigFile($completePath);
                return $this;
            }
        }

        // Still here? Give feedback about the filename.
        $this->pool->config->setPathToConfigFile($completePath);

        return $this;
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
    public function getFeConfigFromFile(string $parameterName): ?array
    {
        // Get the human-readable stuff from the file.
        $value = $this->getConfigFromFile(static::SECTION_FE_EDITING, $parameterName);

        if (empty($value)) {
            // Sorry, no value stored.
            return null;
        }

        // Get the rendering type.
        $type = $this->feConfigFallback[$parameterName][static::RENDER][static::RENDER_TYPE];

        // Stitch together the setting.
        switch ($value) {
            case static::RENDER_TYPE_CONFIG_DISPLAY:
                $editable = static::VALUE_FALSE;
                break;

            case static::RENDER_TYPE_CONFIG_FULL:
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
     * Returns settings from the file, if it is validated.
     *
     * @param string $group
     *   The group name inside the file.
     * @param string $name
     *   The name of the setting.
     *
     * @return mixed
     *   The value from the file. Null, when not available or not validated.
     */
    public function getConfigFromFile(string $group, string $name)
    {
        // Do we have a value in the file?
        // Does it validate?
        if (
            isset($this->settings[$group][$name]) &&
            $this->validation->evaluateSetting($group, $name, $this->settings[$group][$name])
        ) {
            return $this->settings[$group][$name];
        }

        return null;
    }
}
