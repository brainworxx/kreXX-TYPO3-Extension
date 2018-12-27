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

namespace Brainworxx\Includekrexx\Controller;

use Brainworxx\Krexx\Service\Factory\Pool;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AjaxController
{
    /**
     * The pool. And no, it's not closed.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * The uri builder. We need to append the dispatcher links.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * Inject the pool.
     */
    public function __construct()
    {
        Pool::createPool();
        $this->pool = \Krexx::$pool;
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
    }

    /**
     * List the logfiles with their corresponding meta data.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest|array $arg1
     *   Depending on the TYPO3 version, either an empty array (6.2) or the
     *   ServerRequest (7.6 and above)
     * @param \TYPO3\CMS\Core\Http\Response|\TYPO3\CMS\Core\Http\AjaxRequestHandler $response
     *   Depending on the TYPO3 version.
     *
     * @return \TYPO3\CMS\Core\Http\Response|\TYPO3\CMS\Core\Http\AjaxRequestHandler
     */
    public function refreshLoglistAction($arg1, $response)
    {
        // 1. Get the log folder.
        $dir = $this->pool->config->getLogDir();

        // 2. Get the file list and sort it.
        $files = glob($dir . '*.Krexx.html');
        if (!is_array($files)) {
            $files = array();
        }
        // The function filemtime gets cached by php btw.
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // 3. Get the file info.

        foreach ($files as $file) {
            try {
                $fileinfo = array();

                if (version_compare(TYPO3_version, '9.0', '>=')) {
                    $argumentKey = 'route';
                } else {
                    $argumentKey = 'M';
                }
                
                // Getting the basic info.
                $fileinfo['name'] = basename($file);
                $fileinfo['size'] = $this->fileSizeConvert(filesize($file));
                $fileinfo['time'] = date("d.m.y H:i:s", filemtime($file));
                $fileinfo['id'] = str_replace('.Krexx.html', '', $fileinfo['name']);
                $fileinfo['dispatcher'] = $this->uriBuilder
                    ->reset()
                    ->setArguments(array($argumentKey => 'tools_IncludekrexxKrexxConfiguration'))
                    ->uriFor(
                        'dispatch',
                        array('id' => $fileinfo['id']),
                        'Index',
                        'includekrexx',
                        'tools_IncludekrexxKrexxConfiguration'
                    );

                // Parsing a potentially 80MB file for it's content is not a good idea.
                // That is why the kreXX lib provides some meta data. We will open
                // this file and add it's content to the template.
                if (is_readable($file . '.json')) {
                    $fileinfo['meta'] = (array) json_decode(file_get_contents($file . '.json'), true);

                    foreach ($fileinfo['meta'] as &$meta) {
                        $meta['filename'] = basename($meta['file']);
                        // Unescape the stuff from the json, to prevent double escaping.
                        $meta['varname'] = htmlspecialchars_decode($meta['varname']);
                    }
                }

                $fileList[] = $fileinfo;

            } catch (\Throwable $e) {
                // We simply skip this one on error.
                continue;
            } catch (\Exception $e) {
                continue;
            }
        }

        if (is_a($response, 'TYPO3\\CMS\\Core\\Http\\Response')) {
            // 7.6 and above.
            $response->getBody()->write(json_encode($fileList));
        } else {
            // Below 7.6.
            $response->setContentFormat('jsonbody');
            $response->setContent(($fileList));
        }

        return $response;
    }

    /**
     * Deletes a logfile.
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest|array $arg1
     *   Depending on the TYPO3 version, either an empty array (6.2) or the
     *   ServerRequest (7.6 and above)
     * @param \TYPO3\CMS\Core\Http\Response|\TYPO3\CMS\Core\Http\AjaxRequestHandler $response
     *   Depending on the TYPO3 version.
     *
     * @return \TYPO3\CMS\Core\Http\Response|\TYPO3\CMS\Core\Http\AjaxRequestHandler
     */
    public function deleteAction($arg1, $response)
    {
        // No directory traversal for you!
        $id = preg_replace('/[^0-9]/', '', GeneralUtility::_GET('id'));
        // Directly add the delete result return value.
        $file = $this->pool->config->getLogDir() . $id . '.Krexx';

        $result = new \stdClass();
        if ($this->delete($file . '.html') && $this->delete($file . '.html.json')) {
            $result->class  = 'success';
            $result->text = LocalizationUtility::translate('fileDeleted', 'includekrexx', array($id));
        } else {

            $result->class  = 'error';
            $result->text = LocalizationUtility::translate('fileDeletedFail', 'includekrexx', array('n/a'));
        }

        if (is_a($response, 'TYPO3\\CMS\\Core\\Http\\Response')) {
            // 7.6 and above.
            $response->getBody()->write(json_encode($result));
        } else {
            // Below 7.6.
            // 6.2 always wants to send an array of json objects,for some reason,
            // not a single one. Hence, we send it as plain. The js does not
            // care.
            $response->setContentFormat('plain');
            $response->setContent(array(json_encode($result)));
        }

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
     * Converts bytes into human readable file size.
     *
     * @author Mogilev Arseny
     *
     * @param string $bytes
     *   The bytes value we want to make readable.
     *
     * @return string
     *   Human readable file size.
     */
    protected function fileSizeConvert($bytes)
    {
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array(
                "UNIT" => "TB",
                "VALUE" => pow(1024, 4),
            ),
            1 => array(
                "UNIT" => "GB",
                "VALUE" => pow(1024, 3),
            ),
            2 => array(
                "UNIT" => "MB",
                "VALUE" => pow(1024, 2),
            ),
            3 => array(
                "UNIT" => "KB",
                "VALUE" => 1024,
            ),
            4 => array(
                "UNIT" => "B",
                "VALUE" => 1,
            ),
        );

        $result = '';
        foreach ($arBytes as $aritem) {
            if ($bytes >= $aritem["VALUE"]) {
                $result = $bytes / $aritem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2))) . " " . $aritem["UNIT"];
                break;
            }
        }
        return $result;
    }
}
