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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Model;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Analysis model trait with the json that contains additional analysis results.
 */
trait Json
{
    /**
     * Additional data, we are sending to the FE vas a json, hence the name.
     *
     * Right now, only the smokygrey skin makes use of this.
     *
     * @var string[]
     */
    protected array $json = [];

    /**
     * Setter for the $helpId.
     *
     * @param string $helpId
     *   The ID of the help text.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setHelpid(string $helpId): Model
    {
        $this->addToJson($this->pool->messages->getHelp('metaHelp'), $this->pool->messages->getHelp($helpId));
        return $this;
    }

    /**
     * We simply add more info to our info json.
     * Leftover linebreaks will be removed.
     * If the value is empty, we will remove a possible previous entry to this key.
     *
     * @param string $key
     *   The array key.
     * @param string $value
     *   The value we want to set.
     *
     * @return Model
     *   $this for chaining.
     */
    public function addToJson(string $key, string $value): Model
    {

        if (empty($value)) {
            unset($this->json[$key]);
        } else {
            // Remove leftover linebreaks.
            $value = trim(str_replace(["\r", "\n"], ['', ''], $value));
            $this->json[$key] = $value;
        }

        return $this;
    }

    /**
     * Getter for json.
     *
     * @return string[]
     *   More analysis data.
     */
    public function getJson(): array
    {
        return $this->json;
    }
}
