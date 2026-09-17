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
     * "Assigning" the list to the view.
     * Well, not really. it is not called at all. But we need to implement this
     * method, because the interface requires it.
     *
     * @codeCoverageIgnore
     *   Not much to test here.
     *
     * @param \TYPO3\CMS\Backend\Template\ModuleTemplate $view
     */
    public function assignData($view): void
    {
        // Do nothing.
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
        $files = glob(pattern: $this->pool->config->getLogDir() . '*.Krexx.html');
        if (empty($files)) {
            return [];
        }

        set_error_handler(callback: Krexx::$pool->retrieveErrorCallback());
        // The function filemtime gets cached by php btw.
        usort(
            array: $files,
            callback: function ($fileA, $fileB): int {
                return (int)filemtime($fileB) - (int)filemtime($fileA);
            }
        );
        restore_error_handler();

        return $this->retrieveFileInfo(files: $files);
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
        // These files may not be available any more on high traffic sites. The
        // filemtime() will throw a warning if the file does not exist any more.
        set_error_handler(\Krexx::$pool->retrieveErrorCallback());
        $fileList = [];
        foreach ($files as $file) {
            // Getting the basic info.
            $fileinfo = [
                'name' => basename(path: $file),
                'size' => $this->fileSizeConvert(bytes: (int)filesize(filename: $file)),
                'time' => date("d.m.y H:i:s", (int)filemtime(filename: $file)),
                'meta' => $this->addMetaToFileInfo(file: $file)
            ];

            $fileinfo['id'] = str_replace(search: '.Krexx.html', replace: '', subject: $fileinfo['name']);
            $fileinfo['dispatcher'] = $this->getRoute(fileId: $fileinfo['id']);
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
            !is_readable(filename: $file . '.json')
            || empty($meta = file_get_contents(filename: $file . '.json'))
        ) {
            restore_error_handler();
            return [];
        }
        restore_error_handler();

        $metaArray = (array)json_decode(json: $meta, associative: true);
        if (empty($metaArray)) {
            return [];
        }

        foreach ($metaArray as &$meta) {
            $meta[static::PATHINFO_FILENAME] = $meta[static::TRACE_FILE] === 'n/a' ?
                $meta[static::TRACE_FILE] : basename(path: $meta[static::TRACE_FILE]);
            // Unescape the stuff from the json, to prevent double escaping.
            $meta[static::TRACE_VARNAME] = htmlspecialchars_decode(string: $meta[static::TRACE_VARNAME]);
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
    protected function fileSizeConvert(false|int $bytes): string
    {
        $bytes = (float)$bytes;
        $unit = 'UNIT';
        $value = 'VALUE';

        $arBytes = [
            [$unit => 'TB', $value => pow(num: 1024, exponent: 4)],
            [$unit => 'GB', $value => pow(num: 1024, exponent: 3)],
            [$unit => 'MB', $value => pow(num: 1024, exponent: 2)],
            [$unit => 'KB', $value => 1024],
            [$unit => 'B', $value => 1],
        ];

        foreach ($arBytes as $aritem) {
            if ($bytes >= $aritem[$value]) {
                return str_replace(
                    search: '.',
                    replace: ',',
                    subject: (string)round(num: $bytes / $aritem[$value], precision:  2)
                ) . ' ' . $aritem[$unit];
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
            name: 'tools_IncludekrexxKrexxConfiguration_dispatch',
            parameters: [
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[id]' => $fileId,
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[action]' => 'dispatch',
                'tx_includekrexx_tools_includekrexxkrexxconfiguration[controller]' => 'Index'
            ]
        );
    }
}
