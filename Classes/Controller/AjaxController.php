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

use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Service\LanguageTrait;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use stdClass;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

/**
 * Handles the backend ajax requests for the loglist.
 */
#[AsController]
class AjaxController implements ConstInterface, ControllerConstInterface
{
    use LanguageTrait;

    /**
     * @param \TYPO3\CMS\Core\Http\Response $response
     * @param \Brainworxx\Includekrexx\Collectors\LogfileList $logfileList
     */
    public function __construct(
        protected Response $response,
        protected LogfileList $logfileList
    ) {
    }

    /**
     * List the logfiles with their corresponding metadata.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $serverRequest
     *   The current server request.
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     */
    public function refreshLoglistAction(ServerRequest $serverRequest): Response
    {
        // There is already an access check in the LogfileList.
        // We will not check twice.
        $this->response->getBody()->write(
            string: json_encode(value: $this->logfileList->retrieveFileList())
        );

        return $this->response;
    }

    /**
     * Deletes a logfile.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $serverRequest
     *   The current server request.
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     */
    public function deleteAction(ServerRequest $serverRequest): Response
    {
        $result = new stdClass();
        Pool::createPool();

        // No directory traversal for you!
        $fileId = preg_replace(
            pattern: '/[^0-9]/',
            replacement: '',
            subject: $serverRequest->getQueryParams()['fileid'] ?? ''
        );
        // Directly add the delete-result return value.
        $file = Krexx::$pool->config->getLogDir() . $fileId . '.Krexx';

        if ($this->delete(file: $file . '.html') && $this->delete(file: $file . '.html.json')) {
            $result->class = 'success';
            $result->text = static::translate(key:'fileDeleted', arguments: [$fileId]);
        } else {
            $result->class = 'error';
            $result->text = static::translate(key: 'fileDeletedFail', arguments: ['n/a']);
        }

        $this->response->getBody()->write(string: json_encode(value: $result));
        return $this->response;
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
        if (is_writable(filename: dirname(path: $file)) && file_exists(filename: $file)) {
            // Away with you!
            unlink(filename: $file);
            return true;
        } else {
            return false;
        }
    }
}
