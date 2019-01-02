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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger;

use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;

/**
 * Special overwrites and event handlers for fluid.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger
 */
class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'Fluid debugger';
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
     * Code generation for fluid.
     */
    public static function exec()
    {
        // Registering the fluid connector class.
        Registration::addRewrite(
            'Brainworxx\\Krexx\\Analyse\\Code\\Connectors',
            'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Rewrites\\Code\\Connectors'
        );

        // Registering the special source generation for methods.
        Registration::addRewrite(
            'Brainworxx\\Krexx\\Analyse\\Code\\Codegen',
            'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Rewrites\Code\\Codegen'
        );

        // Depending on the TYPO3 version, we need another fluid caller finder.
        if (version_compare(TYPO3_version, '8.4', '>')) {
            // Fluid 2.2 or higher
            Registration::addRewrite(
                'Brainworxx\\Krexx\\Analyse\\Caller\\CallerFinder',
                'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Rewrites\\CallerFinder\\Fluid'
            );
        } else {
            // Fluid 2.0 or lower.
            Registration::addRewrite(
                'Brainworxx\\Krexx\\Analyse\\Caller\\CallerFinder',
                'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\Rewrites\\CallerFinder\\FluidOld'
            );
        }

        // The code generation class is a singleton.
        // We need to reset the pool.
        \Krexx::$pool->reset();

        // Register our event handler, to remove the 'get' from the getter
        // method names. Fluid does not use these.
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end',
            'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\EventHandlers\\GetterWithoutGet'
        );
        // Another event switches to VHS code generation.
        Registration::registerEvent(
            'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end',
            'Brainworxx\\Includekrexx\\Plugins\\FluidDebugger\\EventHandlers\\VhsMethods'
        );

        // Adding additional texts.
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('includekrexx');
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/fluid.kreXX.ini');

        // Register our debug-viewhelper globally, so people don't have to
        // do it inside the template. 'krexx' as a namespace should be unique enough.
        if (version_compare(TYPO3_version, '8.5', '>=')) {
            if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'])) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['krexx'] = array(
                    0 => 'Brainworxx\\Includekrexx\\ViewHelpers'
                );
            }
        }

        // Add the legacy debug viewhelper, in case people are using the old krexx
        // namespace.
        if (!class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
            include_once $extPath . 'Classes/ViewHelpers/LegacyDebugViewHelper.php';
        }
    }
}
