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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\View\ViewConstInterface;
use DOMDocument;
use finfo;

/**
 * Doing a deep XML analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar
 */
class Xml extends AbstractScalarAnalysis implements ViewConstInterface
{
    /**
     * @var string
     */
    const XML_CHILDREN = 'children';

    /**
     * @var array|bool
     */
    protected $decodedXml;

    /**
     * The model, so far.
     *
     * @var Model
     */
    protected $model;

    /**
     * The original, un-pretty-print XML string.
     *
     * @var string
     */
    protected $originalXml = '';

    /**
     * Is there currently a node open?
     *
     * @var bool
     */
    protected $tnodeOpen = false;

    /**
     * {@inheritDoc}
     */
    public static function isActive(): bool
    {
        return function_exists('xml_parser_create') &&
            class_exists(DOMDocument::class) &&
            class_exists(finfo::class) &&
            function_exists('xml_parser_create');
    }

    /**
     * Test, if this is a valid XML structure.
     *
     * @param bool|int|string $string
     *   The possible json.
     * @param Model $model
     *   The model, so far for additional information.
     *
     * @return bool
     *   Well? Can we handle it?
     */
    public function canHandle($string, Model $model): bool
    {
        // Get a first impression, we check the mime type of the model.
        $metaStuff = $model->getJson();

        if (
            empty($metaStuff[static::META_MIME_TYPE_STRING]) === true ||
            strpos($metaStuff[static::META_MIME_TYPE_STRING], 'xml;') === false
        ) {
            // Was not identified as xml before.
            // Early return.
            return false;
        }

        $this->model = $model;
        $this->originalXml = $string;

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

        set_error_handler($this->pool->retrieveErrorCallback());
        // We try to decode it.
        $this->parseXml($this->originalXml);
        restore_error_handler();

        if (empty($this->decodedXml) === false) {
            $meta[static::META_DECODED_XML] = $this->decodedXml;
            // The pretty print done by a dom parser.
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($this->originalXml);
            $meta[static::META_PRETTY_PRINT] = $this->pool->encodingService->encodeString($dom->saveXML());
        } else {
            $meta[static::META_DECODED_XML] = 'Unable to decode the XML structure!';
        }

        // Move the extra part into a nest, for better readability.
        if ($this->model->hasExtra()) {
            $this->model->setHasExtra(false);
            $meta[static::META_CONTENT] = $this->model->getData();
        }

        return $meta;
    }

    /**
     * Parse an XML string into an array structure.
     *
     * @param string $strInputXML
     *   The string we want to parse.
     */
    protected function parseXml(string $strInputXML)
    {
        $resParser = xml_parser_create();
        xml_set_object($resParser, $this);
        xml_set_element_handler($resParser, "tagOpen", "tagClosed");
        xml_set_character_data_handler($resParser, "tagData");
        xml_parse($resParser, $strInputXML);
        xml_parser_free($resParser);
    }

    /**
     * Handle the opening of a tag.
     *
     * @param resource $parser
     *   The parser resource.
     * @param string $name
     *   The name of the tag we are opening.
     * @param array $attributes
     *   The attributes of the tag we are opening.
     */
    protected function tagOpen($parser, string $name, array $attributes)
    {
        $this->tnodeOpen = false;
        if (empty($attributes) === true) {
            $this->decodedXml[] = ["name" => $name];
        } else {
            $this->decodedXml[] = ["name" => $name, "attributes" => $attributes];
        }
    }

    /**
     * Retrieve the tag data.
     *
     * @param resource $parser
     *   The parser resource.
     * @param string $tagData
     *   The tag data.
     */
    protected function tagData($parser, string $tagData)
    {
        $count = count($this->decodedXml) - 1;
        if ($this->tnodeOpen) {
            $this->decodedXml[$count][static::XML_CHILDREN][] = array_pop(
                $this->decodedXml[$count][static::XML_CHILDREN]
            ) . $tagData;
        } elseif (trim($tagData) !== '') {
            $this->decodedXml[$count][static::XML_CHILDREN][] = $tagData;
            $this->tnodeOpen = true;
        }
    }

    /**
     * Handle the closing of a tag.
     *
     * @param resource $parser
     *   The parser resource.
     * @param string $name
     *   The name of the tag we are handling.
     */
    protected function tagClosed($parser, string $name)
    {
        $count = count($this->decodedXml);
        $this->tnodeOpen = false;
        $this->decodedXml[$count - 2][static::XML_CHILDREN][] = $this->decodedXml[$count - 1];
        array_pop($this->decodedXml);
    }
}
