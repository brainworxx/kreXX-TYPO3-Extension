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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View\Output;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Output string handling for kreXX, splitting strings into small tiny chunks.
 *
 * The main problem with our "templating engine" is, we are
 * adding partials into partials, over and over again. This
 * results in a very long string, 30 MB or larger. When using
 * str_replace() on it, we can have a memory peak of 90 MB or
 * more.
 * This class splits this string into small and good-to-handle
 * chunks. We also use this class stitch back together this
 * string for output.
 *
 * @see \Brainworxx\Krexx\Service\Factory\Pool->encodingService
 *   We use '@@@' to mark a chunk key. This function escapes the @
 *   so we have no collusion with data from strings.
 *
 * @package Brainworxx\Krexx\View\Output
 */
class Chunks
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Here we store the metadata from the call.
     *
     * We save this data in a separate file, so that a backend extension can offer
     * some additional data about the logfiles and their content.
     *
     * @var array
     */
    protected $metadata = array();

    /**
     * Is the chunks folder write protected?
     *
     * When we do, kreXX will store temporary files in the chunks folder.
     * This saves a lot of memory!
     *
     * @var bool
     */
    protected $useChunks = true;

    /**
     * Is the log folder write protected?
     *
     * @var bool
     */
    protected $useLogging = true;

    /**
     * The logfolder.
     *
     * @var string
     */
    protected $logDir;

    /**
     * The folder for the output chunks.
     *
     * @var string
     */
    protected $chunkDir;

    /**
     * Microtime stamp for chunk operations.
     *
     * @var string
     */
    protected $fileStamp;

    /**
     * Here we save the encoding we are currently using.
     *
     * @var string
     */
    protected $officialEncoding = 'utf8';

    /**
     * List of encodings, where we do not change the $officialEncoding var.
     *
     * @var array
     */
    protected $doNothingEncodiung = array('ASCII', 'UTF-8', false);

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->chunkDir = $pool->config->getChunkDir();
        $this->logDir = $pool->config->getLogDir();
        $this->fileStamp = explode(' ', microtime());
        $this->fileStamp = $this->fileStamp[1] . str_replace('0.', '', $this->fileStamp[0]);
    }

    /**
     * Splits a string into small chunks.
     *
     * The chunks are saved to disk and later on.
     *
     * @param string $string
     *   The data we want to split into chunks.
     *
     * @return string
     *   The key to the chunk, wrapped up in @@@@@@.
     */
    public function chunkMe($string)
    {
        if ($this->useChunks && strlen($string) > 10000) {
            // Get the key.
            $key = $this->genKey();
            // Detect the encoding in the chunk.
            $this->detectEncoding($string);
            // Write the key to the chunks folder.
            $this->pool->fileService->putFileContents($this->chunkDir . $key . '.Krexx.tmp', $string);
            // Return the first part plus the key.
            return '@@@' . $key . '@@@';
        }

        // Return the original, because it's too small.
        return $string;
    }

    /**
     * Generates the chunk key.
     *
     * @return string
     *   The generated key.
     */
    protected function genKey()
    {
        static $counter = 0;
        ++$counter;

        return $this->fileStamp . '_' . $counter;
    }

    /**
     * Gets the original data from the string.
     *
     * Reads the data from a file in the chunks folder.
     * The output may contain other chunk keys.
     * nothing more then a wrapper for file_get_contents()
     *
     * @param string $key
     *   The key of the chunk of which we want to get the data.
     *
     * @return string
     *   The original date
     *
     */
    protected function dechunkMe($key)
    {
        $filename = $this->chunkDir . $key . '.Krexx.tmp';
        // Read the file.
        $string = $this->pool->fileService->getFileContents($filename);
        // Delete it, we don't need it anymore.
        $this->pool->fileService->deleteFile($filename);
        return $string;
    }

    /**
     * Replaces all chunk keys from a string with the original data.
     *
     * Send the output to the browser.
     *
     * @param string $string
     *   The chunk string.
     */
    public function sendDechunkedToBrowser($string)
    {
        // Do some housekeeping. Unless something dreadful had happened, there
        // should not be anything to cleanup.
        $this->cleanupOldChunks();

        $chunkPos = strpos($string, '@@@');

        while ($chunkPos !== false) {
            // We have a chunk, we send the html part.
            echo substr($string, 0, $chunkPos);
            ob_flush();
            flush();
            $chunkPart = substr($string, $chunkPos);

            // We translate the first chunk.
            $result = explode('@@@', $chunkPart, 3);
            $string = str_replace('@@@' . $result[1] . '@@@', $this->dechunkMe($result[1]), $chunkPart);
            $chunkPos = strpos($string, '@@@');
        }

        // No more chunk keys, we send what is left.
        echo $string;
        ob_flush();
        flush();
    }

    /**
     * Replaces all chunk keys from a string with the original data.
     *
     * Saves the output to a file.
     *
     * @param string $string
     *   The chunked version of the output.
     */
    public function saveDechunkedToFile($string)
    {
        $this->cleanupOldChunks();

        if (!$this->useLogging) {
            // We have no write access. Do nothing.
            return;
        }

        // Cleanup old logfiles to prevent a overflow.
        $this->cleanupOldLogs($this->logDir);

        // Determine the filename.
        $filename = $this->logDir . $this->fileStamp . '.Krexx.html';
        $chunkPos = strpos($string, '@@@');

        while ($chunkPos !== false) {
            // We have a chunk, we save the html part.
            $this->pool->fileService->putFileContents($filename, substr($string, 0, $chunkPos));

            $chunkPart = substr($string, $chunkPos);

            // We translate the first chunk.
            // Strangely, with a memory peak of 84MB, explode is
            // 2 mb cheaper than preg_match().
            $result = explode('@@@', $chunkPart, 3);
            $string = str_replace('@@@' . $result[1] . '@@@', $this->dechunkMe($result[1]), $chunkPart);
            $chunkPos = strpos($string, '@@@');
        }

        // No more chunks, we save what is left.
        $this->pool->fileService->putFileContents($filename, $string);
        // Save our metadata, so a potential backend module can display it.
        // We may or may not have already some output for this file.
        if (!empty($this->metadata)) {
            // Remove the old metadata file. We still have all it's content
            // available in $this->metadata.
            $this->pool->fileService->deleteFile($filename . '.json');
            // Create a new metadata file with new info.
            $this->pool->fileService->putFileContents($filename . '.json', json_encode($this->metadata));
        }
    }

    /**
     * Deletes chunk files older then 1 hour, in case there are some left.
     */
    protected function cleanupOldChunks()
    {
        if (!$this->useChunks) {
            // We have no write access. Do nothing.
            return;
        }

        static $beenHere = false;

        // We only do this once.
        if ($beenHere) {
            return;
        }

        $beenHere = true;
        // Clean up leftover files.
        $chunkList = glob($this->chunkDir . '*.Krexx.tmp');
        if (!empty($chunkList)) {
            $now = time();
            foreach ($chunkList as $file) {
                // We delete everything that is older than 15 minutes.
                if ((filemtime($file) + 900) < $now) {
                    $this->pool->fileService->deleteFile($file);
                }
            }
        }
    }

    /**
     * Deletes old logfiles.
     *
     * @param string $logDir
     *   The directory with the logfiles.
     */
    protected function cleanupOldLogs($logDir)
    {
        if (!$this->useLogging) {
            // We have no write access. Do nothing.
            return;
        }

        // Cleanup old logfiles to prevent a overflow.
        $logList = glob($logDir . '*.Krexx.html');
        if (empty($logList)) {
            return;
        }

        array_multisort(array_map('filemtime', $logList), SORT_DESC, $logList);
        $maxFileCount = (int)$this->pool->config->getSetting('maxfiles');
        $count = 1;
        // Cleanup logfiles.
        foreach ($logList as $file) {
            if ($count > $maxFileCount) {
                $this->pool->fileService->deleteFile($file);
                $this->pool->fileService->deleteFile($file . '.json');
            }
            ++$count;
        }
    }

    /**
     * Setter for the $useChunks.
     *
     * When the chunks folder is not writable, we will not use chunks.
     * This will increase the memory usage significantly!
     *
     * @param boolean $bool
     *   Are we using chunks?
     */
    public function setUseChunks($bool)
    {
        $this->useChunks = $bool;
    }

    public function setUseLogging($bool)
    {
        $this->useLogging = $bool;
    }

    /**
     * We add some metadata that we will store in a separate file.
     *
     * @param array $caller
     *   The caller from the caller finder.
     */
    public function addMetadata($caller)
    {
        $this->metadata[] = $caller;
    }

    /**
     * When we are done, delete all leftover chunks, just in case.
     */
    public function __destruct()
    {
        // Get a list of all chunk files from the run.
        $chunkList = glob($this->chunkDir . $this->fileStamp . '_*');
        if (empty($chunkList)) {
            return;
        }

        // Delete them all!
        foreach ($chunkList as $file) {
            $this->pool->fileService->deleteFile($file);
        }
    }

    /**
     * Simple wrapper around mb_detect_encoding.
     *
     * We also try to track the encoding we need to add to the output, so
     * people can use unicode function names.
     * We are not using it above, because there we are only handling broken
     * string encoding by completely encoding it, every char in there.
     *
     * @see \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString
     *
     * @param string $string
     *   The string we are processing.
     */
    public function detectEncoding($string)
    {
        $encoding = mb_detect_encoding($string);

        // We need to decide, if we need to change the official encoding of
        // the HTML output with a meta tag. we ignore everything in the
        // $this->doNothingEncoding array.
        if (in_array($encoding, $this->doNothingEncodiung, true) === false) {
            $this->officialEncoding = $encoding;
        }
    }

    /**
     * Getter for the official encoding.
     *
     * @return string
     */
    public function getOfficialEncoding()
    {
        return $this->officialEncoding;
    }
}
