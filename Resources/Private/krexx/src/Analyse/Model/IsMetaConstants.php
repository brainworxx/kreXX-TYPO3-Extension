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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
 * Trait IsMetaConstants
 *
 * @deprecated
 *   Since 4.0.0. Will be removed.
 *
 * @codeCoverageIgnore
 *   We will not test deprecated methods.
 */
trait IsMetaConstants
{
    /**
     * We need to know if we are rendering the expandable child for the
     * constants. The code generation does special stuff there.
     *
     * @var bool
     */
    protected $isMetaConstants = false;

    /**
     * Getter for the isMetaConstants.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return bool
     *   True means that we are currently rendering the expandable child for
     *   the constants.
     */
    public function isMetaConstants(): bool
    {
        return $this->isMetaConstants;
    }

    /**
     * Getter for the isMetaConstants.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return bool
     *   True means that we are currently rendering the expandable child for
     *   the constants.
     */
    public function getIsMetaConstants(): bool
    {
        return $this->isMetaConstants();
    }

    /**
     * Setter for the isMetaConstants.
     *
     * @deprecated
     *   Since 4.0.0. Will be removed.
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @param bool $bool
     *   The value we want to set.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setIsMetaConstants(bool $bool): Model
    {
        $this->isMetaConstants = $bool;
        if ($bool === true) {
            $this->codeGenType = static::CODEGEN_TYPE_META_CONSTANTS;
        }

        return $this;
    }
}
