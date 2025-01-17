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
 * Analysis model trait with the code generation information.
 */
trait CodeGenType
{
    /**
     * The code generation type.
     *
     * @var string
     */
    protected string $codeGenType = '';

    /**
     * Getter for the code generation type.
     *
     * @return string
     *   The string, representing the type.
     */
    public function getCodeGenType(): string
    {
        if (
            $this->codeGenType === '' &&
            $this->getConnectorLeft() === '' &&
            $this->getConnectorRight() === ''
        ) {
            // No connectors, no nothing.
            // Normal meta stuff.
            // We will ignore this one.
            return static::CODEGEN_TYPE_EMPTY;
        }

        return $this->codeGenType;
    }

    /**
     * Setter for the code generation type
     *
     * @param string $codeGenType
     *   The string, representing the type.
     *
     * @return Model
     *   For chaining.
     */
    public function setCodeGenType(string $codeGenType): Model
    {
        if ($codeGenType !== '') {
            $this->codeGenType = $codeGenType;
        }

        return $this;
    }
}
