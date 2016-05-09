<?php
/**
 * @file
 *   Log controller for the kreXX typo3 extension
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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


use \Brainworxx\Krexx\Framework\Config;
use \Brainworxx\Krexx\View\Messages;


if (!class_exists('Tx_Includekrexx_Controller_LogController')) {
  /**
   * Class Tx_Includekrexx_Controller_LogController
   */
  class Tx_Includekrexx_Controller_LogController extends Tx_Includekrexx_Controller_CompatibilityController {

    /**
     * Lists all kreXX logfiles.
     */
    public function listAction() {
      // 1. Get the log folder.
      $dir = Config::$krexxdir . Config::getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR;

      // 2. Get the file list and sort it.
      $files = glob($dir . '*.Krexx.html');
      // The function filemtime gets cached by php btw.
      usort($files, function($a,$b) {return filemtime($b) - filemtime($a);});

      // 3. Get the file info.
      $file_list = array();
      foreach ($files as $file) {
        $file_info = array();
        $file_info['name'] = basename($file);
        $file_info['size'] = $this->fileSizeConvert(filesize($file));
        $file_info['time'] = date("d.m.y H:i:s", filemtime($file));
        $file_info['id'] = str_replace('.Krexx.html', '', $file_info['name']);

        $file_list[] = $file_info;
      }

      // 4. Has kreXX something to say? Maybe a writeprotected logfolder?
      foreach ($this->getTranslatedMessages() as $message) {
        $this->addMessage($message, $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);
      }

      // 5. Assign the flile list.
      $this->view->assign('files', $file_list);
    }

    /**
     * Gets the content of a logfile
     */
    public function getContentAction() {
      // No directory traversal for you!
      $id = preg_replace('/[^0-9]/', '', $this->request->getArgument('id'));
      // Get the filepath.
      $file = Config::$krexxdir . Config::getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR . $id . '.Krexx.html';
      if (is_readable($file)) {
        // We open and then send the file.
        $this->dispatchFile($file);
        die();
      }
      else {
        // Error message and redirect to the list action.
        $this->addMessage($this->LLL('log.notreadable', array($id . '.Krexx.html')), $this->LLL('log.fileerror'), t3lib_FlashMessage::ERROR);
        $this->redirect('list');
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
    protected function fileSizeConvert($bytes) {
      $bytes = floatval($bytes);
      $ar_bytes = array(
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
      foreach ($ar_bytes as $ar_item) {
        if ($bytes >= $ar_item["VALUE"]) {
          $result = $bytes / $ar_item["VALUE"];
          $result = str_replace(".", ",", strval(round($result, 2))) . " " . $ar_item["UNIT"];
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
    protected function dispatchFile($path) {
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

}
