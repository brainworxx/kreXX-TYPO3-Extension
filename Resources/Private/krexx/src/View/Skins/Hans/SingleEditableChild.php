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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;

trait SingleEditableChild
{

    /**
     * The array we use for the string replace.
     *
     * @var array
     */
    protected $renderSingleEditableChildArray = [
        ConstInterface::MARKER_NAME,
        ConstInterface::MARKER_SOURCE,
        ConstInterface::MARKER_NORMAL,
        ConstInterface::MARKER_TYPE,
        ConstInterface::MARKER_HELP,
    ];

    /**
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        // For dropdown elements, we need to render the options.
        $options = '';
        if ($model->getType() === Fallback::RENDER_TYPE_SELECT) {
            $options = $this->renderSelectOptions($model);
        }

        return str_replace(
            $this->renderSingleEditableChildArray,
            [
                $model->getData(),
                $model->getNormal(),
                str_replace(static::MARKER_OPTIONS, $options, $this->renderSpecificEditableElement($model)),
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
    protected function renderSelectOptions(Model $model)
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
            if ($value === $model->getName()) {
                // This one is selected.
                $selected = 'selected="selected"';
            } else {
                $selected = '';
            }

            $options .= str_replace(
                [static::MARKER_TEXT, static::MARKER_VALUE, static::MARKER_SELECTED],
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
    protected function renderSpecificEditableElement(Model $model)
    {
        return str_replace(
            [
                static::MARKER_ID,
                static::MARKER_VALUE,
            ],
            [
                $model->getDomid(),
                $model->getName()
            ],
            $this->getTemplateFileContent('single' . $model->getType())
        );
    }
}
