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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Log controller for the kreXX typo3 extension
 */
class LogController extends CompatibilityController
{

    /**
     * Lists all kreXX logfiles.
     */
    public function listAction()
    {
        $this->checkProductiveSetting();

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
        $fileList = array();
        foreach ($files as $file) {
            $fileinfo = array();

            // Getting the basic info.
            $fileinfo['name'] = basename($file);
            $fileinfo['size'] = $this->fileSizeConvert(filesize($file));
            $fileinfo['time'] = date("d.m.y H:i:s", filemtime($file));
            $fileinfo['id'] = str_replace('.Krexx.html', '', $fileinfo['name']);

            // Parsing a potentially 80MB file for it's content is not a good idea.
            // That is why the kreXX lib provides some meta data. We will open
            // this file and add it's content to the template.
            if (is_readable($file . '.json')) {
                $fileinfo['meta'] = (array) json_decode(file_get_contents($file . '.json'), true);

                foreach ($fileinfo['meta'] as &$meta) {
                    $meta['filename'] = basename($meta['file']);

                    // Unescape the stuff from the json, to prevent double escaping.
                    // Meh, there is no f:format.raw in 4.5 . . .
                    $meta['varname'] = htmlspecialchars_decode($meta['varname']);
                }
            }

            $fileList[] = $fileinfo;
        }

        // 4. Has kreXX something to say? Maybe a write protected logfolder?
        foreach ($this->getTranslatedMessages() as $message) {
            $this->addFlashMessage(
                $message,
                LocalizationUtility::translate('general.error.title', static::EXT_KEY),
                FlashMessage::ERROR
            );
        }

        // 5. Assign the file list.
        $this->view->assign('files', $fileList);
        $this->assignFlashInfo();
    }

    /**
     * Gets the content of a logfile
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function getContentAction()
    {
        // No directory traversal for you!
        $id = preg_replace('/[^0-9]/', '', $this->request->getArgument('id'));
        // Get the filepath.
        $file = $this->pool->config->getLogDir() . $id . '.Krexx.html';
        if (is_readable($file)) {
            // We open and then send the file.
            $this->dispatchFile($file);
            die();
        } else {
            // Error message and redirect to the list action.
            $this->addFlashMessage(
                LocalizationUtility::translate('log.notreadable', static::EXT_KEY, array($id . '.Krexx.html')),
                LocalizationUtility::translate('log.fileerror', static::EXT_KEY),
                FlashMessage::ERROR
            );
            $this->redirect('list');
        }
    }

    /**
     * Deletes a logfile.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function deleteAction()
    {
        // No directory traversal for you!
        $id = preg_replace('/[^0-9]/', '', $this->request->getArgument('id'));
        // Get the filepath.
        $file = $this->pool->config->getLogDir() . $id . '.Krexx';

        // Delete the logfile.
        $this->delete($file . '.html');
        // Delete the meta data json.
        $this->delete($file . '.html.json');
        // Going back.
        $this->redirect('list');
    }

    /**
     * Physically deletes a file, if possible.
     *
     * @param string $file
     *   Path to the file we want to delete.
     */
    protected function delete($file)
    {
        if (is_writeable(dirname(($file))) && file_exists($file)) {
            // Away with you!
            unlink($file);
        } else {
            // Error message.
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'log.notdeletable',
                    static::EXT_KEY,
                    array($this->pool->fileService->filterFilePath($file))
                ),
                LocalizationUtility::translate('log.fileerror', static::EXT_KEY),
                FlashMessage::ERROR
            );
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

    /**
     * Dispatches a file, using output buffering.
     *
     * @param string $path
     *   The path of the file we want to dispatch to the browser.
     */
    protected function dispatchFile($path)
    {
        header('Content-Type: text/html; charset=utf-8');

        $size = 1024 * 1024;
        $res = fopen($path, "rb");
        while (!feof($res)) {
            echo fread($res, $size);
            ob_flush();
            flush();
        }
        fclose($res);
    }
}
