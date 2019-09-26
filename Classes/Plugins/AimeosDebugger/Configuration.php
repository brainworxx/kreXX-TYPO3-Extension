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
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\DebugMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Methods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods as AnalyseDebugMethods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods as AnalyseMethods;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as IterateThroughMethods;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Aimeos\MW\DB\Statement\Base as StatementBase;

class Configuration implements PluginConfigInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Aimeos debugger';
    }

    /**
     * {@inheritdoc}
     *
     */
    public function getVersion()
    {
        return ExtensionManagementUtility::getExtensionVersion(Bootstrap::EXT_KEY);
    }

    /**
     * The Aimeos shop hat a lot of magical methods.
     *
     * This plugin tries to resolve them.
     */
    public function exec()
    {
        // Resolving the __get().
        Registration::registerEvent(
            PublicProperties::class . '::callMe::start',
            Properties::class
        );

        // Resolving the getter that get their values from an private array.
        Registration::registerEvent(
            ThroughGetter::class . '::retrievePropertyValue::resolving',
            Getter::class
        );

        // Resolving the magical class methods of the decorator pattern.
        Registration::registerEvent(
            AnalyseMethods::class . '::callMe::start',
            Methods::class
        );

        // Resolving the magical factory for the view helpers (not to be confused
        // with fluid viewhelpers).
        Registration::registerEvent(
            AnalyseMethods::class . '::callMe::start',
            ViewFactory::class
        );

        // Replacing the magical factory name in the method analysis.
        Registration::registerEvent(
            IterateThroughMethods::class . '::callMe::end',
            ThroughMethods::class
        );

        // Adding additional debug methods.
        Registration::registerEvent(
            AnalyseDebugMethods::class . '::callMe::start',
            DebugMethods::class
        );

        // No __toString for the db statement class.
        Registration::addMethodToDebugBlacklist(
            StatementBase::class,
            '__toString'
        );

        // Adding additional texts.
        $extPath = ExtensionManagementUtility::extPath(Bootstrap::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/aimeos.kreXX.ini');
    }
}
