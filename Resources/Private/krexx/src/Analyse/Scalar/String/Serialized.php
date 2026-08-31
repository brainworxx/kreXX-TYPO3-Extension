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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\FormatSerialize;

class Serialized extends AbstractScalarAnalysis
{
    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Works only when hte multibyte extension is installed.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * Test if this one looks like a serialized whatever.
     *
     * @param string|int|bool $string $string
     *   The string we want to take a look at.
     * @param Model $model
     *   The model, so far.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle(string|int|bool $string, Model $model): bool
    {
        // We only handle objects and arrays.
        // Everything else is not really pretty print worthy.
        $needle = substr(string: $string, offset: 0, length: 2);
        if (in_array(needle: $needle, haystack: ['o:', 'O:','a:', 'C:'], strict: true)) {
            $this->handledValue = $string;
            $this->model = $model;
            return true;
        }

        return false;
    }

    /**
     * We do some pretty print with the serialized string
     *
     * @return string[]
     */
    protected function handle(): array
    {
        $messages = $this->pool->messages;
        $meta = [];
        $result = $this->pool->createClass(classname: FormatSerialize::class)
            ->prettyPrint(string: $this->handledValue);

        if ($result !== null) {
            $meta[$messages->getHelp(key: 'metaPrettyPrint')] = $this->pool
                ->encodingService->encodeString(data: $result);
            $this->model->setHasExtra(value: false);
            $meta[$messages->getHelp(key: 'metaContent')] = $this->model->getData();
        }

        return $meta;
    }
}
