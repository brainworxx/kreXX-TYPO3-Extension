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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Processing of arrays.
 */
class ProcessArray extends AbstractProcessNoneScalar implements
    ProcessConstInterface,
    CallbackConstInterface,
    ConfigConstInterface
{
    /**
     * Cached setting for the array count limit before switching to the
     * simpified version.
     *
     * @var int
     */
    protected int $arrayCountLimit = 0;

    /**
     * The model we are currently working on.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * {@inheritDoc}
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->arrayCountLimit = (int) $this->pool->config
            ->getSetting(static::SETTING_ARRAY_COUNT_LIMIT);
    }

    /**
     * Is this one an array?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this an array?
     */
    public function canHandle(Model $model): bool
    {
        $this->model = $model;
        return is_array($model->getData());
    }

    /**
     * Render a dump for an array.
     *
     * @return string
     *   The rendered markup.
     */
    protected function handleNoneScalar(): string
    {
        $this->pool->emergencyHandler->upOneNestingLevel();
        $count = count($this->model->getData());

        if ($count > $this->arrayCountLimit) {
            // Budget array analysis.
            $this->model->injectCallback($this->pool->createClass(ThroughLargeArray::class))
                ->setHelpid('simpleArray');
        } else {
            // Complete array analysis.
            $this->model->injectCallback($this->pool->createClass(ThroughArray::class));
        }

        // Dumping all Properties.
        $result = $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $this->model->setType(static::TYPE_ARRAY)
                    ->setNormal($count . $this->pool->messages->getHelp('countElements'))
                    ->addParameter(static::PARAM_DATA, $this->model->getData())
                    ->addParameter(static::PARAM_MULTILINE, false)
            )
        );

        $this->pool->emergencyHandler->downOneNestingLevel();
        return $result;
    }
}
