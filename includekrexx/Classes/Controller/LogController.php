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


// The mainproblem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put this thing here, hoping not to break anything!
if (!class_exists('Tx_Extbase_MVC_Controller_ActionController')) {
  class Tx_Extbase_MVC_Controller_ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
  }
}
if (!class_exists('Tx_Extbase_MVC_Controller_Arguments')) {
  class Tx_Extbase_MVC_Controller_Arguments extends \TYPO3\CMS\Extbase\Mvc\Controller\Arguments {
  }
}
if (!class_exists('t3lib_FlashMessage')) {
  class t3lib_FlashMessage extends \TYPO3\CMS\Core\Messaging\FlashMessage {
  }
}

if (!class_exists('Tx_Includekrexx_Controller_LogController')) {
  class Tx_Includekrexx_Controller_LogController extends Tx_Extbase_MVC_Controller_ActionController {

    /**
     * Injects the arguments
     *
     * @param Tx_Extbase_MVC_Controller_Arguments $arguments
     *   The arguments from the call to the controller.
     */
    public function injectArguments(Tx_Extbase_MVC_Controller_Arguments $arguments) {
      $this->arguments = $arguments;
    }


    /**
     * Wrapper for the \TYPO3\CMS\Extbase\Utility\LocalizationUtility
     *
     * @param string $key
     *   The key we want to translate
     * @param null|array $args
     *   The strings from the controller we want to place inside the
     *   translation.
     *
     * @return string
     *   The translation itself.
     */
    protected function LLL($key, $args = NULL) {

      if ((int) TYPO3_version > 6) {
        // 7+ version.
        $result = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'includekrexx', $args);
      }
      else {
        // Version 4.5 until 6.2
        $result = Tx_Extbase_Utility_Localization::translate($key, 'includekrexx', $args);
      }

      return $result;
    }

    /**
     * Gets all messages from kreXX and translates them.
     *
     * @return string
     *   The translated messages.
     */
    protected function getTranslatedMessages() {
      $result = '';
      // Get the keys and the args.
      $keys = Messages::getKeys();

      foreach ($keys as $message) {
        // And translate them and add a linebreak.
        $result .= $this->LLL($message['key'], $message['params']) . '<br />';
      }

      return $result;
    }

    /**
     * Lists all kreXX logfiles.
     */
    public function listAction() {
      // 1. Get the log folder
      $dir = Config::$krexxdir . Config::getConfigValue('logging', 'folder') . DIRECTORY_SEPARATOR;

      // 2. Get the file list and sort it.
      $files = glob($dir . '*.Krexx.html');
      // The function filemtime gets cached by php btw.
      usort($files, function($a,$b) {return filemtime($b) - filemtime($a);});

      // 3. Get the file info
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
      $this->addMessage($this->getTranslatedMessages(), $this->LLL('general.error.title'), t3lib_FlashMessage::ERROR);

      // 5. Assign the flile list.
      $this->view->assign('files', $file_list);
    }

    /**
     * Gets the content of a logfile
     */
    public function getContentAction() {
      // No directory traversal for you!
      $id = (int)$this->request->getArgument('id');
      // 1. Get the filepath.
      $file = Config::$krexxdir . Config::getConfigValue('logging', 'folder') . DIRECTORY_SEPARATOR . $id . '.Krexx.html';
      if (is_readable($file)) {
        // We open and then send the file.
        header('content-type text/html charset=utf-8');
        header('Content-Disposition: inline; filename="' . $id . '.Krexx.html"');
        $this->dispatchFile($file);
        die();
      }
      else {
        // Error message and redirect to the list action
        $this->addMessage($this->LLL('log.notreadable', array($id . '.Krexx.html')), $this->LLL('log.fileerror'), t3lib_FlashMessage::ERROR);
        $this->redirect('list');
      }

    }

    /**
     * Converts bytes into human readable file size.
     *
     * @param string $bytes
     * @return string human readable file size (2,87 Мб)
     * @author Mogilev Arseny
     */
    protected function fileSizeConvert($bytes) {
      $bytes = floatval($bytes);
      $arBytes = array(
        0 => array(
          "UNIT" => "TB",
          "VALUE" => pow(1024, 4)
        ),
        1 => array(
          "UNIT" => "GB",
          "VALUE" => pow(1024, 3)
        ),
        2 => array(
          "UNIT" => "MB",
          "VALUE" => pow(1024, 2)
        ),
        3 => array(
          "UNIT" => "KB",
          "VALUE" => 1024
        ),
        4 => array(
          "UNIT" => "B",
          "VALUE" => 1
        ),
      );

      $result = '';
      foreach ($arBytes as $arItem) {
        if ($bytes >= $arItem["VALUE"]) {
          $result = $bytes / $arItem["VALUE"];
          $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
          break;
        }
      }
      return $result;
    }

    /**
     * Wrapper for the FlashMessage, which was changed in 7.0.
     *
     * @param string $text
     * @param string $title
     * @param integer $severity
     */
    protected function addMessage($text, $title, $severity) {
      if (!isset($this->flashMessageContainer)) {
        $this->addFlashMessage($text, $title, $severity);
      }
      else {
        $this->flashMessageContainer->add($text, $title, $severity);
      }
    }

    /**
     * Dispatches a file, using output buffering.
     *
     * @param string $path
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