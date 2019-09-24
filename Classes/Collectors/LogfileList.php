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

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as BeUriBuilder;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use Throwable;
use Exception;

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
        $fileList = [];

        if ($this->hasAccess === false) {
            // No access.
            return $fileList;
        }

        // Get the log files anf sort them.

        $files = glob($this->pool->config->getLogDir() . '*.Krexx.html');
        if (!is_array($files)) {
            $files = [];
        }
        // The function filemtime gets cached by php btw.
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $this->retrieveFileInfo($files);
    }

    /**
     * Get all the log file infos together.
     *
     * @param array $files
     *   The list of files to process.
     *
     * @return array
     *   The file info in a neat array.
     */
    protected function retrieveFileInfo($files)
    {
        if (version_compare(TYPO3_version, '9.0', '>=')) {
            $uriBuilder = $this->objectManager->get(UriBuilder::class);
        } else {
            $uriBuilder = $this->objectManager->get(BeUriBuilder::class);
        }

        $fileList = [];
        foreach ($files as $file) {
            $fileinfo = [];
            // Getting the basic info.
            $fileinfo['name'] = basename($file);
            $fileinfo['size'] = $this->fileSizeConvert(filesize($file));
            $fileinfo['time'] = date("d.m.y H:i:s", filemtime($file));
            $fileinfo['id'] = str_replace('.Krexx.html', '', $fileinfo['name']);
            $fileinfo['dispatcher'] = $this->getRoute($fileinfo['id'], $uriBuilder);

            // Parsing a potentially 80MB file for it's content is not a good idea.
            // That is why the kreXX lib provides some meta data. We will open
            // this file and add it's content to the template.
            if (is_readable($file . '.json')) {
                $fileinfo['meta'] = (array)json_decode(file_get_contents($file . '.json'), true);
                foreach ($fileinfo['meta'] as &$meta) {
                    $meta['filename'] = basename($meta['file']);
                    // Unescape the stuff from the json, to prevent double escaping.
                    $meta['varname'] = htmlspecialchars_decode($meta['varname']);
                }
            }

            $fileList[] = $fileinfo;
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

        $arBytes = [
            0 => [
                $unit => 'TB',
                $value => pow(1024, 4),
            ],
            1 => [
                $unit => 'GB',
                $value => pow(1024, 3),
            ],
            2 => [
                $unit => 'MB',
                $value => pow(1024, 2),
            ],
            3 => [
                $unit => 'KB',
                $value => 1024,
            ],
            4 => [
                $unit => 'B',
                $value => 1,
            ],
        ];

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

    /**
     * Depending on the TYPO3 version, we must use different classes to get a
     * functioning link to the backend dispatcher.
     *
     * @param string $id
     *   The id of the file we want to get the url from.
     * @param UriBuilder|BeUriBuilder
     *  The concrete uri builder, depending on the TYPO3 version.
     *
     * @return string
     *   The URL
     */
    protected function getRoute($id, $uriBuilder)
    {
        if ($uriBuilder instanceof UriBuilder) {
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            return (string)$uriBuilder->buildUriFromRoute(
                'tools_IncludekrexxKrexxConfiguration_dispatch',
                [
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[id]' => $id,
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[action]' => 'dispatch',
                    'tx_includekrexx_tools_includekrexxkrexxconfiguration[controller]' => 'Index'
                ]
            );
        } else {
            /** @var BeUriBuilder $uriBuilder */
            return $uriBuilder
                ->reset()
                ->setArguments(['M' => static::PLUGIN_NAME])
                ->uriFor(
                    'dispatch',
                    ['id' => $id],
                    'Index',
                    Bootstrap::EXT_KEY,
                    static::PLUGIN_NAME
                );
        }
    }
}
