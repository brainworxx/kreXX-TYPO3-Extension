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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use DateTime;
use Throwable;

/**
 * Processing of integers.
 */
class ProcessInteger extends AbstractRouting implements ProcessInterface, ProcessConstInterface
{
    /**
     * The model we are currently working on.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Is this one an integer?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this an integer?
     */
    public function canHandle(Model $model): bool
    {
        $this->model = $model;
        return is_int($model->getData());
    }

    /**
     * Render a dump for an integer value.
     *
     * @return string
     *   The rendered markup.
     */
    public function handle(): string
    {
        // Detect a timestamp. Everything bigger than 946681200
        // is assumed to be a timestamp.
        try {
            $int = $this->model->getData();
            if ($int > 946681200) {
                $this->model->addToJson(
                    $this->pool->messages->getHelp('metaTimestamp'),
                    (new DateTime('@' . $int))->format('d.M Y H:i:s')
                );
            }
        } catch (Throwable $e) {
            // Do nothing.
            // Not sure how this can happen.
        }

        return $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $this->model->setNormal($this->model->getData())->setType(static::TYPE_INTEGER)
            )
        );
    }
}
