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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Includekrexx\Domain\Model\Settings;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * Handle the backend interactions that are not ajax.
 */
class IndexController extends AbstractController implements ConstInterface
{
    /**
     * Simple index action, display everything.
     *
     * @return string|null|\Psr\Http\Message\ResponseInterface
     */
    public function indexAction()
    {
        if ($this->hasAccess() === false) {
            // Sorry!
            $this->addFlashMessage(
                static::translate(static::ACCESS_DENIED, static::EXT_KEY),
                static::translate(static::ACCESS_DENIED, static::EXT_KEY),
                AbstractMessage::ERROR
            );
            if (method_exists($this, 'htmlResponse') === true) {
                $response = $this->responseFactory->createResponse()
                    ->withAddedHeader('Content-Type', 'text/html; charset=utf-8');
                $response->getBody()->write('');
                return $response;
            }

            return '';
        }

        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a write-protected logfolder?
        $this->retrieveKrexxMessages();
        $this->configuration->assignData($this->view);
        $this->formConfiguration->assignData($this->view);
        $this->view->assign('settings', $this->settingsModel);
        $this->assignCssJs();

        if (method_exists($this, 'htmlResponse') === true) {
            return GeneralUtility::makeInstance(HtmlResponse::class, $this->moduleTemplate->renderContent());
        }

        return $this->moduleTemplate->renderContent();
    }

    /**
     * Save the configuration, hen redirect back to the index.
     *
     * @param \Brainworxx\Includekrexx\Domain\Model\Settings $settings
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     *
     * @return void
     */
    public function saveAction(Settings $settings)
    {
        if ($this->hasAccess() === false) {
            $this->addFlashMessage(
                static::translate(static::ACCESS_DENIED, static::EXT_KEY),
                static::translate(static::SAVE_FAIL_TITLE, static::EXT_KEY),
                AbstractMessage::ERROR
            );
            return $this->redirect('index');
        }

        $filepath = $this->pool->config->getPathToConfigFile();

        // Check for writing permission.
        // Check the actual writing process.
        if (is_writable(dirname($filepath)) && file_put_contents($filepath, $settings->generateIniContent())) {
            // File was saved successfully.
            $this->addFlashMessage(
                static::translate(static::SAVE_SUCCESS_TEXT, static::EXT_KEY, [$filepath]),
                static::translate(static::SAVE_SUCCESS_TITLE, static::EXT_KEY),
                AbstractMessage::OK
            );
        } else {
            // Something went wrong here!
            $this->addFlashMessage(
                static::translate(static::FILE_NOT_WRITABLE, static::EXT_KEY, [$filepath]),
                static::translate(static::SAVE_FAIL_TITLE, static::EXT_KEY),
                AbstractMessage::ERROR
            );
        }

        // Retrieve the failed messages from kreXX and redirect back.
        $this->retrieveKrexxMessages();
        return $this->redirect('index');
    }

    /**
     * Dispatch a logfile.
     *
     * @param ServerRequest|null $serverRequest
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface|\TYPO3\CMS\Core\Http\NullResponse
     */
    public function dispatchAction(ServerRequest $serverRequest = null)
    {
        // And I was so happy to get rid of the 4.5 compatibility nightmare.
        if ($this->request === null) {
            $rawId = $serverRequest->getQueryParams()['tx_includekrexx_tools_includekrexxkrexxconfiguration']['id'];
        } else {
            try {
                $rawId = $this->request->getArgument('id');
            } catch (NoSuchArgumentException $e) {
                $rawId = '';
            }
        }

        // No directory traversal for you!
        // Get the filepath.
        $file = $this->pool->config->getLogDir() . preg_replace('/[^0-9]/', '', (string) $rawId) . '.Krexx.html';
        if ($this->hasAccess()) {
            // We open and then send the file.
            $this->dispatchFile($file);
        }
        return $this->createResponse();
    }
}
