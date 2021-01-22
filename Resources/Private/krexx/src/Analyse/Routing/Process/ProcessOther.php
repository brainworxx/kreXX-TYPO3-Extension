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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;

/**
 * Processing of other types of values.
 * At least tell the dev that there is "something" out there.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessOther extends AbstractRouting implements ProcessInterface
{
    /**
     * Fall back to telling the dev to create a ticket, requesting an analysis
     * processor for this variable type.
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this a resource?
     */
    public function canHandle(Model $model): bool
    {
        return true;
    }

    /**
     * Render a 'dump' for a other type.
     *
     * @param Model $model
     *   The model with the data for the output.
     *
     * @return string
     *   The rendered markup.
     */
    public function handle(Model $model): string
    {
        // Unknown type, better encode it, just to be sure.
        $type = $this->pool->encodingService->encodeString(gettype($model->getData()));
        return $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $model->setType($type)
                    ->setNormal('Unhandled type: ' . $type)
                    ->setHelpid('unhandedOtherHelp')
            )
        );
    }
}
