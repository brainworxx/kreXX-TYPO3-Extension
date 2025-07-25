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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\DynamicGetter;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Analyse\Objects as ObjectsFluid;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterWithoutGet;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\VhsMethods;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Getter as GetterFluid;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\CallerFinder\Fluid as CallerFinderFluid;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen as FluidCodegen;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Connectors as FluidConnectors;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface as Typo3ConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Special overwrites and event handlers for fluid.
 */
class Configuration implements PluginConfigInterface, Typo3ConstInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Fluid debugger';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \TYPO3\CMS\Core\Package\Exception
     */
    public function getVersion(): string
    {
        return ExtensionManagementUtility::getExtensionVersion(static::EXT_KEY);
    }

    /**
     * Code generation for fluid.
     */
    public function exec(): void
    {
        // Registering the fluid connector class.
        Registration::addRewrite(Connectors::class, FluidConnectors::class);
        // Registering the special source generation for methods.
        Registration::addRewrite(Codegen::class, FluidCodegen::class);
        // Special caller finder for Fluid.
        Registration::addRewrite(CallerFinder::class, CallerFinderFluid::class);
        // Move the getter to the same level as the public properties.
        Registration::addRewrite(Getter::class, GetterFluid::class);
        // Remove the constants-analysis.
        Registration::addRewrite(Objects::class, ObjectsFluid::class);

        // The code generation class is a singleton.
        // We need to reset the pool.
        Krexx::$pool->reset();

        // Register our event handler, to remove the 'get' from the getter
        // method names. Fluid does not use these.
        Registration::registerEvent(ThroughGetter::class . '::goThroughMethodList::end', GetterWithoutGet::class);
        // Another event switches to VHS code generation.
        Registration::registerEvent(ThroughMethods::class . static::END_EVENT, VhsMethods::class);
        // Special analysis for the ContentBlocks objects.
        Registration::registerEvent(ThroughGetter::class . static::START_EVENT, DynamicGetter::class);

        // Adding additional texts.
        $extPath = ExtensionManagementUtility::extPath(static::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/fluid.kreXX.ini');
    }
}
