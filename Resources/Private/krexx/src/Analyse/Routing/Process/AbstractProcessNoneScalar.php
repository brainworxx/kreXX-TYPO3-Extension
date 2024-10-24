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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;

/**
 * Abstract class for the analysis of more complicated stuff like classes
 * or arrays.
 */
abstract class AbstractProcessNoneScalar extends AbstractRouting implements ProcessInterface, ProcessConstInterface
{
    /**
     * Generate the output of the none scalar analysis.
     *
     * @return string
     *   The generated DOM
     */
    public function handle(): string
    {
        // Check the nesting level.
        if ($this->pool->emergencyHandler->checkNesting()) {
            return $this->handleNestedTooDeep($this->model);
        }

        // Render recursion.
        if ($this->pool->recursionHandler->isInHive($this->model->getData())) {
            return $this->handleRecursion($this->model);
        }

        // Render the none scalar stuff.
        return $this->handleNoneScalar();
    }

    /**
     * doing the none scalar stuff.
     *
     * @return string
     *   The generated DOM.
     */
    abstract protected function handleNoneScalar(): string;

    /**
     * This none simple type was analysed before.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function handleRecursion(Model $model): string
    {
        $data = $model->getData();
        if (is_object($data)) {
            $normal = '\\' . get_class($data);
            $domId = $this->generateDomIdFromObject($data);
        } else {
            // Must be the globals array.
            $normal = '$GLOBALS';
            $domId = '';
        }

        return $this->pool->render->renderRecursion(
            // Prepare the model for the recursion rendering.
            // We also need to unset the data for the source generation.
            $model->setDomid($domId)
                ->setNormal($normal)
                ->setData(null)
        );
    }

    /**
     * This none simple type was nested too deep.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function handleNestedTooDeep(Model $model): string
    {
        $text = $this->pool->messages->getHelp('maximumLevelReached2');

        $model->setData($text)
            ->setNormal($this->pool->messages->getHelp('maximumLevelReached1'))
            ->setType(is_array($model->getData()) ? static::TYPE_ARRAY : static::TYPE_OBJECT)
            ->setHasExtra(true);

        // Render it directly.
        return $this->pool->render->renderExpandableChild($model);
    }
}
