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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

use Brainworxx\Krexx\Analysis\Codegen;
use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Framework\Chunks;
use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\View\Messages;

/**
 * Individual render class for the smokey-grey skin.
 *
 * @package Brainworxx\Krexx\View
 */
class Render extends \Brainworxx\Krexx\View\Render
{

    /**
     * {@inheritDoc}
     */
    public function renderSingleChild(Simple $model)
    {

        $template = parent::renderSingleChild($model);

        $json['Help'] = $this->getHelp($model->getHelpid());
        // Prepare the json.
        $json = json_encode($json);

        $template = str_replace('{addjson}', $json, $template);

        return $template;
    }


    /**
     * {@inheritDoc}
     */
    public function renderExpandableChild(Simple $model, $isExpanded = false)
    {

        // Check for emergency break.
        if (!OutputActions::checkEmergencyBreak()) {
            // Normally, this should not show up, because the Chunks class will not
            // output anything, except a JS alert.
            Messages::addMessage("Emergency break for large output during analysis process.");
            return '';
        }


        if ($model->getName() == '' && $model->getType() == '') {
            // Without a Name or Type I only display the Child with a Node.
            $template = $this->getTemplateFileContent('expandableChildSimple');
            // Replace our stuff in the partial.
            return str_replace('{mainfunction}', Chunks::chunkMe($model->renderMe()), $template);
        } else {
            // We need to render this one normally.
            $template = $this->getTemplateFileContent('expandableChildNormal');
            // Replace our stuff in the partial.
            $template = str_replace('{name}', $model->getName(), $template);
            $template = str_replace('{type}', $model->getType(), $template);

            // Explode the type to get the class names right.
            $types = explode(' ', $model->getType());
            $cssType = '';
            foreach ($types as $singleType) {
                $cssType .= ' k' . $singleType;
            }
            $template = str_replace('{ktype}', $cssType, $template);

            $template = str_replace('{additional}', $model->getAdditional(), $template);
            // There is not much need for a connector to an empty name.
            if (empty($model->getName()) && $model->getName() != 0) {
                $template = str_replace('{connector1}', '', $template);
                $template = str_replace('{connector2}', '', $template);
            } else {
                $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
                $template = str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);
            }


            // Generating our code and adding the Codegen button, if there is
            // something to generate.
            $gencode = Codegen::generateSource($model);
            if ($gencode == '') {
                // Remove the markers, because here is nothing to add.
                $template = str_replace('{gensource}', '', $template);
                $template = str_replace('{gencode}', '', $template);
            } else {
                // We add the buttton and the code.
                $template = str_replace('{gensource}', $gencode, $template);
                $template = str_replace('{gencode}', $this->getTemplateFileContent('gencode'), $template);
            }

            // Is it expanded?
            // This is done in the js.
            $template = str_replace('{isExpanded}', '', $template);

            $json['Help'] = $this->getHelp($model->getHelpid());
            $json = json_encode($json);
            $template = str_replace('{addjson}', $json, $template);

            return str_replace(
                '{nest}',
                Chunks::chunkMe($this->renderNest($model, false)),
                $template
            );
        }
    }


    /**
     * {@inheritDoc}
     */
    public function renderSingleEditableChild(Simple $model)
    {

        $template = parent::renderSingleEditableChild($model);

        // Prepare the json. Not much do display for form elements.
        $json = json_encode(array(
            'Help' => $this->getHelp($model->getHelpid()),
        ));
        $template = str_replace('{addjson}', $json, $template);

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function renderButton(Simple $model)
    {

        $template = parent::renderButton($model);

        // Prepare the json. Not much do display for form elements.
        $json = json_encode(array(
            'Help' => $this->getHelp($model->getHelpid()),
        ));
        $template = str_replace('{addjson}', $json, $template);

        return str_replace('{class}', $model->getName(), $template);
    }

    /**
     * {@inheritDoc}
     */
    public function renderHeader($doctype, $headline, $cssJs)
    {
        $template = parent::renderHeader($doctype, $headline, $cssJs);

        // Doing special stuff for smokygrey:
        // We hide the debug-tab when we are displaying the config-only and switch
        // to the config as the current payload.
        if ($headline == 'Edit local settings') {
            $template = str_replace('{kdebug-classes}', 'khidden', $template);
            $template = str_replace('{kconfiguration-classes}', 'kactive', $template);
            $template = str_replace('{klinks-classes}', '', $template);
        } else {
            $template = str_replace('{kdebug-classes}', 'kactive', $template);
            $template = str_replace('{kconfiguration-classes}', '', $template);
            $template = str_replace('{klinks-classes}', '', $template);
        }

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        $template = parent::renderFooter($caller, $configOutput);

        // Doing special stuff for smokygrey:
        // We hide the debug-tab when we are displaying the config-only and switch
        // to the config as the current payload.
        if ($configOnly) {
            $template = str_replace('{kconfiguration-classes}', '', $template);
        } else {
            $template = str_replace('{kconfiguration-classes}', 'khidden', $template);
        }

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function renderFatalMain($type, $errstr, $errfile, $errline, $source)
    {
        $template = parent::renderFatalMain($type, $errstr, $errfile, $errline, $source);

        // Add the search.
        $template = str_replace('{search}', $this->renderSearch(), $template);
        return str_replace('{KrexxId}', OutputActions::$recursionHandler->getMarker(), $template);
    }
}
