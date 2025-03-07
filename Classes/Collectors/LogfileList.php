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

namespace Brainworxx\Includekrexx\Collectors;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Krexx;
use TYPO3\CMS\Backend\Routing\UriBuilder as BeUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collection the log file list for the frontend and the backend.
 */
class LogfileList extends AbstractCollector implements BacktraceConstInterface
{
    /**
     * Assigning the list to the view. Used by out adminpanel logging module.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @param \TYPO3\CMS\Fluid\View\AbstractTemplateView|\TYPO3\CMS\Backend\Template\ModuleTemplate $view
     */
    public function assignData($view): void
    {
        $view->assign('filelist', $this->retrieveFileList());
    }

    /**
     * Retrieve the file list, like the method name says. Used by the ajax controller.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @return string[][]
     *   The file list with the info.
     */
    public function retrieveFileList(): array
    {
        if (!$this->hasAccess) {
            // No access.
            return [];
        }

        // Get the log files and sort them.
        $files = glob($this->pool->config->getLogDir() . '*.Krexx.html');
        if (empty($files)) {
            return [];
        }

        set_error_handler(Krexx::$pool->retrieveErrorCallback());
        // The function filemtime gets cached by php btw.
        usort(
            $files,
            function ($fileA, $fileB): int {
                return (int)filemtime($fileB) - (int)filemtime($fileA);
            }
        );
        restore_error_handler();

        return $this->retrieveFileInfo($files);
    }

    /**
     * Get all the log file infos together.
     *
     * @param string[] $files
     *   The list of files to process.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @return string[][]
     *   The file info in a neat array.
     */
    protected function retrieveFileInfo(array $files): array
    {
        // These files may not be available anymore on high traffic sites. The
        // filemtime() will throw a warning if the file does not exist anymore.
        set_error_handler(\Krexx::$pool->retrieveErrorCallback());
        $fileList = [];
        foreach ($files as $file) {
            // Getting the basic info.
            $fileinfo = [
                'name' => basename($file),
                'size' => $this->fileSizeConvert((int)filesize($file)),
                'time' => date("d.m.y H:i:s", (int)filemtime($file)),
                'meta' => $this->addMetaToFileInfo($file)
            ];

            $fileinfo['id'] = str_replace('.Krexx.html', '', $fileinfo['name']);
            $fileinfo['dispatcher'] = $this->getRoute($fileinfo['id']);
            $fileList[] = $fileinfo;
        }
        restore_error_handler();

        return $fileList;
    }

    /**
     * Parsing a potentially 80 MB file for it's content is not a good idea. That
     * is why the kreXX lib provides some metadata. We will open this file and
     * add it's content to the template.
     *
     * @param string $file
     *   The file name for which we are retrieving the metadata.
     *
     * @return string[]
     *   The meta stuff we were able to retrieve.
     */
    protected function addMetaToFileInfo(string $file): array
    {
        set_error_handler(\Krexx::$pool->retrieveErrorCallback());
        if (
            !is_readable($file . '.json')
            || empty($meta = file_get_contents($file . '.json'))
        ) {
            restore_error_handler();
            return [];
        }
        restore_error_handler();

        $metaArray = (array)json_decode($meta, true);
        if (empty($metaArray)) {
            return [];
        }

        foreach ($metaArray as &$meta) {
            $meta[static::PATHINFO_FILENAME] = $meta[static::TRACE_FILE] === 'n/a' ?
                $meta[static::TRACE_FILE] : basename($meta[static::TRACE_FILE]);
            // Unescape the stuff from the json, to prevent double escaping.
            $meta[static::TRACE_VARNAME] = htmlspecialchars_decode($meta[static::TRACE_VARNAME]);
        }

        return $metaArray;
    }

    /**
     * Converts bytes into human-readable file size.
     *
     * @author Mogilev Arseny
     *
     * @param false|int $bytes
     *   The bytes value we want to make readable.
     *
     * @return string
     *   Human-readable file size.
     */
    protected function fileSizeConvert($bytes): string
    {
        $bytes = floatval($bytes);
        $unit = 'UNIT';
        $value = 'VALUE';

        $arBytes = [
            [$unit => 'TB', $value => pow(1024, 4)],
            [$unit => 'GB', $value => pow(1024, 3)],
            [$unit => 'MB', $value => pow(1024, 2)],
            [$unit => 'KB', $value => 1024],
            [$unit => 'B', $value => 1],
        ];

        foreach ($arBytes as $aritem) {
            if ($bytes >= $aritem[$value]) {
                return str_replace('.', ',', strval(round($bytes / $aritem[$value], 2))) . ' ' . $aritem[$unit];
            }
        }

        return '';
    }

    /**
     * get the backend ajax routing.
     *
     * @param string $fileId
     *   The id of the file we want to get the url from.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     *
     * @return string
     *   The URL
     */
    protected function getRoute(string $fileId): string
    {
        return (string) GeneralUtility::makeInstance(BeUriBuilder::class)->buildUriFromRoute(
            'tools_IncludekrexxKrexxConfiguration_dispatch',
            [
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[id]' => $fileId,
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[action]' => 'dispatch',
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[controller]' => 'Index'
            ]
        );
    }
}
