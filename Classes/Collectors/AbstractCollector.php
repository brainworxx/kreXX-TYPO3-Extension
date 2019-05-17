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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

abstract class AbstractCollector
{
    const MODULE_DATA = 'moduleData';
    const PLUGIN_NAME = 'tools_IncludekrexxKrexxConfiguration';

    const SETTINGS_NAME = 'name';
    const SETTINGS_HELPTEXT = 'helptext';
    const SETTINGS_VALUE = 'value';
    const SETTINGS_USE_FACTORY_SETTINGS = 'useFactorySettings';
    const SETTINGS_FALLBACK = 'fallback';
    const SETTINGS_MODE = 'mode';
    const SETTINGS_OPTIONS = 'options';

    /**
     * The kreXX pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * The current backend user
     *
     * @var array
     */
    protected $userUc = [];

    /**
     * List of options, that are 'expert' only.
     *
     * @var array
     */
    protected $expertOnly = [
        'detectAjax',
        'useScopeAnalysis',
        'maxStepNumber',
        'arrayCountLimit',
        'debugMethods',
        'maxRuntime',
        'memoryLeft',
        'maxfiles'
    ];

    /**
     * Do we have access here?
     *
     * @var bool
     */
    protected $hasAccess = false;

    /**
     * Inject the pool.
     */
    public function __construct()
    {
        Pool::createPool();
        $this->pool = Krexx::$pool;
        if (isset($GLOBALS['BE_USER'])) {
            $user = $GLOBALS['BE_USER'];
            $this->hasAccess = $user
                ->check('modules', static::PLUGIN_NAME);
        }
        if ($this->hasAccess &&
            isset($user->uc[static::MODULE_DATA][IndexController::MODULE_KEY])
        ) {
            $this->userUc = $user->uc[static::MODULE_DATA][IndexController::MODULE_KEY];
        }
    }

    /**
     * Depending on the TYPO3 version, we must use different classes to get a
     * functioning link to the backend dispatcher.
     *
     * @param string $id
     *   The id of the file we want to get the url from.
     *
     * @return string
     *   The URL
     */
    protected function getRoute($id)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        if (version_compare(TYPO3_version, '9.0', '>=')) {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get(UriBuilder::class);

            return (string)$uriBuilder->buildUriFromRoute(
                'tools_IncludekrexxKrexxConfiguration_dispatch',
                [
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[id]' => $id,
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[action]' => 'dispatch',
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[controller]' => 'Index'
                ]
            );
        } else {
            /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
            return $uriBuilder
                ->reset()
                ->setArguments(['M' => static::PLUGIN_NAME])
                ->uriFor(
                    'dispatch',
                    ['id' => $id],
                    'Index',
                    Bootstrap::EXT_KEY,
                    static::PLUGIN_NAME
                );
        }
    }

    /**
     * Assigning stuff to the view.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    abstract public function assignData(ViewInterface $view);
}
