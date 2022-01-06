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
    private $markerExpandableChild = [
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
        '{key-ktype}',
    ];

    /**
     * @var string
     */
    private $markerSourceButton = '{language}';

    /**
     * {@inheritDoc}
     */
    public function renderExpandableChild(Model $model, bool $isExpanded = false): string
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Generating our code.
        $codegenHandler =  $this->pool->codegenHandler;
        $generateSource = $codegenHandler->generateSource($model);
        return str_replace(
            $this->markerExpandableChild,
            [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorRight($model->getConnectorRight(128), $model->getReturnType()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $generateSource),
                $this->pool->chunks->chunkMe($this->renderNest($model, false)),
                $this->renderSourceButtonSg($generateSource, $model),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_L, $codegenHandler->generateWrapperLeft()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_R, $codegenHandler->generateWrapperRight()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson())),
                'key' . $model->getKeyType(),
            ],
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * Render the source button.
     *
     * @param string $gencode
     *   The generated source.
     * @param Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderSourceButtonSg(string $gencode, Model $model): string
    {
        if (
            $gencode === static::CODEGEN_STOP_BIT ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return str_replace(
                $this->markerSourceButton,
                $model->getConnectorLanguage(),
                $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON)
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
