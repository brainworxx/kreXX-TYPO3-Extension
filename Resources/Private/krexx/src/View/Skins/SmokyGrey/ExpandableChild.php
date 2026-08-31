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

namespace Brainworxx\Krexx\View\Skins\SmokyGrey;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Renderer en expandable child. That is tha stuff you can click and then opens.
 */
trait ExpandableChild
{
    /**
     * @var string[]
     */
    private array $markerExpandableChild = [
        '{name}',
        '{type}',
        '{ktype}',
        '{normal}',
        '{connectorRight}',
        '{gensource}',
        '{nest}',
        '{sourcebutton}',
        '{codewrapperLeft}',
        '{codewrapperRight}',
        '{addjson}',
    ];

    /**
     * @var string
     */
    private string $markerSourceButton = '{language}';

    /**
     * {@inheritDoc}
     */
    public function renderExpandableChild(Model $model, bool $isExpanded = false): string
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

        // Generating our code.
        $codegenHandler =  $this->pool->codegenHandler;
        $generateSource = $codegenHandler->generateSource(model: $model);
        return str_replace(
            search: $this->markerExpandableChild,
            replace: [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses(model: $model),
                $model->getNormal(),
                $this->renderConnectorRight(
                    connector: $model->getConnectorRight(cap: 128),
                    returnType: $model->getReturnType()
                ),
                $this->generateDataAttribute(name: static::DATA_ATTRIBUTE_SOURCE, data: $generateSource),
                $this->pool->chunks->chunkMe(string: $this->renderNest(model: $model)),
                $this->renderSourceButtonSg(genCode: $generateSource, model: $model),
                $this->generateDataAttribute(
                    name: static::DATA_ATTRIBUTE_WRAPPER_L,
                    data: $codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    name: static::DATA_ATTRIBUTE_WRAPPER_R,
                    data: $codegenHandler->generateWrapperRight()
                ),
                $this->generateDataAttribute(
                    name: static::DATA_ATTRIBUTE_JSON,
                    data: $this->encodeJson($model->getJson())
                ),
            ],
            subject: $this->fileCache[static::FILE_EX_CHILD_NORMAL]
        );
    }

    /**
     * Render the source button.
     *
     * @param string $genCode
     *   The generated source.
     * @param Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderSourceButtonSg(string $genCode, Model $model): string
    {
        if (
            $genCode === static::CODEGEN_STOP_BIT ||
            ($genCode === '' || $genCode === '0') ||
            !$this->pool->codegenHandler->isCodegenAllowed()
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return str_replace(
                search: $this->markerSourceButton,
                replace: $model->getConnectorLanguage(),
                subject: $this->fileCache[static::FILE_SOURCE_BUTTON]
            );
        }
    }

    /**
     * Getter of the expandable child for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerExpandableChild(): array
    {
        return $this->markerExpandableChild;
    }

    /**
     * Getter of the source button for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSourceButton(): array
    {
        return [$this->markerSourceButton];
    }
}
