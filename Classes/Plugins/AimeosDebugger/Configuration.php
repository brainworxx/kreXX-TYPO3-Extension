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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;

class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getName()
    {
        return 'Aimeos debugger';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getVersion()
    {
        return 'v1.0.1';
    }

    /**
     * The Aimeos shop hat a lot of magical methods.
     *
     * This plugin tries to resolve them.
     */
    public static function exec()
    {
        // Resolving the __get().
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties::callMe::start',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\EventHandlers\\Properties'
        );

        // Resolving the getter that get their values from an private array.
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\EventHandlers\\Getter'
        );

        // Resolving the magical class methods of the decorator pattern.
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\EventHandlers\\Methods'
        );

        // Resolving the magical factory for the view helpers (not to be confused
        // with fluid viewhelpers).
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\EventHandlers\\ViewFactory'
        );

        // Replacing the magical factory name in the method analysis.
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end',
            'Brainworxx\\Includekrexx\\Plugins\\AimeosDebugger\\EventHandlers\\ThroughMethods'
        );

        // No __toString for the db statement class.
        Registration::addMethodToDebugBlacklist(
            '\\Aimeos\\MW\\DB\\Statement\\Base',
            '__toString'
        );

        // Adding additional texts.
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(Bootstrap::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/aimeos.kreXX.ini');
    }
}
