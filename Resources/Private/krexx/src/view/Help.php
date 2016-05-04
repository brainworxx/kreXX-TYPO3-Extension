<?php
/**
 * @file
 *   Help texts for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
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

namespace Brainworxx\Krexx\View;

/**
 * Help texts for kreXX.
 *
 * @package Brainworxx\Krexx\View
 */
class Help {

  // A simple array to hold the values.
  // There should not be any string collisions.
  protected static $helpArray = array(
    'localFunction' => 'Here you can enter your own alias function for \krexx::open().<br/> Example: When you enter &apos;gue&apos;, the function will be \krexx::gue($myObject); [or krexx($myObject, &apos;gue&apos;);],<br/> which only devs can use who have set the same value.This is useful, to prevent other devs from calling your debug functions.',
    'analyseProtected' => 'Shall kreXX try to analyse the protected properties of a class?<br/> This may result in a lot of output.',
    'analysePrivate' => 'Shall kreXX try to analyse the private properties of a class?<br/> This may result in a lot of output.',
    'analyseTraversable' => 'Shall kreXX try to analyse possible traversable data?<br/> Depending on the underlying framework this info might be covered by the debug callback functions.',
    'debugMethods' => 'Comma-separated list of used debug callback functions. A lot of frameworks offer these, toArray and toString being the most common.<br/> kreXX will try to call them, if they are available and display their provided data.<br/> You can not change them on the frontend. If you want other settings here, you have to edit the kreXX configuration file.',
    'level' => 'Some frameworks have objects inside of objects inside of objects, and so on.<br/> Normally kreXX does not run in circles, but going to deep inside of an object tree can result in a lot of output.',
    'resetbutton' => 'Here you can reset your local settings, which are stored in a cookie.<br/> kreXX will then use the global settings (either ini-file or factory settings).' ,
    'destination' => 'kreXX can save it&apos;s output to a file, instead of outputting it to the frontend.<br/> The output will then be stored in the log folder.',
    'maxCall' => 'A lot of output does not only slow down your server, it also slows down your browser. When using kreXX in a loop,<br/> it will create output every time the loop is executed. To limit this, you can configure the maximum call settings.',
    'disabled' => 'Here you can disable kreXX. Note that this is just a local setting, it does not affect other browsers.',
    'folder' => 'This is the folder where kreXX will store it&apos;s logfiles.',
    'maxfiles' => 'How many logfiles do you want to store inside your logging folder?<br/> When there are more files than this number, the older files will get deleted.',
    'skin' => 'Choose a skin here. We have provided kreXX with two skins: smoky-grey and hans.',
    'currentSettings' => 'kreXX&apos;s configuration can be edited here, changes will be stored in a cookie and overwrite the ini and factory settings.<br/> <strong>Please note, that these are only local settings. They only affect this browser.</strong>',
    'registerAutomatically' => 'This option registers the fatal errorhandler as soon as kreXX is included. When a fatal error occures,<br/> kreXX will offer a backtrace and an analysis of the all objects in it. PHP always clears the stack in case of a fatal error,<br/> so kreXX has to keep track of it. <strong>Be warned:</strong> This option will dramatically slow down your requests. Use this only when you have to.<br/> It is by far better to register the errorhandler yourself with <strong>\krexx::registerFatal();</strong> and later unregister it<br/> with <strong>\krexx::unregisterFatal();</strong> tp prevent a slowdown.',
    'detectAjax' => 'kreXX tries to detect whether a request is made via ajax. When it is detected, it will do no output at all. The AJAX detection can be disabled here.',
    'backtraceAnalysis' => 'Shall kreXX do a "deep" analysis of  the backtrace? Be warned, a deep analysis can produce a lot of output.<br/> A "normal" analysis will use the configured settings, while a "deep" analysis will get as much data from the objects as possible.',
    'memoryLeft' => 'kreXX checks regularly how much memory is left. Here you can adjust the amount where it will trigger an emergency break.<br />Unit of measurement is MB.',
    'maxRuntime' => 'kreXX checks during the analysis how much time has elapsed since start. Here you can adjust the amount where it will trigger an emergency break.<br />Unit of measurement is seconds.',
    'analyseMethodsAtall' => 'Here you can toggle if kreXX shall analyse the methods of a class.',
    'analyseProtectedMethods' => 'Here you can toggle if kreXX shall analyse the protected methods of a class. Of cause, they will only be analysed if kreXX is analysing class methods at all.',
    'analysePrivateMethods' => 'Here you can toggle if kreXX shall analyse the private methods of a class. Of cause, they will only be analysed if kreXX is analysing class methods at all.',
    '_getProperties' => 'Typo3 debug function.<br />It takes the properties directly from the model, ignoring the getter function.<br />If the getter function is used to compute this value, the values from this function may be inaccurate.',
    'php7' => "It looks like you are using PHP7.\r\nFatal errors got removed in PHP7, meaning that they are now catchable like normal errors.",
    'php7yellow' => 'The fatal error handler does not work with PHP7!',
    'analyseConstants' => 'Here you can toggle, if kreXX shall analyse all constants of a class.',
  );

  /**
   * Returns the help text when found, otherwise returns an empty string.
   *
   * @param string $what
   *   The help ID from the array above.
   *
   * @return string
   *   The help text.
   */
  public static function getHelp($what) {
    $result = '';
    if (isset(self::$helpArray[$what])) {
      $result = self::$helpArray[$what];
    }
    return $result;
  }
}
