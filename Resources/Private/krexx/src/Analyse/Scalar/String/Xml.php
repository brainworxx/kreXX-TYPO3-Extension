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
use DOMDocument;

/**
 * Doing a deep XML analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Xml extends AbstractScalarAnalysis
{
    /**
     * The model, so far.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Was the decoding of the XML successful?
     *
     * @var string
     */
    protected string $error = '';

    /**
     * The "xml" parser.
     *
     * @var \DOMDocument
     */
    protected DOMDocument $DOMDocument;

    /**
     * Inject the pool, initialize the parser.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
        $this->DOMDocument = new DOMDocument("1.0");
        // The pretty print done by a dom parser.
        $this->DOMDocument->preserveWhiteSpace = false;
        $this->DOMDocument->formatOutput = true;
    }

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return class_exists(class: DOMDocument::class, autoload: false);
    }

    /**
     * Test, if this is a valid XML structure.
     *
     * @param string|int|bool $string
     *   The possible json.
     * @param Model $model
     *   The model, so far for additional information.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle(string|int|bool $string, Model $model): bool
    {
        // Get a first impression, we check the mime type of the model.
        $metaStuff = $model->getJson();
        $mimeType = $this->pool->messages->getHelp(key: 'metaMimeTypeString');
        if (empty($metaStuff[$mimeType]) || !str_contains(haystack: $metaStuff[$mimeType], needle: 'xml;')) {
            // Was not identified as xml before.
            // Early return.
            return false;
        }
        $this->error = '';

        // Load the document.
        set_error_handler(callback: $this->errorCallback(...));
        $this->DOMDocument->loadXML(source: $string);
        restore_error_handler();

        if ($this->error !== '' && $this->error !== '0') {
            $model->addToJson(key: $this->pool->messages->getHelp(key: 'xmlError'), value: $this->error);
            return false;
        }

        $this->model = $model;
        $this->handledValue = $string;

        return true;
    }

    /**
     * Generate the metadata from the XML-
     *
     * @return array
     */
    protected function handle(): array
    {
        $meta = [];
        $messages = $this->pool->messages;

        $meta[$messages->getHelp(key: 'metaPrettyPrint')] = $this->pool
            ->encodingService
            ->encodeString(data: $this->DOMDocument->saveXML());

        // Move the extra part into a nest, for better readability.
        $this->model->setHasExtra(value: false);
        $meta[$messages->getHelp(key: 'metaContent')] = $this->pool
            ->encodingService
            ->encodeString(data: $this->handledValue);

        return $meta;
    }

    /**
     * Error callback in case something is wrong when decoding the XML.
     *
     * @param int $errno
     * @param string $errstr
     *
     * @return bool
     */
    public function errorCallback(int $errno, string $errstr): bool
    {
        $this->error = $this->pool->encodingService->encodeString(data: $errstr);
        return true;
    }
}
