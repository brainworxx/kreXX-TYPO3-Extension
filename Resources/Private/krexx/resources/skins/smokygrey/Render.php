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

namespace Brainworxx\Krexx\View\Smokygrey;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Individual render class for the smokey-grey skin.
 *
 * @package Brainworxx\Krexx\View\Smokygrey
 */
class Render extends \Brainworxx\Krexx\View\Render
{

    /**
     * {@inheritDoc}
     */
    public function renderSingleChild(Model $model)
    {
        // Replace the source button and set the json.
        $json = $model->getJson();
        $help = $this->pool->messages->getHelp($model->getHelpid());
        if (!empty($help)) {
            $json['Help'] = $help;
        }

        return str_replace(
            array('{language}', '{addjson}'),
            array($model->getConnectorLanguage(), $this->generateDataAttribute('addjson', $this->encodeJson($json))),
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

        if ($gencode === ';stop;' || empty($gencode)) {
            // Remove the button marker, because here is nothing to add.
            $sourcebutton = '';
        } else {
            // Add the button.
            $sourcebutton = str_replace(
                '{language}',
                $model->getConnectorLanguage(),
                $this->getTemplateFileContent('sourcebutton')
            );
        }

        $json = $model->getJson();
        $help = $this->pool->messages->getHelp($model->getHelpid());
        if (!empty($help)) {
            $json['Help'] = $help;
        }

        return str_replace(
            array(
                '{name}',
                '{type}',
                '{ktype}',
                '{normal}',
                '{connector2}',
                '{gensource}',
                '{isExpanded}',
                '{addjson}',
                '{nest}',
                '{sourcebutton}',
                '{codewrapper1}',
                '{codewrapper2}',
            ),
            array(
                $model->getName(),
                $model->getType(),
                $cssType,
                $model->getNormal(),
                $this->renderConnector($model->getConnector2(128)),
                $this->generateDataAttribute('source', $gencode),
                '',
                $this->generateDataAttribute('addjson', $this->encodeJson($json)),
                $this->pool->chunks->chunkMe($this->renderNest($model, false)),
                $sourcebutton,
                $this->generateDataAttribute('codewrapper1', $this->pool->codegenHandler->generateWrapper1()),
                $this->generateDataAttribute('codewrapper2', $this->pool->codegenHandler->generateWrapper2()),
            ),
            $this->getTemplateFileContent('expandableChildNormal')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderRecursion(Model $model)
    {
        $template = parent::renderRecursion($model);
        // We add our json to the output.
        $json = $model->getJson();
        $help = $this->pool->messages->getHelp($model->getHelpid());
        if (!empty($help)) {
            $json['Help'] = $help;
        }
        return str_replace('{addjson}', $this->generateDataAttribute('addjson', $this->encodeJson($json)), $template);
    }

    /**
     * {@inheritDoc}
     */
    public function renderSingleEditableChild(Model $model)
    {

        // Prepare the json. Not much do display for form elements.
        $json = $this->encodeJson(array(
            'Help' => $this->pool->messages->getHelp($model->getHelpid()),
        ));
        return str_replace(
            '{addjson}',
            $this->generateDataAttribute('addjson', $json),
            parent::renderSingleEditableChild($model)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function renderButton(Model $model)
    {
        // Prepare the json. Not much do display for form elements.
        $json = $this->encodeJson(array(
            'Help' => $this->pool->messages->getHelp($model->getHelpid()),
        ));

        return str_replace(
            array('{addjson}', '{class}'),
            array($this->generateDataAttribute('addjson', $json), $model->getName()),
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
                '{kdebug-classes}',
                '{kconfiguration-classes}',
                '{klinks-classes}',
            ),
            array(
                $debugClass,
                $configClass,
                $linkClass,
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
        if ($configOnly) {
            $template = str_replace(
                '{kconfiguration-classes}',
                '',
                parent::renderFooter($caller, $configOutput)
            );
        } else {
            $template = str_replace(
                '{kconfiguration-classes}',
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
            array('{search}', '{KrexxId}'),
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
