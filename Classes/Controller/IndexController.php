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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Domain\Model\Settings;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

class IndexController extends AbstractController
{
    /**
     * Simple index action, display everything.
     *
     * @return string|null
     */
    public function indexAction()
    {
        if ($this->hasAccess() === false) {
            // Sorry!
            $this->addFlashMessage(
                static::translate(static::ACCESS_DENIED, Bootstrap::EXT_KEY),
                static::translate(static::ACCESS_DENIED, Bootstrap::EXT_KEY),
                FlashMessage::ERROR
            );
            return '';
        }

        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a write protected logfolder?
        $this->retrieveKrexxMessages();
        $this->configuration->assignData($this->view);
        $this->formConfiguration->assignData($this->view);
        $this->view->assign('settings', $this->settingsModel);
        $this->assignCssJs();
        return null;
    }

    /**
     * Save the configuration, hen redirect back to the index.
     *
     * @param \Brainworxx\Includekrexx\Domain\Model\Settings $settings
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function saveAction(Settings $settings)
    {
        if ($this->hasAccess() === false) {
            $this->addFlashMessage(
                static::translate(static::ACCESS_DENIED, Bootstrap::EXT_KEY),
                static::translate(static::SAVE_FAIL_TITLE, Bootstrap::EXT_KEY),
                FlashMessage::ERROR
            );
            $this->redirect('index');
        }

        $filepath = $this->pool->config->getPathToConfigFile();

        // Check for writing permission.
        // Check the actual writing process.
        if (is_writable(dirname($filepath)) && file_put_contents($filepath, $settings->generateIniContent())) {
            // File was saved successfully.
            $this->addFlashMessage(
                static::translate(static::SAVE_SUCCESS_TEXT, Bootstrap::EXT_KEY, [$filepath]),
                static::translate(static::SAVE_SUCCESS_TITLE, Bootstrap::EXT_KEY),
                FlashMessage::OK
            );
        } else {
            // Something went wrong here!
            $this->addFlashMessage(
                static::translate(static::FILE_NOT_WRITABLE, Bootstrap::EXT_KEY, [$filepath]),
                static::translate(static::SAVE_FAIL_TITLE, Bootstrap::EXT_KEY),
                FlashMessage::ERROR
            );
        }

        // Retrieve the failed messages from kreXX and redirect back.
        $this->retrieveKrexxMessages();
        $this->redirect('index');
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
        $id = preg_replace('/[^0-9]/', '', $rawId);
        // Get the filepath.
        $file = $this->pool->config->getLogDir() . $id . '.Krexx.html';
        if ($this->hasAccess()) {
            // We open and then send the file.
            $this->dispatchFile($file);
        }
        return $this->createResponse();
    }
}
