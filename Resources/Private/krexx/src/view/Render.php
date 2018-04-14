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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\Fallback;

/**
 * Render methods.
 *
 * It get extended by the render class of the used skin, so every skin can do
 * some special stuff.
 *
 * @package Brainworxx\Krexx\View
 */
class Render extends AbstractRender
{

    const MARKER_NAME = '{name}';
    const MARKER_NORMAL = '{normal}';
    const MARKER_CONNECTOR_LEFT = '{connectorLeft}';
    const MARKER_CONNECTOR_RIGHT = '{connectorRight}';
    const MARKER_GEN_SOURCE = '{gensource}';
    const MARKER_VERSION = '{version}';
    const MARKER_DOCTYPE = '{doctype}';
    const MARKER_KREXX_COUNT = '{KrexxCount}';
    const MARKER_HEADLINE = '{headline}';
    const MARKER_CSS_JS = '{cssJs}';
    const MARKER_SEARCH = '{search}';
    const MARKER_MESSAGES = '{messages}';
    const MARKER_ENCODING = '{encoding}';
    const MARKER_CONFIG_INFO = '{configInfo}';
    const MARKER_CALLER = '{caller}';
    const MARKER_CSS = '{css}';
    const MARKER_JS = '{js}';
    const MARKER_DATA = '{data}';
    const MARKER_SOURCE_BUTTON = '{sourcebutton}';
    const MARKER_EXPAND = '{expand}';
    const MARKER_CALLABLE = '{callable}';
    const MARKER_EXTRA = '{extra}';
    const MARKER_TYPE = '{type}';
    const MARKER_TYPE_CLASSES = '{type-classes}';
    const MARKER_CODE_WRAPPER_LEFT = '{codewrapperLeft}';
    const MARKER_CODE_WRAPPER_RIGHT = '{codewrapperRight}';
    const MARKER_K_TYPE = '{ktype}';
    const MARKER_IS_EXPANDED = '{isExpanded}';
    const MARKER_NEST = '{nest}';
    const MARKER_ID = '{id}';
    const MARKER_VALUE = '{value}';
    const MARKER_TEXT = '{text}';
    const MARKER_SELECTED = '{selected}';
    const MARKER_SOURCE = '{source}';
    const MARKER_OPTIONS = '{options}';
    const MARKER_CLASS = '{class}';
    const MARKER_ERROR_STRING = '{errstr}';
    const MARKER_FILE = '{file}';
    const MARKER_LINE = '{line}';
    const MARKER_CLASS_NAME = '{className}';
    const MARKER_LINE_NO = '{lineNo}';
    const MARKER_SOURCE_CODE = '{sourceCode}';

    /**
     * {@inheritdoc}
     */
    public function renderRecursion(Model $model)
    {
        return str_replace(
            array(
                static::MARKER_NAME,
                static::MARKER_DOM_ID,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_HELP,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
            ),
            array(
                $model->getName(),
                $model->getDomid(),
                $model->getNormal(),
                $this->renderConnector($model->getConnectorLeft()),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnectorRight()),
                $this->generateDataAttribute(
                    'source',
                    $this->pool->codegenHandler->generateSource($model)
                ),
            ),
            $this->getTemplateFileContent('recursion')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderHeader($doctype, $headline, $cssJs)
    {
        return str_replace(
            array(
                static::MARKER_VERSION,
                static::MARKER_DOCTYPE,
                static::MARKER_KREXX_COUNT,
                static::MARKER_HEADLINE,
                static::MARKER_CSS_JS,
                static::MARKER_KREXX_ID,
                static::MARKER_SEARCH,
                static::MARKER_MESSAGES,
                static::MARKER_ENCODING,
            ),
            array(
                $this->pool->config->version,
                $doctype,
                $this->pool->emergencyHandler->getKrexxCount(),
                $headline,
                $cssJs,
                $this->pool->recursionHandler->getMarker(),
                $this->renderSearch(),
                $this->pool->messages->outputMessages(),
                $this->pool->chunks->getOfficialEncoding(),
            ),
            $this->getTemplateFileContent('header')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        if (isset($caller['file']) === true) {
            $caller = $this->renderCaller($caller['file'], $caller['line']);
        } else {
             // When we have no caller, we will not render it.
            $caller = '';
        }

        return str_replace(
            array(
                static::MARKER_CONFIG_INFO,
                static::MARKER_CALLER,
            ),
            array(
                $configOutput,
                $caller,
            ),
            $this->getTemplateFileContent('footer')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderCssJs(&$css, &$javascript)
    {
        return str_replace(
            array(static::MARKER_CSS, static::MARKER_JS),
            array($css, $javascript),
            $this->getTemplateFileContent('cssJs')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingleChild(Model $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $partExpand = '';
        $partCallable = '';
        $partExtra = '';

        if ($model->getHasExtra() === true) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = 'kexpand';
            // Add the yellow box for large output text.
            $partExtra = str_replace(
                static::MARKER_DATA,
                $model->getData(),
                $this->getTemplateFileContent('singleChildExtra')
            );
        }

        if ($model->getIsCallback() === true) {
            // Add callable partial.
            $partCallable = str_replace(
                static::MARKER_NORMAL,
                $model->getNormal(),
                $this->getTemplateFileContent('singleChildCallable')
            );
        }

        // Stitching the classes together, depending on the types.
        $typeClasses = '';
        foreach (explode(' ', $model->getType()) as $typeClass) {
            $typeClasses .= 'k' . $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->pool->codegenHandler->generateSource($model);

        if (empty($gensource) === true) {
            // Remove the markers, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // We add the buttton and the code.
            $sourcebutton = $this->getTemplateFileContent('sourcebutton');
        }

        // Stitching it together.
        return str_replace(
            array(
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_EXPAND,
                static::MARKER_CALLABLE,
                static::MARKER_EXTRA,
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_TYPE_CLASSES,
                static::MARKER_NORMAL,
                static::MARKER_HELP,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
            ),
            array(
                $this->generateDataAttribute('source', $gensource),
                $sourcebutton,
                $partExpand,
                $partCallable,
                $partExtra,
                $model->getName(),
                $model->getType(),
                $typeClasses,
                $model->getNormal(),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnectorLeft()),
                $this->renderConnector($model->getConnectorRight()),
                $this->generateDataAttribute('codewrapperLeft', $this->pool->codegenHandler->generateWrapperLeft()),
                $this->generateDataAttribute('codewrapperRight', $this->pool->codegenHandler->generateWrapperRight()),
            ),
            $this->getTemplateFileContent('singleChild')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Explode the type to get the class names right.
        $cssType = '';
        foreach (explode(' ', $model->getType()) as $singleType) {
            $cssType .= ' k' . $singleType;
        }

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);
        if ($gencode === ';stop;' || empty($gencode) === true) {
            // Remove the button marker, because here is nothing to add.
            $sourceButton = '';
        } else {
            // Add the button.
            $sourceButton = $this->getTemplateFileContent('sourcebutton');
        }

        // Is it expanded?
        if ($isExpanded === true) {
            $expandedClass = 'kopened';
        } else {
            $expandedClass = '';
        }

        return str_replace(
            array(
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_K_TYPE,
                static::MARKER_NORMAL,
                static::MARKER_HELP,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_IS_EXPANDED,
                static::MARKER_NEST,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
            ),
            array(
                $model->getName(),
                $model->getType(),
                $cssType,
                $model->getNormal(),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnectorLeft()),
                $this->renderConnector($model->getConnectorRight(128)),
                $this->generateDataAttribute('source', $gencode),
                $sourceButton,
                $expandedClass,
                $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
                $this->generateDataAttribute('codewrapperLeft', $this->pool->codegenHandler->generateWrapperLeft()),
                $this->generateDataAttribute('codewrapperRight', $this->pool->codegenHandler->generateWrapperRight()),
            ),
            $this->getTemplateFileContent('expandableChildNormal')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        $element = str_replace(
            array(
                static::MARKER_ID,
                static::MARKER_VALUE,
            ),
            array(
                $model->getDomid(),
                $model->getName()       // Wrong!
            ),
            $this->getTemplateFileContent('single' . $model->getType())
        );
        $options = '';

        // For dropdown elements, we need to render the options.
        if ($model->getType() === Fallback::RENDER_TYPE_SELECT) {
            // Here we store what the list of possible values.
            if ($model->getDomid() === Fallback::SETTING_SKIN) {
                // Get a list of all skin folders.
                $valueList = $this->getSkinList();
            } else {
                $valueList = array('true', 'false');
            }

            // Paint it.
            $optionTemplateName = 'singleSelectOptions';
            foreach ($valueList as $value) {
                if ($value === $model->getName()) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }

                $options .= str_replace(
                    array(static::MARKER_TEXT, static::MARKER_VALUE, static::MARKER_SELECTED),
                    array($value, $value, $selected),
                    $this->getTemplateFileContent($optionTemplateName)
                );
            }
        }

        return str_replace(
            array(
                static::MARKER_NAME,
                static::MARKER_SOURCE,
                static::MARKER_NORMAL,
                static::MARKER_TYPE,
                static::MARKER_HELP,
            ),
            array(
                $model->getData(),
                $model->getNormal(),
                str_replace(static::MARKER_OPTIONS, $options, $element),
                Fallback::RENDER_EDITABLE,
                $this->renderHelp($model),
            ),
            $this->getTemplateFileContent('singleEditableChild')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderButton(Model $model)
    {
        return str_replace(
            array(
                static::MARKER_HELP,
                static::MARKER_TEXT,
                static::MARKER_CLASS,
            ),
            array(
                $this->renderHelp($model),
                $model->getNormal(),
                $model->getName()
            ),
            $this->getTemplateFileContent('singleButton')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalMain($type, $errstr, $errfile, $errline)
    {
        $readFrom = $errline -6;
        $readTo = $errline +5;
        $source = $this->pool->fileService->readSourcecode($errfile, $errline -1, $readFrom, $readTo -1);

        return str_replace(
            array(
                static::MARKER_TYPE,
                static::MARKER_ERROR_STRING,
                static::MARKER_FILE,
                static::MARKER_SOURCE,
                static::MARKER_KREXX_COUNT,
                static::MARKER_LINE,
            ),
            array(
                $type,
                $errstr,
                $errfile,
                $source,
                $this->pool->emergencyHandler->getKrexxCount(),
                $errline
            ),
            $this->getTemplateFileContent('fatalMain')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalHeader($cssJs, $doctype)
    {
        return str_replace(
            array(
                static::MARKER_CSS_JS,
                static::MARKER_VERSION,
                static::MARKER_DOCTYPE,
                static::MARKER_SEARCH,
                static::MARKER_KREXX_ID,
            ),
            array(
                $cssJs,
                $this->pool->config->version,
                $doctype,
                $this->renderSearch(),
                $this->pool->recursionHandler->getMarker()
            ),
            $this->getTemplateFileContent('fatalHeader')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderMessages(array $messages)
    {
        $result = '';
        $messageTemplate = $this->getTemplateFileContent('message');
        foreach ($messages as $message) {
            $result .= str_replace('{message}', $message, $messageTemplate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function renderBacktraceSourceLine($className, $lineNo, $sourceCode)
    {
        return str_replace(
            array(
                static::MARKER_CLASS_NAME,
                static::MARKER_LINE_NO,
                static::MARKER_SOURCE_CODE,
            ),
            array(
                $className,
                $lineNo,
                $sourceCode,
            ),
            $this->getTemplateFileContent('backtraceSourceLine')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingeChildHr()
    {
        return $this->getTemplateFileContent('singleChildHr');
    }
}
