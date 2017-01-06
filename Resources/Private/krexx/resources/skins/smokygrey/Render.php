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

use Brainworxx\Krexx\Analyse\Model;

/**
 * Individual render class for the smokey-grey skin.
 *
 * @package Brainworxx\Krexx\View\Smokygrey
 */
class Render extends \Brainworxx\Krexx\Service\View\Render
{

    /**
     * {@inheritDoc}
     */
    public function renderSingleChild(Model $model)
    {

        $template = parent::renderSingleChild($model);
        $json = $model->getJson();

        $json['Help'] = $this->pool->messages->getHelp($model->getHelpid());
        // Prepare the json.
        $json = $this->encodeJson($json);
        $template = str_replace('{addjson}', $json, $template);

        return $template;
    }


    /**
     * {@inheritDoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {

        // Check for emergency break.
        if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

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
        $template = str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);
        $template = str_replace('{gensource}', $gencode, $template);
        if ($gencode == ';stop;' || empty($gencode)) {
            // Remove the button marker, because here is nothing to add.
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // Add the button.
            $template = str_replace('{sourcebutton}', $this->getTemplateFileContent('sourcebutton'), $template);
        }

        // Is it expanded?
        // This is done in the js.
        $template = str_replace('{isExpanded}', '', $template);

        $json = $model->getJson();
        $json['Help'] = $this->pool->messages->getHelp($model->getHelpid());
        $json = $this->encodeJson($json);
        $template = str_replace('{addjson}', $json, $template);

        return str_replace('{nest}', $this->pool->chunks->chunkMe($this->renderNest($model, false)), $template);
    }

    /**
     * {@inheritDoc}
     */
    public function renderRecursion(Model $model)
    {
        $template = parent::renderRecursion($model);
        // We add our json to the output.
        $json = $model->getJson();
        $json['Help'] = $this->pool->messages->getHelp($model->getHelpid());
        $json = $this->encodeJson($json);
        return str_replace('{addjson}', $json, $template);
    }

    /**
     * {@inheritDoc}
     */
    public function renderSingleEditableChild(Model $model)
    {

        $template = parent::renderSingleEditableChild($model);

        // Prepare the json. Not much do display for form elements.
        $json = $this->encodeJson(array(
            'Help' => $this->pool->messages->getHelp($model->getHelpid()),
        ));
        $template = str_replace('{addjson}', $json, $template);

        return $template;
    }

    /**
     * {@inheritDoc}
     */
    public function renderButton(Model $model)
    {

        $template = parent::renderButton($model);

        // Prepare the json. Not much do display for form elements.
        $json = $this->encodeJson(array(
            'Help' => $this->pool->messages->getHelp($model->getHelpid()),
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
    public function renderFatalMain($type, $errstr, $errfile, $errline)
    {
        $template = parent::renderFatalMain($type, $errstr, $errfile, $errline);

        // Add the search.
        $template = str_replace('{search}', $this->renderSearch(), $template);
        return str_replace('{KrexxId}', $this->pool->recursionHandler->getMarker(), $template);
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
     * Do nothing. Help stuff is implemented vis javascript json.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return string
     */
    protected function renderHelp($model)
    {
         return '';
    }

    /**
     * Some special escaping for the json output
     *
     * @param array $array
     *   The string we want to special-escape
     * @return string
     *   The json from the array.
     */
    protected function encodeJson(array $array)
    {
        foreach ($array as &$string) {
            // Our js has some problems with single quotes and escaped quotes.
            // We remove them as well as linebreaks.
            $string = str_replace('"', "\\u0027", $string);
            $string = str_replace("'", "\\u0022", $string);
            $string = str_replace('&quot;', "\\u0027", $string);
            // Unicode greater-than aund smaller-then values.
            $string = str_replace('&lt;', "\\u276E", $string);
            $string = str_replace('&gt;', "\\u02C3", $string);
        }

        return json_encode($array);
    }
}
