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

namespace Brainworxx\Includekrexx\Modules;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Frontend Access to the logfiles inside the admin panel.
 *
 * @package Brainworxx\Includekrexx\Modules
 */
class Log extends AbstractSubModule implements
    DataProviderInterface,
    ContentProviderInterface,
    ResourceProviderInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'krexx';
    }

    /**
     * Sub-Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'
        );
    }

    /**
     * Retrieve the file list.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *   The frontend request. Currently not used.
     * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
     *   The data we will assign to the admin panel.
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData(
            array('files' => GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
                ->get('Brainworxx\\Includekrexx\\Collectors\\LogfileList')
                ->retrieveFileList())
        );
    }

    /**
     * Render a standalone view with the links.
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData $data
     * @return string
     */
    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:includekrexx/Resources/Private/Templates/Modules/Log.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:includekrexx/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:includekrexx/Resources/Private/Layouts']);
        $view->assignMultiple($data->getArrayCopy());

        return $view->render();
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.css'];
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [
            'EXT:includekrexx/Resources/Public/Css/Adminpanel.css'
        ];
    }
    /**
     * No JS so far.
     *
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return [];
    }
}