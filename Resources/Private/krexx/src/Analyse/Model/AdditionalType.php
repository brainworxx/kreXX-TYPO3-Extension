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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
 * Analysis model trait with the additional type.
 */
trait AdditionalType
{
    /**
     * Additional data that gets added to the type. Normally something like
     * 'protected static final'
     *
     * @var string
     */
    protected string $additional = '';

    /**
     * The type of the variable we are analysing, in a string.
     *
     * @var string
     */
    protected string $type = '';

    /**
     * Setter for additional.
     *
     * @param string $additional
     *   The long result of the analysis.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setAdditional(string $additional): Model
    {
        $this->additional = $additional;
        return $this;
    }

    /**
     * Getter for additional
     *
     * @return string
     *   The long result of the analysis.
     */
    public function getAdditional(): string
    {
        return $this->additional;
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *   The type of the variable we are analysing.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setType(string $type): Model
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Getter for the type.
     *
     * @return string
     *   The type of the variable we are analysing
     */
    public function getType(): string
    {
        return $this->additional . $this->type;
    }
}
