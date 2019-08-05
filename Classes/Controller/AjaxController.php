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

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use stdClass;

class AjaxController
{
    /**
     * List the logfiles with their corresponding meta data.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $serverRequest
     *   The current server request.
     * @param \TYPO3\CMS\Core\Http\Response|null
     *   The prepared response object. Since 10.0, we need to create thjis one
     *   by ourself.
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     */
    public function refreshLoglistAction(ServerRequest $serverRequest, Response $response = null)
    {
        if ($response === null) {
            $response = GeneralUtility::makeInstance(Response::class);
        }

        $fileList = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(LogfileList::class)
            ->retrieveFileList();

        $response->getBody()->write(json_encode($fileList));

        return $response;
    }

    /**
     * Deletes a logfile.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $arg1
     *   The current server request.
     * @param \TYPO3\CMS\Core\Http\Response $response
     *   The prepared response object.
     *
     * @return \TYPO3\CMS\Core\Http\Response
     *   The response with the json string.
     */
    public function deleteAction(ServerRequest $arg1, Response $response)
    {
        $result = new stdClass();

        if ($this->hasAccess() === false) {
            $result->class  = 'error';
            $result->text = LocalizationUtility::translate('accessDenied', Bootstrap::EXT_KEY);
        } else {
            Pool::createPool();

            // No directory traversal for you!
            $id = preg_replace('/[^0-9]/', '', GeneralUtility::_GET('fileid'));
            // Directly add the delete result return value.
            $file = Krexx::$pool->config->getLogDir() . $id . '.Krexx';

            if ($this->delete($file . '.html') && $this->delete($file . '.html.json')) {
                $result->class  = 'success';
                $result->text = LocalizationUtility::translate('fileDeleted', Bootstrap::EXT_KEY, [$id]);
            } else {
                $result->class  = 'error';
                $result->text = LocalizationUtility::translate('fileDeletedFail', Bootstrap::EXT_KEY, ['n/a']);
            }
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
     * @return boolean
     *   The success status of the deleting.
     */
    protected function delete($file)
    {
        if (is_writeable(dirname(($file))) && file_exists($file)) {
            // Away with you!
            unlink($file);
            return true;
        } else {
            return false;
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
}
