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

use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Deep analysis for json strings.
 */
class Json extends AbstractScalarAnalysis implements CodegenConstInterface
{
    /**
     * Code generation for this one is the json encoder.
     *
     * @var string
     */
    protected string $codeGenType = self::CODEGEN_TYPE_JSON_DECODE;

    /**
     * What the variable name says.
     *
     * @var \stdClass|array
     */
    protected $decodedJson;

    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return function_exists('json_decode');
    }

    /**
     * Test, if this is a json, and if we can decode it.
     *
     * @param string $string
     *   The string we want to take a look at.
     * @param Model $model
     *   The model, so far.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle($string, Model $model): bool
    {
        // Get a fist impression.
        $first = substr($string, 0, 1);
        if (!($first === '{' xor $first === '[')) {
            return false;
        }

        // @deprecated Will be removed once we drop 8.3 support.
        if (function_exists('json_validate') && json_validate($string)) {
            // Doing it the PHP 8.3 way.
            $this->model = $model;
            $this->handledValue = $string;
            return true;
        }

        // The only way to test a valid json, is to decode it.
        // @deprecated This will be removed in PHP 8.3.
        $this->decodedJson = json_decode($string);
        if (json_last_error() === JSON_ERROR_NONE || $this->decodedJson !== null) {
            $this->model = $model;
            $this->handledValue = $string;
            return true;
        }

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
        if (empty($this->decodedJson)) {
            // We will not decode it again, if we already have a result.
            // @deprecated The "if" will be removed in PHP 8.3.
            $this->decodedJson = json_decode($this->handledValue);
        }

        $messages = $this->pool->messages;
        $meta = [
            $messages->getHelp('metaDecodedJson') => $this->decodedJson,
            $messages->getHelp('metaPrettyPrint') => $this->pool->encodingService
                ->encodeString(json_encode($this->decodedJson, JSON_PRETTY_PRINT))
        ];

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[$messages->getHelp('metaContent')] = $this->model->getData();
        }

        return $meta;
    }
}
