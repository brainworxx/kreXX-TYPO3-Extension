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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle the backend interactions that are not ajax.
 */
class IndexController extends AbstractController implements ConstInterface
{
    /**
     * Simple index action, display everything.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function indexAction(): ResponseInterface
    {
        $this->checkProductiveSetting();

        // Has kreXX something to say? Maybe a write-protected logfolder?
        $this->retrieveKrexxMessages();
        $this->moduleTemplate->assignMultiple(
            values: ['settings' => $this->settingsModel]
        );

        return $this->moduleTemplateRender();
    }

    /**
     * Save the configuration, hen redirect back to the index.
     *
     * @param \Brainworxx\Includekrexx\Domain\Model\Settings|null $settings
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function saveAction(?Settings $settings = null): ResponseInterface
    {
        if ($settings === null) {
            // Huh, someone was messing with the URL, redirect back to index.
            return $this->redirect(actionName: 'index');
        }
        // Check for writing permission.
        // Check the actual writing process.
        $jsonPath = $settings->prepareFileName(
            $this->pool->config->getPathToConfigFile()
        );
        if (
            is_writable(filename: dirname(path: $jsonPath))
            && file_put_contents(filename: $jsonPath, data: $settings->generateContent())
        ) {
            // File was saved successfully.
            $this->addFlashMessage(
                messageBody: static::translate(key: static::SAVE_SUCCESS_TEXT, arguments: [$jsonPath]),
                messageTitle: static::translate(static::SAVE_SUCCESS_TITLE)
            );
        } else {
            // Something went wrong here!
            $this->addFlashMessage(
                messageBody: static::translate(key: static::FILE_NOT_WRITABLE, arguments: [$jsonPath]),
                messageTitle: static::translate(static::SAVE_FAIL_TITLE),
                severity: ContextualFeedbackSeverity::ERROR
            );
        }

        // Retrieve the failed messages from kreXX and redirect back.
        $this->retrieveKrexxMessages();
        return $this->redirect(actionName: 'index');
    }

    /**
     * Dispatch a logfile.
     *
     * @param ServerRequest $serverRequest
     *
     * @return \TYPO3\CMS\Core\Http\NullResponse
     */
    public function dispatchAction(ServerRequest $serverRequest): NullResponse
    {
        // No directory traversal for you!
        // Get the filepath.
        $rawId = $serverRequest->getQueryParams()['tx_includekrexx_tools_includekrexxkrexxconfiguration']['id'];
        $file = $this->pool->config->getLogDir() .
            preg_replace(pattern: '/[^0-9]/', replacement: '', subject: (string)$rawId) .
            '.Krexx.html';
        // We open and then send the file.
        $this->dispatchFile(path: $file);

        return GeneralUtility::makeInstance(NullResponse::class);
    }
}
