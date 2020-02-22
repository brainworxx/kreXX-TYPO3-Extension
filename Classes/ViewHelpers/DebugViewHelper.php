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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\ViewHelpers;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use ReflectionClass;

/**
 * Our fluid wrapper for kreXX.
 *
 * @namespace
 *   When using TYPO3 6.2 until 8.4, you need to declare the namespace first:
 *   {namespace krexx=Brainworxx\Includekrexx\ViewHelpers}
 *   or
 *   <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
 *         xmlns:krexx="http://typo3.org/ns/Brainworxx/Includekrexx/ViewHelpers"
 *         data-namespace-typo3-fluid="true">
 *   TYPO3 8.5 and beyond don't need to do that anymore  ;-)
 *
 * @usage
 *   <krexx:debug>{_all}</krexx:debug>
 *   or
 *   <krexx:debug value="{my: 'value', to: 'analyse'}" />
 *   Use this part if you don't want fluid to escape your string or if you are
 *   stitching together an array.
 */
class DebugViewHelper extends AbstractViewHelper
{
    const ARGUMENT_VALUE = 'value';

    /**
     * No escaping for the rendered children, we want then as they are.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * We do not have any output.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * {@inheritdoc}
     *
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument(static::ARGUMENT_VALUE, 'mixed', 'The variable we want to analyse.', false);
    }

    /**
     * A wrapper for kreXX();
     *
     * @throws \ReflectionException
     *
     * @return string
     *   Returns an empty string.
     */
    public function render(): string
    {
        Pool::createPool();
        $view = $this->viewHelperVariableContainer->getView();
        Krexx::$pool->registry->set('view', $view);
        Krexx::$pool->registry->set('viewReflection', new ReflectionClass($view));
        Krexx::$pool->registry->set('renderingContext', $this->renderingContext);
        Registration::activatePlugin(
            FluidConfiguration::class
        );

        $this->analysis();

        Registration::deactivatePlugin(
            FluidConfiguration::class
        );
        Krexx::$pool->registry->set('view', null);
        Krexx::$pool->registry->set('viewReflection', null);
        Krexx::$pool->registry->set('renderingContext', null);

        return '';
    }

    /**
     * Analyse the stuff from the template.
     */
    protected function analysis()
    {
        $found  = false;
        if (!is_null($this->arguments[static::ARGUMENT_VALUE])) {
            Krexx::open($this->arguments[static::ARGUMENT_VALUE]);
            $found = true;
        }

        $children = $this->renderChildren();
        if (!is_null($children)) {
            Krexx::open($children);
            $found = true;
        }

        if (!$found) {
            // Both are NULL, we must tell the dev!
            Krexx::open(null);
        }
    }
}
