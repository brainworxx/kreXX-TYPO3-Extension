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

namespace Brainworxx\Includekrexx\Collectors;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Collection the log file list for the frontend and the backend.
 *
 * @package Brainworxx\Includekrexx\Collectors
 */
class LogfileList extends AbstractCollector
{
    /**
     * Assigning the list to the view. Used by out adminpanel logging module.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    public function assignData(ViewInterface $view)
    {
        $view->assign('filelist', $this->retrieveFileList());
    }

    /**
     * Retrieve the file list, like the method name says. Used by the ajax controller.
     *
     * @return array
     *   The file list with the info.
     */
    public function retrieveFileList()
    {
        $fileList = array();

        if ($this->hasAccess() === false) {
            // No access.
            return $fileList;
        }

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
                // Getting the basic info.
                $fileinfo['name'] = basename($file);
                $fileinfo['size'] = $this->fileSizeConvert(filesize($file));
                $fileinfo['time'] = date("d.m.y H:i:s", filemtime($file));
                $fileinfo['id'] = str_replace('.Krexx.html', '', $fileinfo['name']);
                $fileinfo['dispatcher'] = $this->getRoute($fileinfo['id']);

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

        return $fileList;
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
        $unit = 'UNIT';
        $value = 'VALUE';

        $arBytes = array(
            0 => array(
                $unit => 'TB',
                $value => pow(1024, 4),
            ),
            1 => array(
                $unit => 'GB',
                $value => pow(1024, 3),
            ),
            2 => array(
                $unit => 'MB',
                $value => pow(1024, 2),
            ),
            3 => array(
                $unit => 'KB',
                $value => 1024,
            ),
            4 => array(
                $unit => 'B',
                $value => 1,
            ),
        );

        $result = '';
        foreach ($arBytes as $aritem) {
            if ($bytes >= $aritem[$value]) {
                $result = $bytes / $aritem[$value];
                $result = str_replace('.', ',', strval(round($result, 2))) . ' ' . $aritem[$unit];
                break;
            }
        }
        return $result;
    }
}