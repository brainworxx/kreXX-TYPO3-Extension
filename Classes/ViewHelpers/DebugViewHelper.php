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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Plugins\FluidDebugger\Configuration as FluidConfiguration;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\Registration;
use ReflectionClass;
use Throwable;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Our fluid wrapper for kreXX.
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
    /**
     * @var string
     */
    protected const ARGUMENT_VALUE = 'value';

    /**
     * @var string
     */
    public const REGISTRY_VIEW = 'view';

    /**
     * @var string
     */
    public const REGISTRY_VIEW_REFLECTION = 'viewReflection';

    /**
     * @var string
     */
    public const REGISTRY_RENDERING_CONTEXT = 'renderingContext';

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
     * The name of the analysis methods in the kreXX class.
     *
     * @var string
     */
    protected string $analysisType = 'open';

    /**
     * The rendered children.
     *
     * @var mixed
     */
    protected $children;

    /**
     * {@inheritdoc}
     */
    public function initializeArguments(): void
    {
        $this->registerArgument(static::ARGUMENT_VALUE, 'mixed', 'The variable we want to analyse.');
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
        try {
            $this->children = $this->renderChildren();
        } catch (Throwable $e) {
        }

        Pool::createPool();
        $view = $this->viewHelperVariableContainer->getView();
        $registry = Krexx::$pool->registry;
        $registry->set(static::REGISTRY_VIEW, $view);
        $registry->set(static::REGISTRY_VIEW_REFLECTION, new ReflectionClass($view));
        $registry->set(static::REGISTRY_RENDERING_CONTEXT, $this->renderingContext);
        Registration::activatePlugin(
            FluidConfiguration::class
        );

        $this->analysis();

        Registration::deactivatePlugin(
            FluidConfiguration::class
        );
        $registry->set(static::REGISTRY_VIEW, null);
        $registry->set(static::REGISTRY_VIEW_REFLECTION, null);
        $registry->set(static::REGISTRY_RENDERING_CONTEXT, null);

        return '';
    }

    /**
     * Analyse the stuff from the template.
     */
    protected function analysis(): void
    {
        $type = $this->analysisType;
        $found  = false;
        if (isset($this->arguments[static::ARGUMENT_VALUE])) {
            Krexx::$type($this->arguments[static::ARGUMENT_VALUE]);
            $found = true;
        }

        if ($this->children !== null) {
            Krexx::$type($this->children);
            $found = true;
        }

        if (!$found) {
            // Both are NULL, we must tell the dev!
            Krexx::$type(null);
        }
    }
}
