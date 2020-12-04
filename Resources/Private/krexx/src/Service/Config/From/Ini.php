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

/**
 * Class Ini
 *
 * @deprecated
 *   Since 4.0.1. Will be removed. use Brainworxx\Krexx\Service\Config\From\File
 *
 * @codeCoverageIgnore
 *   We will not test deprecated classes.
 *
 * @package Brainworxx\Krexx\Service\Config\From
 */
class Ini extends File
{
    /**
     * Get the configuration of the frontend config form.
     *
     * @param string $name
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We do not test deprecated methods.
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
     * Setter for the ini path.
     *
     * @param string $path
     *   The path to the ini file.
     *
     * @deprecated
     *   Since 4.0.1. Will be removed. Use loadFile instead.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function loadIniFile(string $path): Ini
    {
        return $this->loadFile($path);
    }
}