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

namespace Brainworxx\Krexx\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\AbstractScalarAnalysis;

class Base64 extends AbstractScalarAnalysis implements CodegenConstInterface
{
    /**
     * Code generation for this one is the json encoder.
     *
     * @var string
     */
    protected $codeGenType = self::CODEGEN_TYPE_BASE64_DECODE;

    /**
     * The decoded string.
     *
     * @var string
     */
    protected $decodedString;

    /**
     * The model, so far.
     *
     * @var Model
     */
    protected $model;

    /**
     * Is's always active.
     *
     * @inheritDoc
     */
    public static function isActive(): bool
    {
        return true;
    }

    /**
     * The only way to find out if we have a valid base64 string is by decoding
     * it, encoding it again and check the results.
     *
     * We ignore anything smaller than 36 characters.
     *
     * @inheritDoc
     */
    public function canHandle($string, Model $model): bool
    {
        if (
            strlen($string) > 36
            && base64_encode($this->decodedString = base64_decode($string, true)) === $string
        ) {
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
        $messages = $this->pool->messages;
        $meta = [
            $messages->getHelp('metaDecodedBase64') => $this->decodedString,
        ];

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[$messages->getHelp('metaContent')] = $this->model->getData();
        }

        unset($this->decodedString, $this->model);

        return $meta;
    }
}
