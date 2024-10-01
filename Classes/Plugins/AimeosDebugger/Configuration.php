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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger;

use Aimeos\MW\DB\Statement\Base as StatementBase;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\DebugMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Decorators;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Getter;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\Properties;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ThroughMethods;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers\ViewFactory;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface as Typo3ConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods as AnalyseDebugMethods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods as AnalyseMethods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as IterateThroughMethods;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Plugin\Registration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Configuration for the Aimeos Debugger plugin.
 */
class Configuration implements PluginConfigInterface, Typo3ConstInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Aimeos debugger';
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
     * The Aimeos shop hat a lot of magical methods.
     *
     * This plugin tries to resolve them.
     */
    public function exec(): void
    {
        // Resolving the __get().
        Registration::registerEvent(PublicProperties::class . static::START_EVENT, Properties::class);

        // Resolving the getter that get their values from a private array.
        Registration::registerEvent(ThroughGetter::class . '::retrievePropertyValue::resolving', Getter::class);

        // Resolving the magical class methods of the decorator pattern.
        Registration::registerEvent(AnalyseMethods::class . static::START_EVENT, Decorators::class);

        // Resolving the magical factory for the view helpers (not to be confused
        // with fluid viewhelpers).
        Registration::registerEvent(AnalyseMethods::class . static::START_EVENT, ViewFactory::class);

        // Replacing the magical factory name in the method analysis.
        Registration::registerEvent(IterateThroughMethods::class . static::END_EVENT, ThroughMethods::class);

        // Adding additional debug methods.
        Registration::registerEvent(AnalyseDebugMethods::class . static::START_EVENT, DebugMethods::class);

        // No __toString for the db statement class.
        Registration::addMethodToDebugBlacklist(StatementBase::class, '__toString');

        // Adding additional texts.
        $extPath = ExtensionManagementUtility::extPath(static::EXT_KEY);
        Registration::registerAdditionalHelpFile($extPath . 'Resources/Private/Language/aimeos.kreXX.ini');
    }
}
