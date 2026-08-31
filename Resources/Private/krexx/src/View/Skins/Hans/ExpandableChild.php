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

namespace Brainworxx\Krexx\View\Skins\Hans;

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
        '{connectorLeft}',
        '{connectorRight}',
        '{gensource}',
        '{sourcebutton}',
        '{isExpanded}',
        '{nest}',
        '{codewrapperLeft}',
        '{codewrapperRight}',
        '{help}',
    ];

    /**
     * @var string[]
     */
    private array $markerNest = [
        '{style}',
        '{mainfunction}',
        '{domId}',
        '{extra}',
    ];

    /**
     * @var string
     */
    private string $markerSingleChildExtra = '{data}';

    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, bool $isExpanded = false): string
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

        // Generating our code.
        $codegenHandler = $this->pool->codegenHandler;
        $generateSource = $codegenHandler->generateSource(model: $model);
        return str_replace(
            search: $this->markerExpandableChild,
            replace: [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses(model: $model),
                $model->getNormal(),
                $this->renderConnectorLeft(connector: $model->getConnectorLeft()),
                $this->renderConnectorRight(
                    connector: $model->getConnectorRight(cap: 128),
                    returnType: $model->getReturnType()
                ),
                $this->generateDataAttribute(name: static::DATA_ATTRIBUTE_SOURCE, data: $generateSource),
                $this->renderSourceButtonWithStop(gencode: $generateSource),
                $isExpanded ? 'kopened' : '',
                $this->pool->chunks->chunkMe(string: $this->renderNest(model: $model, isExpanded: $isExpanded)),
                $this->generateDataAttribute(
                    name: static::DATA_ATTRIBUTE_WRAPPER_L,
                    data: $codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    name: static::DATA_ATTRIBUTE_WRAPPER_R,
                    data: $codegenHandler->generateWrapperRight()
                ),
                $this->renderHelp(model: $model),
            ],
            subject: $this->fileCache[static::FILE_EX_CHILD_NORMAL]
        );
    }

    /**
     * Render the source button.
     *
     * @param string $gencode
     *   The generated source.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderSourceButtonWithStop(string $gencode): string
    {
        if (
            $gencode === static::CODEGEN_STOP_BIT ||
            ($gencode === '' || $gencode === '0') ||
            !$this->pool->codegenHandler->isCodegenAllowed()
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return $this->fileCache[static::FILE_SOURCE_BUTTON];
        }
    }

    /**
     * Renders a nest with an anonymous function in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   The only expanded nest is the settings menu, when we render only the
     *   settings menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderNest(Model $model, bool $isExpanded = false): string
    {
        // Get the dom id.
        $domid = $model->getDomid();
        if ($domid !== '') {
            $domid = 'id="' . $domid . '"';
        }

        // Are we expanding this one?
        $style = $isExpanded ? '' : static::STYLE_HIDDEN;

        return str_replace(
            search: $this->markerNest,
            replace: [
                $style,
                $model->renderMe(),
                $domid,
                $this->renderExtra(model: $model),
            ],
            subject: $this->fileCache[static::FILE_NEST]
        );
    }

    /**
     * Render the 'extra' part of the singe child output.
     *
     * @param Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function renderExtra(Model $model): string
    {
        if ($model->hasExtra()) {
            return str_replace(
                search: $this->markerSingleChildExtra,
                replace: $this->prepareExtra(extra: $model->getData()),
                subject: $this->fileCache[static::FILE_SI_CHILD_EX]
            );
        }

        return '';
    }

    /**
     * Prepare the extra for the single child output.
     *
     * The problem with the extra part is: a trailing linebreak is not rendered
     * correctly. So we have to add the last linebreak again, if there is one.
     * Otherwise, the output is correct.
     *
     * @param string $extra
     *   The extra string.
     * @return string
     *   The prepared extra string.
     */
    protected function prepareExtra(string $extra): string
    {
        $lastChar = substr(string: $extra, offset: -1);
        if ($lastChar === "\n" || $lastChar === "\r") {
            return $extra . $lastChar;
        }

        return $extra;
    }

    /**
     * Getter of the extra for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerSingleChildExtra(): array
    {
        return [$this->markerSingleChildExtra];
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
     * Getter of the nest for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return string[]
     *   The marker array.
     */
    public function getMarkerNest(): array
    {
        return $this->markerNest;
    }
}
