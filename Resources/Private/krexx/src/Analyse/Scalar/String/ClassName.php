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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Throwable;
use ReflectionException;

class ClassName extends AbstractScalarAnalysis
{
    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Is always active
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * Is this string a class name, and will we do a meta analysis of it?
     *
     * To avoid needless junk, we only look at classes with a namespace.
     *
     * @param string $string
     *   The possible class name we are looking at
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     *
     * @return bool
     *   Is this a class name?
     */
    public function canHandle($string, Model $model): bool
    {
        set_error_handler($this->pool->retrieveErrorCallback());
        try {
            if (strpos($string, '\\') !== false && class_exists($string)) {
                $this->handledValue = $string;
                $this->model = $model;
                restore_error_handler();
                return true;
            }
        } catch (Throwable $throwable) {
        }

        restore_error_handler();
        return false;
    }

    /**
     * Add the decoded json and a pretty-print-json to the output.
     *
     * @return string[]
     *   The array for the meta callback.
     */
    protected function handle(): array
    {
        $messages = $this->pool->messages;
        $meta = [];
        try {
            $meta = [$messages->getHelp('metaReflection') => new ReflectionClass($this->handledValue)];
        } catch (ReflectionException $e) {
        }

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[$messages->getHelp('metaContent')] = $this->model->getData();
        }

        return $meta;
    }
}
