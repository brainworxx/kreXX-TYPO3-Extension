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

namespace Brainworxx\Includekrexx\Plugins\AimeosMagic;

use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;

class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getName()
    {
        return 'Aimeos magical method resolving v1.0';
    }

    /**
     * The Aimeos shop hat a lot of magical methods.
     *
     * This plugin tries to resolve them.
     */
    public static function exec()
    {
        // Resolving the __get()
        Event::register(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties::callMe::start',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\EventHandlers\\MagicalProperties'
        );

        // Resolving the __function()
        Event::register(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\EventHandlers\\MagicalFunctions'
        );

        // Different display text for magical aimoes methods
        Event::register(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\EventHandlers\\MagicalFunctionsDumping'
        );

        // Make sure to mark the recursions as aimeos magic stuff
        Event::register(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::recursion',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\EventHandlers\\MagicalFunctionsDumping'
        );

        // Make sure to mark the magical Aimeos getter as such
        Event::register(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Getter::analysisEnd',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosMagic\\EventHandlers\\MagicalFunctionsDumping'
        );
    }
}