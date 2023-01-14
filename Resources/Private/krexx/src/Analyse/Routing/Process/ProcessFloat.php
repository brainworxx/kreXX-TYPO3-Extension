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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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
use DateTime;
use Throwable;

/**
 * Processing of floats.
 */
class ProcessFloat extends AbstractRouting implements ProcessInterface, ProcessConstInterface
{
    /**
     * Is this one a float?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this a float?
     */
    public function canHandle(Model $model): bool
    {
        return is_float($model->getData());
    }

    /**
     * Render a dump for a float value.
     *
     * @param Model $model
     *   The data we are analysing.
     *
     * @return string
     *   The rendered markup.
     */
    public function handle(Model $model): string
    {
        // Detect a micro timestamp. Everything bigger than 946681200000
        // is assumed to be a micro timestamp.
        $float = $model->getData();
        if ($float > 946681200) {
            try {
                $model->addToJson(
                    $this->pool->messages->getHelp('metaTimestamp'),
                    (DateTime::createFromFormat('U.u', (string)$float))->format('d.M Y H:i:s.u')
                );
            } catch (Throwable $exception) {
                // Do nothing
            }
        }

        return $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $model->setNormal($model->getData())->setType(static::TYPE_FLOAT)
            )
        );
    }
}
