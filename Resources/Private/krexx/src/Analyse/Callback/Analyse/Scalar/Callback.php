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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Comment\Functions;
use Brainworxx\Krexx\Analyse\Model;
use ReflectionFunction;
use ReflectionException;

/**
 * The stuff we are doing here is very similar to the method analysis. The
 * main difference here is, that we do not have a hosting class and no
 * inheritance. We can extract the needed data directly out of the
 * reflection and dump it via ThroughMeta.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Callback extends AbstractScalarAnalysis
{
    /**
     * The callback we are analysing.
     *
     * @var string
     */
    protected $callback = '';

    /**
     * Is always active, because there are no system dependencies.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * Is this actually a callback? Simple wrapper around is_callable().
     *
     * @param string $string
     *   The string to test.
     * @param Model $model
     *   What the variable name says.
     *
     * @return bool
     *   The result, if it's callable.
     */
    public function canHandle($string, Model $model): bool
    {
        if (is_callable($string)) {
            $this->callback = $string;
            return true;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function handle(): array
    {
        try {
            $reflectionFunction = new ReflectionFunction($this->callback);
        } catch (ReflectionException $e) {
            // Huh, we were unable to retrieve the reflection.
            // Nothing left to do here.
            return [];
        }

        // Stitching together the main analysis.
        /** @var Functions $comment */
        $comment = $this->pool->createClass(Functions::class);
        $meta = [
            static::META_COMMENT => $comment->getComment($reflectionFunction),
            static::META_DECLARED_IN => $this->retrieveDeclarationPlace($reflectionFunction)
        ];
        $this->insertParameters($reflectionFunction, $meta);

        return $meta;
    }

    /**
     * Retrieve the declaration place, if possible.
     *
     * @param \ReflectionFunction $reflectionFunction
     *   The reflection function.
     *
     * @return string
     *   The declaration place.
     */
    protected function retrieveDeclarationPlace(ReflectionFunction $reflectionFunction): string
    {
        if ($reflectionFunction->isInternal() === true) {
            return static::META_PREDECLARED;
        }

        return $this->pool->fileService->filterFilePath($reflectionFunction->getFileName()) . "\n" .
            static::META_IN_LINE . $reflectionFunction->getStartLine();
    }

    /**
     * We insert the parameters into the meta array.
     *
     * @param \ReflectionFunction $reflectionFunction
     *   The reflection of the function that we are analysing.
     * @param array $meta
     *   The meta array, so far.
     */
    protected function insertParameters(ReflectionFunction $reflectionFunction, array &$meta)
    {
        foreach ($reflectionFunction->getParameters() as $key => $reflectionParameter) {
            ++$key;
            $meta[static::META_PARAM_NO . $key] = $this->pool
                ->codegenHandler
                ->parameterToString($reflectionParameter);
        }
    }
}
