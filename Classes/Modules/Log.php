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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Modules;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Controller\AbstractController;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Frontend Access to the logfiles inside the admin panel.
 *
 * This class *must-not-have* any class variables, because it gets serialized
 * by the core. Any class variable would cause a fatal in the frontend.
 *
 * @package Brainworxx\Includekrexx\Modules
 */
class Log extends AbstractSubModule implements
    DataProviderInterface,
    ContentProviderInterface,
    ResourceProviderInterface
{
    use LanguageTrait;

    const MESSAGE_SEVERITY_ERROR = 'error';
    const MESSAGE_SEVERITY_INFO = 'info';
    const TRANSLATION_PREFIX = 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:';

    /**
     * The identifyer for the Admin Panel Module.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return Bootstrap::KREXX;
    }

    /**
     * Sub-Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return static::translate(static::TRANSLATION_PREFIX . 'mlang_tabs_tab');
    }

    /**
     * Retrieve the file list.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *   The frontend request. Currently not used.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
     *   The data we will assign to the admin panel.
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData(
            ['files' => GeneralUtility::makeInstance(ObjectManager::class)
                ->get(LogfileList::class)
                ->retrieveFileList()]
        );
    }

    /**
     * Render a standalone view with the links.
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData $data
     *   The data we need to display.
     *
     * @return string
     *   The rendered content.
     */
    public function getContent(ModuleData $data): string
    {
        if ($this->hasAccess() === false) {
            return $this->renderMessage(
                static::translate(static::TRANSLATION_PREFIX . AbstractController::ACCESS_DENIED),
                static::MESSAGE_SEVERITY_ERROR
            );
        }

        $filelist = $data->getArrayCopy();

        // Handling an empty log file list.
        if (empty($filelist['files'])) {
            return $this->retrieveKrexxMessages() . $this->renderMessage(
                static::translate(static::TRANSLATION_PREFIX . 'log.noresult'),
                static::MESSAGE_SEVERITY_INFO
            );
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Private/Templates/Modules/Log.html')
        );
        $view->setPartialRootPaths(['EXT:includekrexx/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:includekrexx/Resources/Private/Layouts']);
        $view->assignMultiple($filelist);


        return $this->retrieveKrexxMessages() . $view->render();
    }

    /**
     * {@inheritDoc}
     */
    public function getCssFiles(): array
    {
        return ['EXT:includekrexx/Resources/Public/Css/Adminpanel.css'];
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

    /**
     * Additional check, if the current Backend user has access to the extension.
     *
     * @return bool
     *   The result of the check.
     */
    protected function hasAccess(): bool
    {
        return isset($GLOBALS['BE_USER']) &&
            $GLOBALS['BE_USER']->check('modules', AbstractCollector::PLUGIN_NAME);
    }

    /**
     * Similar to the Flashmessages, just for the Admin Panel.
     *
     * @param string $text
     *   The text to display.
     * @param string $severity
     *   One of the severity constants from this class, which is also the
     *   message css class
     *
     * @return string
     *   The rendered HTML message.
     */
    protected function renderMessage(string $text, string $severity): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:includekrexx/Resources/Private/Templates/Modules/Message.html')
        );
        $view->setPartialRootPaths(['EXT:includekrexx/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:includekrexx/Resources/Private/Layouts']);
        $view->assignMultiple(
            [
                'text' => (string) $text,
                'severity' => $severity,
            ]
        );

        return $view->render();
    }

    /**
     * Retrieve messages from the kreXX lib.
     *
     * @return string
     *   The renders messages.
     */
    protected function retrieveKrexxMessages(): string
    {
        // Relay the renderedMessages from kreXX.
        Pool::createPool();
        $renderedMessages = '';

        foreach (Krexx::$pool->messages->getKeys() as $message) {
            $renderedMessages .= $this->renderMessage(
                static::translate(
                    static::TRANSLATION_PREFIX . $message['key'],
                    Bootstrap::EXT_KEY,
                    $message['params']
                ),
                static::MESSAGE_SEVERITY_ERROR
            );
        }

        return $renderedMessages;
    }
}
