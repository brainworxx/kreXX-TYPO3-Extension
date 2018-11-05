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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Smokygrey;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Individual render class for the smokey-grey skin.
 *
 * @package Brainworxx\Krexx\View\Smokygrey
 */
class Render extends \Brainworxx\Krexx\View\Render
{

    const MARKER_LANGUAGE = '{language}';
    const MARKER_ADDITIONAL_JSON = '{addjson}';
    const MARKER_K_DEBUG_CLASSES = '{kdebug-classes}';
    const MARKER_K_CONFIG_CLASSES = '{kconfiguration-classes}';
    const MARKER_K_LINK_CLASSES = '{klinks-classes}';

    const DATA_ATTRIBUTE_JSON = 'addjson';

    /**
     * {@inheritDoc}
     */
    public function renderSingleChild(Model $model)
    {
        // Replace the source button and set the json.
        return str_replace(
            array(
                static::MARKER_LANGUAGE,
                static::MARKER_ADDITIONAL_JSON,
            ),
            array(
                $model->getConnectorLanguage(),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson()))
            ),
            parent::renderSingleChild($model)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

        // Explode the type to get the class names right.
        $types = explode(' ', $model->getType());
        $cssType = '';
        foreach ($types as $singleType) {
            $cssType .= ' k' . $singleType;
        }

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);

        if ($gencode === ';stop;' ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // Add the button.
            $sourcebutton = str_replace(
                static::MARKER_LANGUAGE,
                $model->getConnectorLanguage(),
                $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON)
            );
        }

        return str_replace(
            array(
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_K_TYPE,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_IS_EXPANDED,
                static::MARKER_ADDITIONAL_JSON,
                static::MARKER_NEST,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
            ),
            array(
                $model->getName(),
                $model->getType(),
                $cssType,
                $model->getNormal(),
                $this->renderConnector($model->getConnectorRight(128)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gencode),
                '',
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson())),
                $this->pool->chunks->chunkMe($this->renderNest($model, false)),
                $sourcebutton,
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_L,
                    $this->pool->codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_R,
                    $this->pool->codegenHandler->generateWrapperRight()
                ),
            ),
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderRecursion(Model $model)
    {
        $template = parent::renderRecursion($model);
        // We add our json to the output.
        return str_replace(
            static::MARKER_ADDITIONAL_JSON,
            $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson())),
            $template
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        // Prepare the json. Not much do display for form elements.
        return str_replace(
            static::MARKER_ADDITIONAL_JSON,
            $this->generateDataAttribute(static::DATA_ATTRIBUTE_JSON, $this->encodeJson($model->getJson())),
            parent::renderSingleEditableChild($model)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderButton(Model $model)
    {
        // Prepare the json. Not much do display for form elements.
        return str_replace(
            array(static::MARKER_ADDITIONAL_JSON, static::MARKER_CLASS),
            array($this->generateDataAttribute(
                static::DATA_ATTRIBUTE_JSON,
                $this->encodeJson($model->getJson())
            ),
                $model->getName()
            ),
            parent::renderButton($model)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderHeader($doctype, $headline, $cssJs)
    {
        // Doing special stuff for smokygrey:
        // We hide the debug-tab when we are displaying the config-only and switch
        // to the config as the current payload.
        if ($headline === 'Edit local settings') {
            $debugClass = 'khidden';
            $configClass = 'kactive';
            $linkClass = '';
        } else {
            $debugClass = 'kactive';
            $configClass = '';
            $linkClass = '';
        }

        return str_replace(
            array(
                static::MARKER_K_DEBUG_CLASSES,
                static::MARKER_K_CONFIG_CLASSES,
                static::MARKER_K_LINK_CLASSES,
                static::MARKER_PLUGINS,
            ),
            array(
                $debugClass,
                $configClass,
                $linkClass,
                $this->renderPluginList(),
            ),
            parent::renderHeader($doctype, $headline, $cssJs)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        // Doing special stuff for smokygrey:
        // We hide the debug-tab when we are displaying the config-only and switch
        // to the config as the current payload.
        if ($configOnly === true) {
            $template = str_replace(
                static::MARKER_K_CONFIG_CLASSES,
                '',
                parent::renderFooter($caller, $configOutput)
            );
        } else {
            $template = str_replace(
                static::MARKER_K_CONFIG_CLASSES,
                'khidden',
                parent::renderFooter($caller, $configOutput)
            );
        }

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function renderFatalMain($type, $errstr, $errfile, $errline)
    {
        // Add the search.
        return str_replace(
            array(static::MARKER_SEARCH, static::MARKER_KREXX_ID),
            array($this->renderSearch(), $this->pool->recursionHandler->getMarker()),
            parent::renderFatalMain($type, $errstr, $errfile, $errline)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function renderConnector($connector)
    {
        if (strlen($connector) > 17) {
            // Something big, we should display it.
            // Most likely the parameters of a method.
            return parent::renderConnector($connector);
        }

        return '';
    }

    /**
     * Do nothing. Help stuff is implemented via javascript json.
     *
     * @param Model $model
     * @return string
     */
    protected function renderHelp(Model $model)
    {
         return '';
    }
}
