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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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
use Brainworxx\Krexx\Service\Config\Fallback;

trait SingleEditableChild
{
    /**
     * @var array
     */
    private $markerSingleEditableChild = [
        '{name}',
        '{source}',
        '{normal}',
        '{type}',
        '{help}',
    ];

    /**
     * @var string
     */
    private $markerDropdownOptions = '{options}';

    /**
     * @var array
     */
    private $markerSelectOption = [
        '{text}',
        '{value}',
        '{selected}'
    ];

    private $markerSingleInput = [
        '{id}',
        '{value}',
    ];

    /**
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model): string
    {
        // For dropdown elements, we need to render the options.
        $options = '';
        if ($model->getType() === Fallback::RENDER_TYPE_SELECT) {
            $options = $this->renderSelectOptions($model);
        }

        return str_replace(
            $this->markerSingleEditableChild,
            [
                $model->getData(),
                $model->getNormal(),
                str_replace($this->markerDropdownOptions, $options, $this->renderSpecificEditableElement($model)),
                Fallback::RENDER_EDITABLE,
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_SI_EDIT_CHILD)
        );
    }

    /**
     * Render the options of a select.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderSelectOptions(Model $model): string
    {
        // Here we store what the list of possible values.
        if ($model->getDomid() === Fallback::SETTING_SKIN) {
            // Get a list of all skin folders.
            $valueList = $this->pool->config->getSkinList();
        } else {
            $valueList = ['true', 'false'];
        }

        // Paint it.
        $options = '';
        foreach ($valueList as $value) {
            $value === $model->getName() ? $selected = 'selected="selected"' : $selected = '';
            $options .= str_replace(
                $this->markerSelectOption,
                [$value, $value, $selected],
                $this->getTemplateFileContent(static::FILE_SI_SELECT_OPTIONS)
            );
        }

        return $options;
    }

    /**
     * Dynamically render the element.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderSpecificEditableElement(Model $model): string
    {
        return str_replace(
            $this->markerSingleInput,
            [
                $model->getDomid(),
                $model->getName()
            ],
            $this->getTemplateFileContent('single' . $model->getType())
        );
    }

    /**
     * Getter of the single editable child for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return array
     *   The marker array.
     */
    public function getMarkerSingleEditableChild(): array
    {
        return $this->markerSingleEditableChild;
    }

    /**
     * Getter of the dropdown option for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return array
     *   The marker array.
     */
    public function getMarkerDropdownOptions(): array
    {
        return [$this->markerDropdownOptions];
    }

    /**
     * Getter of the select options for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return array
     *   The marker array.
     */
    public function getMarkerSelectOption(): array
    {
        return $this->markerSelectOption;
    }

    /**
     * Getter of the specific element for unit tests.
     *
     * @codeCoverageIgnore
     *   We are not testing the unit tests.
     *
     * @return array
     *   The marker array.
     */
    public function getMarkerSingleInput(): array
    {
        return $this->markerSingleInput;
    }
}
