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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Prepare everything for a deeper string analysis.
 */
abstract class AbstractScalarAnalysis extends AbstractCallback implements CallbackConstInterface
{
    /**
     * The code generation type constant assigned to the model.
     *
     * @var string
     */
    protected string $codeGenType = '';

    /**
     * Classname that renders the data.
     *
     * @var string
     */
    protected string $iteratorRenderer = ThroughMeta::class;

    /**
     * The value that we are handling. Must be set by the canHandle method.
     *
     * @var string|int|float
     */
    protected $handledValue;

    /**
     * Is this scalar deep analysis class able to do something here?
     *
     * @param string|int|bool $string
     *   The scalar type for the deeper analysis.
     * @param Model $model
     *   The model so far.
     *
     * @return bool
     *   Got this one get handled?
     */
    abstract public function canHandle($string, Model $model): bool;

    /**
     * Are all perquisites met for this class to do anything?
     *
     * If false, this class will not even get asked if it can handle something.
     *
     * @return bool
     */
    abstract public static function isActive(): bool;

    /**
     * Retrieve the meta array and render it.
     *
     * @return string
     *   The rendered DOM.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();
        $meta = $this->handle();

        if (empty($meta)) {
            // Nothing to render.
            return '';
        }

        // Prepare the rendering.
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->addParameter(static::PARAM_DATA, $meta)
            ->addParameter(static::PARAM_CODE_GEN_TYPE, $this->codeGenType)
            ->addParameter(static::PARAM_VALUE, $this->handledValue)
            ->injectCallback($this->pool->createClass($this->iteratorRenderer));

        // We render the model directly. This class acts only as a proxy.
        return $output . $this->dispatchEventWithModel(__FUNCTION__ . static::EVENT_MARKER_END, $model)->renderMe();
    }

    /**
     * Stitch together the meta array for the rendering.
     *
     * @return string[]
     *   The meta array.
     */
    protected function handle(): array
    {
        return [];
    }
}
