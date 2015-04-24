<?php
/**
 * @file
 * Messaging system for kreXX
 * kreXX: Krumo eXXtended
 *
 * This is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @license http://opensource.org/licenses/LGPL-2.1 GNU Lesser General Public License Version 2.1
 * @package Krexx
 */

namespace Krexx;


/**
 * This class hosts functions, which offer additional services.
 *
 * @package Krexx
 */
class Messages {

  /**
   * Here we store all messages, which gets send to the output.
   *
   * @var array
   */
  protected static $messages = array();

  /**
   * The message we want to add. It will be displayed in the output.
   *
   * @param string $message
   *   The message itself.
   * @param string $class
   *   The class of the message.
   */
  public static function addMessage($message, $class = 'normal') {
    self::$messages[$message] = array('message' => $message, 'class' => $class);
  }

  /**
   * Renders the output of the messages.
   *
   * @return string
   *   The rendered html output of the messages.
   */
  public static function outputMessages() {
    // Simple Wrapper for Render::renderMessages
    if (php_sapi_name() == "cli") {
      if (count(self::$messages)) {
        $result = "\n\nkreXX messages\n";
        $result .= "==============\n";
        foreach (self::$messages as $message) {
          $message = $message['message'];
          $result .= "$message\n";
        }
        $result .= "\n\n";
        return $result;
      }
    }
    else {
      return Render::renderMessages(self::$messages);
    }
  }
}
