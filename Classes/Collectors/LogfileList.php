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
        parent::__construct();

        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->uriBuilder = $objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
    }

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
     *   THe file list with the info.
     */
    public function retrieveFileList()
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

        $fileList = array();

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