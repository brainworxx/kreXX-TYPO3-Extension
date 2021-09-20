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

namespace Brainworxx\Krexx\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\FilePath;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Json;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Xml;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Deeper analyses methods for strings.
 *
 * We also inject a callback for a deeper analysis for more complex stuff.
 *   - callback
 *   - absolute file path
 *   - json
 *   - xml
 */
class ScalarString extends AbstractScalar
{

    /**
     * The list of analysis classes, that we use.
     *
     * @var string[]
     */
    protected $classList = [];

    /**
     * Get the additional analysis classes from the plugins.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $classList = [
            Callback::class,
            FilePath::class,
            Json::class,
            Xml::class
        ];

        $classList = array_merge($classList, SettingsGetter::getAdditionalScalarString());

        foreach ($classList as $className) {
            if ($className::isActive() === true) {
                $this->classList[] = $className;
            }
        }

        parent::__construct($pool);
    }

    /**
     * Doing special analysis with strings.
     *
     * @param Model $model
     *   The model, so far.
     *
     * @param string $originalData
     *   The original string, unprocessed and unescaped.
     *
     * @return Model
     *   The adjusted model.
     */
    public function handle(Model $model, string $originalData): Model
    {
        foreach ($this->classList as $className) {
            /** @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\AbstractScalarAnalysis $scalarHandler */
            $scalarHandler = $this->pool->createClass($className);

            if ($scalarHandler->canHandle($originalData, $model) === true) {
                $model->injectCallback($scalarHandler)->setDomid($this->generateDomId($originalData, $className));
                return $model;
            }
        }

        return $model;
    }
}
