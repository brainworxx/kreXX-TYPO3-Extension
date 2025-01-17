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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use stdClass;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles the backend ajax requests for the loglist.
 */
class AjaxController implements ConstInterface, ControllerConstInterface
{
    use LanguageTrait;
    use AccessTrait;

    /**
     * List the logfiles with their corresponding metadata.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $serverRequest
     *   The current server request.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     */
    public function refreshLoglistAction(ServerRequest $serverRequest): Response
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        // There is already an access check in the LogfileList.
        // We will not check twice.
        $fileList = GeneralUtility::makeInstance(LogfileList::class)
            ->retrieveFileList();

        $response->getBody()->write(json_encode($fileList));

        return $response;
    }

    /**
     * Deletes a logfile.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $serverRequest
     *   The current server request.
     * @param \TYPO3\CMS\Core\Http\Response|null $response
     *   The prepared response object. Since 10.0, we need to create this one
     *   by ourselves.
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     */
    public function deleteAction(ServerRequest $serverRequest, ?Response $response = null): Response
    {
        if ($response === null) {
            /** @var Response $response */
            $response = GeneralUtility::makeInstance(Response::class);
        }

        $result = new stdClass();

        if (!$this->hasAccess()) {
            $result->class  = 'error';
            $result->text = static::translate(static::ACCESS_DENIED);

            $response->getBody()->write(json_encode($result));
            return $response;
        }

        Pool::createPool();

        // No directory traversal for you!
        $fileId = preg_replace('/[^0-9]/', '', $serverRequest->getQueryParams()['fileid']);
        // Directly add the delete-result return value.
        $file = Krexx::$pool->config->getLogDir() . $fileId . '.Krexx';

        if ($this->delete($file . '.html') && $this->delete($file . '.html.json')) {
            $result->class  = 'success';
            $result->text = static::translate('fileDeleted', [$fileId]);
        } else {
            $result->class  = 'error';
            $result->text = static::translate('fileDeletedFail', ['n/a']);
        }

        $response->getBody()->write(json_encode($result));
        return $response;
    }

    /**
     * Physically deletes a file, if possible.
     *
     * @param string $file
     *   Path to the file we want to delete.
     *
     * @return bool
     *   The success status of the deleting.
     */
    protected function delete(string $file): bool
    {
        if (is_writable(dirname(($file))) && file_exists($file)) {
            // Away with you!
            unlink($file);
            return true;
        } else {
            return false;
        }
    }
}
