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

namespace Brainworxx\Krexx\Service\Reflection;

use DateTimeImmutable;
use DOMDocument;
use DOMAttr;
use DOMCdataSection;
use DOMCharacterData;
use DOMDocumentType;
use DOMElement;
use DOMEntity;
use DOMException;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use DOMNotation;
use DOMProcessingInstruction;
use DOMText;
use DOMXPath;
use DateTime;

/**
 * Due to a PHP bug, the properties from all ext-dom classes are hidden from
 * reflections, var_dump and other debug means (not X-Debug, though).
 *
 * Hence, we need to introduce a special handling.
 *
 * *sigh*
 */
class HiddenProperty extends UndeclaredProperty
{
    /**
     * Class and property list of possible hidden properties.
     *
     * @var string[][]
     */
    public const HIDDEN_LIST = [
        DOMDocument::class => [
            'actualEncoding',
            'config',
            'doctype',
            'documentElement',
            'documentURI',
            'encoding',
            'formatOutput',
            'implementation',
            'preserveWhiteSpace',
            'recover',
            'resolveExternals',
            'standalone',
            'strictErrorChecking',
            'substituteEntities',
            'validateOnParse',
            'version',
            'xmlEncoding',
            'xmlStandalone',
            'xmlVersion',
        ],
        DOMAttr::class => [
            'name',
            'ownerElement',
            'schemaTypeInfo',
            'specified',
            'value',
        ],
        DOMCdataSection::class => [
            'wholeText',
            'data',
            'length',
            'nodeName',
            'nodeValue',
            'nodeType',
            'parentNode',
            'childNodes',
            'firstChild',
            'lastChild',
            'previousSibling',
            'nextSibling',
            'attributes',
            'ownerDocument',
            'namespaceURI',
            'prefix',
            'localName',
            'baseURI',
            'textContent',
        ],
        DOMCharacterData::class => [
            'data',
            'length',
        ],
        DOMDocumentType::class => [
            'publicId',
            'systemId',
            'name',
            'entities',
            'notations',
            'internalSubset',
        ],
        DOMElement::class => [
            'schemaTypeInfo',
            'tagName',
        ],
        DOMEntity::class => [
            'publicId',
            'systemId',
            'notationName',
            'actualEncoding',
            'encoding',
            'version',
        ],
        DOMException::class => [
            'code',
        ],
        DOMNamedNodeMap::class => [
            'length',
        ],
        DOMNode::class => [
            'nodeName',
            'nodeValue',
            'nodeType',
            'parentNode',
            'childNodes',
            'firstChild',
            'lastChild',
            'previousSibling',
            'nextSibling',
            'attributes',
            'ownerDocument',
            'namespaceURI',
            'prefix',
            'localName',
            'baseURI',
            'textContent',
        ],
        DOMNodeList::class => [
            'length',
        ],
        DOMNotation::class => [
            'publicId',
            'systemId'
        ],
        DOMProcessingInstruction::class => [
            'target',
            'data',
        ],
        DOMText::class => [
            'wholeText',
        ],
        DOMXPath::class => [
            'document',
        ],
        DateTime::class => [
            'date',
            'timezone',
            'timezone_type'
        ],
        DateTimeImmutable::class => [
            'date',
            'timezone',
            'timezone_type'
        ],
    ];

    /**
     * This one is always declared.
     *
     * @var bool
     */
    public bool $isUndeclared = false;

    /**
     * {@inheritDoc}
     */
    public function __construct(\ReflectionClass $ref, $name)
    {
        parent::__construct($ref, $name);

        $name = $ref->getName();
        if ($name === DateTime::class || $name === DateTimeImmutable::class) {
            $this->isPublic = false;
            $this->isProtected = true;
        }
    }
}
