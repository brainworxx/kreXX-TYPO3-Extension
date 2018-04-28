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

namespace Brainworxx\Includekrexx\Plugins\FluidCodeGen;

use Brainworxx\Krexx\Service\Factory\Factory;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;

/**
 * Special overwrites and event handlers for fluid.
 *
 * @package Brainworxx\Includekrexx\Plugins\Fluid
 */
class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'Fluid code generation v1.0';
    }

    /**
     * Code generation for fluid.
     */
    public static function exec()
    {
        // Registering the fluid connector class.
        Factory::$rewrite['Brainworxx\\Krexx\\Analyse\\Code\\Connectors'] =
            'Brainworxx\\Includekrexx\\Plugins\\FluidCodeGen\\Rewrites\\Code\\Connectors';

        // Registering the special source generation for methods.
        Factory::$rewrite['Brainworxx\\Krexx\\Analyse\\Code\\Codegen'] =
            'Brainworxx\\Includekrexx\\Plugins\\FluidCodeGen\\Rewrites\Code\\Codegen';

        // The code generation class is a singleton.
        // We need to reset the pool.
        \Krexx::$pool->reset();

        // Register our event handler, to remove the 'get' from the getter
        // method names. Fluid does not use these.
        \Krexx::$pool->eventService->register(
            'Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::goThroughMethodList::end',
            'Brainworxx\\Includekrexx\\Plugins\\FluidCodeGen\\EventHandlers\\GetterWithoutGet'
        );
        // Another event switches to VHS code generation.
        \Krexx::$pool->eventService->register(
            'Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::callMe::end',
            'Brainworxx\\Includekrexx\\Plugins\\FluidCodeGen\\EventHandlers\\VhsMethods'
            );
    }
}
