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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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
    private $markerExpandableChild = [
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
        '{key-ktype}',
    ];

    /**
     * @var string[]
     */
    private $markerNest = [
        '{style}',
        '{mainfunction}',
        '{domId}',
        '{extra}',
    ];

    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, bool $isExpanded = false): string
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Generating our code.
        /** @var \Brainworxx\Krexx\Analyse\Code\Codegen $codegenHandler */
        $codegenHandler = $this->pool->codegenHandler;
        $generateSource = $codegenHandler->generateSource($model);
        return str_replace(
            $this->markerExpandableChild,
            [
                $model->getName(),
                $model->getType(),
                $this->retrieveTypeClasses($model),
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight(128), $model->getReturnType()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $generateSource),
                $this->renderSourceButtonWithStop($generateSource),
                $this->retrieveOpenedClass($isExpanded),
                $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_L, $codegenHandler->generateWrapperLeft()),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_WRAPPER_R, $codegenHandler->generateWrapperRight()),
                $this->renderHelp($model),
                'key' . $model->getKeyType(),
            ],
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * Return 'kopened', if expanded.
     *
     * @param bool $isExpanded
     *   Well? Is it?
     *
     * @return string
     *   The css class name.
     */
    protected function retrieveOpenedClass(bool $isExpanded): string
    {
        if ($isExpanded === true) {
            return 'kopened';
        }

        return '';
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
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            return '';
        } else {
            // Add the button.
            return $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
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
        if ($isExpanded === true) {
            $style = '';
        } else {
            $style = static::STYLE_HIDDEN;
        }

        return str_replace(
            $this->markerNest,
            [
                $style,
                $model->renderMe(),
                $domid,
                $this->renderExtra($model),
            ],
            $this->getTemplateFileContent(static::FILE_NEST)
        );
    }

    /**
     * Render the 'extra' part of the singe child output.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function renderExtra(Model $model): string
    {
        if ($model->hasExtra() === true) {
            return str_replace(
                $this->markerSingleChildExtra,
                $model->getData(),
                $this->getTemplateFileContent(static::FILE_SI_CHILD_EX)
            );
        }

        return '';
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
