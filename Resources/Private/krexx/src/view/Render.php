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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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
    /**
     * {@inheritdoc}
     */
    public function renderRecursion(Model $model)
    {
        return str_replace(
            array(
                '{name}',
                '{domId}',
                '{normal}',
                '{connector1}',
                '{help}',
                '{connector2}',
                '{gensource}',
            ),
            array(
                $model->getName(),
                $model->getDomid(),
                $model->getNormal(),
                $this->renderConnector($model->getConnector1()),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnector2()),
                $this->pool->codegenHandler->generateSource($model),
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
                '{version}',
                '{doctype}',
                '{KrexxCount}',
                '{headline}',
                '{cssJs}',
                '{KrexxId}',
                '{search}',
                '{messages}',
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
            ),
            $this->getTemplateFileContent('header')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        if (!isset($caller['file'])) {
            // When we have no caller, we will not render it.
            $caller = '';
        } else {
            $caller = $this->renderCaller($caller['file'], $caller['line']);
        }

        return str_replace(
            array(
                '{configInfo}',
                '{caller}',
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
    public function renderCssJs(&$css, &$js)
    {
        return str_replace(
            array('{css}', '{js}'),
            array($css, $js),
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

        if ($model->getHasExtras()) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = 'kexpand';
            // Add the yellow box for large output text.
            $partExtra = str_replace(
                '{data}',
                $model->getData(),
                $this->getTemplateFileContent('singleChildExtra')
            );
        }
        if ($model->getIsCallback()) {
            // Add callable partial.
            $partCallable = str_replace(
                '{normal}',
                $model->getNormal(),
                $this->getTemplateFileContent('singleChildCallable')
            );
        }
        // Stitching the classes together, depending on the types.
        $typeArray = explode(' ', $model->getType());
        $typeClasses = '';
        foreach ($typeArray as $typeClass) {
            $typeClass = 'k' . $typeClass;
            $typeClasses .= $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->pool->codegenHandler->generateSource($model);

        if (empty($gensource)) {
            // Remove the markers, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // We add the buttton and the code.
            $sourcebutton = $this->getTemplateFileContent('sourcebutton');
        }

        // Stitching it together.
        return str_replace(
            array(
                '{gensource}',
                '{sourcebutton}',
                '{expand}',
                '{callable}',
                '{extra}',
                '{name}',
                '{type}',
                '{type-classes}',
                '{normal}',
                '{help}',
                '{connector1}',
                '{connector2}',
                '{codewrapper1}',
                '{codewrapper2}',
                ),
            array(
                $gensource,
                $sourcebutton,
                $partExpand,
                $partCallable,
                $partExtra,
                $model->getName(),
                $model->getType(),
                $typeClasses,
                $model->getNormal(),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnector1()),
                $model->getConnector2(),
                $this->pool->codegenHandler->generateWrapper1(),
                $this->pool->codegenHandler->generateWrapper2(),
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
        if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
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
        if ($gencode === ';stop;' || empty($gencode)) {
            // Remove the button marker, because here is nothing to add.
            $sourceButton = '';
        } else {
            // Add the button.
            $sourceButton = $this->getTemplateFileContent('sourcebutton');
        }

        // Is it expanded?
        if ($isExpanded) {
            $expandedClass = 'kopened';
        } else {
            $expandedClass = '';
        }

        return str_replace(
            array(
                '{name}',
                '{type}',
                '{ktype}',
                '{normal}',
                '{help}',
                '{connector1}',
                '{connector2}',
                '{gensource}',
                '{sourcebutton}',
                '{isExpanded}',
                '{nest}',
                '{codewrapper1}',
                '{codewrapper2}',
            ),
            array(
                $model->getName(),
                $model->getType(),
                $cssType,
                $model->getNormal(),
                $this->renderHelp($model),
                $this->renderConnector($model->getConnector1()),
                $this->renderConnector($model->getConnector2()),
                $gencode,
                $sourceButton,
                $expandedClass,
                $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
                $this->pool->codegenHandler->generateWrapper1(),
                $this->pool->codegenHandler->generateWrapper2(),
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
            array('{name}', '{value}'),
            array($model->getData(), $model->getName()),
            $this->getTemplateFileContent('single' . $model->getType())
        );
        $options = '';

        // For dropdown elements, we need to render the options.
        if ($model->getType() === 'Select') {
            // Here we store what the list of possible values.
            switch ($model->getData()) {
                case "destination":
                    // At php shutdown, logfile or direct after analysis.
                    $valueList = array('browser', 'file');
                    break;

                case "skin":
                    // Get a list of all skin folders.
                    $valueList = $this->getSkinList();
                    break;

                default:
                    // true/false
                    $valueList = array('true', 'false');
                    break;
            }

            // Paint it.
            $optionTemplateName = 'single' . $model->getType() . 'Options';
            foreach ($valueList as $value) {
                if ($value === $model->getName()) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $options .= str_replace(
                    array('{text}', '{value}', '{selected}'),
                    array($value, $value, $selected),
                    $this->getTemplateFileContent($optionTemplateName)
                );
            }
        }

        return str_replace(
            array(
                '{name}',
                '{source}',
                '{normal}',
                '{type}',
                '{help}',
            ),
            array(
                $model->getData(),
                $model->getNormal(),
                str_replace('{options}', $options, $element),
                'editable',
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
                '{help}',
                '{text}',
                '{class}',
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
        $from = $errline -6;
        $to = $errline +5;
        $source = $this->fileService->readSourcecode($errfile, $errline -1, $from, $to -1);

        return str_replace(
            array(
                '{type}',
                '{errstr}',
                '{file}',
                '{source}',
                '{KrexxCount}',
                '{line}',
            ),
            array(
                $type,
                $errstr,
                $errfile,
                $source,
                $this->pool->emergencyHandler->getKrexxCount(),
                $errline,
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
                '{cssJs}',
                '{version}',
                '{doctype}',
                '{search}',
                '{KrexxId}',
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
        foreach ($messages as $message) {
            $result .= str_replace(
                array('{class}', '{message}'),
                array($message['class'], $message['message']),
                $this->getTemplateFileContent('message')
            );
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
                '{className}',
                '{lineNo}',
                '{sourceCode}',
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
