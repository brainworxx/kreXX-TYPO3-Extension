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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

abstract class AbstractCollector
{
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
    protected $userUc = array();

    /**
     * List of options, that are 'expert' only.
     *
     * @var array
     */
    protected $expertOnly = array(
        'detectAjax',
        'useScopeAnalysis',
        'maxStepNumber',
        'arrayCountLimit',
        'debugMethods',
        'maxRuntime',
        'memoryLeft',
        'maxfiles'
    );

    /**
     * Inject the pool.
     */
    public function __construct()
    {
        Pool::createPool();
        $this->pool = \Krexx::$pool;
        $user = $GLOBALS['BE_USER'];
        if (isset($user->uc['moduleData'][IndexController::MODULE_KEY])) {
            $this->userUc = $user->uc['moduleData'][IndexController::MODULE_KEY];
        }
    }

    /**
     * Additional check, if the current Backend user has access to the extension.
     *
     * @return bool
     *   The result of the check.
     */
    protected function hasAccess()
    {
        return isset($GLOBALS['BE_USER']) &&
            $GLOBALS['BE_USER']->check('modules', 'tools_IncludekrexxKrexxConfiguration');
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
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        if (version_compare(TYPO3_version, '9.0', '>=')) {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get('TYPO3\\CMS\\Backend\\Routing\\UriBuilder');

            return (string)$uriBuilder->buildUriFromRoute(
                'tools_IncludekrexxKrexxConfiguration_dispatch',
                array(
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[id]' => $id,
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[action]' => 'dispatch',
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[controller]' => 'Index'
                )
            );
        } else {
            /** @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder */
            $uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
            return $uriBuilder
                ->reset()
                ->setArguments(array('M' => 'tools_IncludekrexxKrexxConfiguration'))
                ->uriFor(
                    'dispatch',
                    array('id' => $id),
                    'Index',
                    'includekrexx',
                    'tools_IncludekrexxKrexxConfiguration'
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
