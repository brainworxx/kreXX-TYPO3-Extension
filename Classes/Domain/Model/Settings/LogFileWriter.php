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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Domain\Model\Settings;

trait LogFileWriter
{
    /**
     * @var string
     */
    protected $activateT3FileWriter;

    /**
     * @var string
     */
    protected $loglevelT3FileWriter;


    /**
     * @param string $activateT3FileWriter
     */
    public function setActivateT3FileWriter(string $activateT3FileWriter)
    {
        $this->activateT3FileWriter = $activateT3FileWriter;
    }

    /**
     * @param string $loglevelT3FileWriter
     */
    public function setLoglevelT3FileWriter(string $loglevelT3FileWriter)
    {
        $this->loglevelT3FileWriter = $loglevelT3FileWriter;
    }

    /**
     * Do nothing. This value will not get added to the ini.
     *
     * @param $value
     *
     * @return int
     */
    public function setFormactivateT3FileWriter($value): int
    {
        // I am supposed to do something with the value in here.
        // Otherwise, this is considered a bug and will lead to a bad rating.
        return (int) $value;
    }

    /**
     * Do nothing. This value will not get added to the ini.
     *
     * @param $value
     *
     * @return string
     */
    public function setFormloglevelT3FileWriter($value): string
    {
        // I am supposed to do something with the value in here.
        // Otherwise, this is considered a bug and will lead to a bad rating.
        return (string) $value;
    }
}
