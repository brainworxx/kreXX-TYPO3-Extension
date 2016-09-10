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

namespace Brainworxx\Krexx\Service\View;

/**
 * Help texts.
 *
 * @package Brainworxx\Krexx\Service\View
 */
class Help
{

    // A simple array to hold the values.
    // There should not be any string collisions.
    protected $helpArray = array(
        'localFunction' => 'Here you can enter your own alias function for \krexx::open().<br/> Example: When you enter &apos;gue&apos;, the function will be \krexx::gue($myObject); [or krexx($myObject, &apos;gue&apos;);],<br/> which only devs can use who have set the same value.This is useful, to prevent other devs from calling your debug functions.',
        'analyseProtected' => 'Shall kreXX try to analyse the protected properties of a class?<br/> This may result in a lot of output.',
        'analysePrivate' => 'Shall kreXX try to analyse the private properties of a class?<br/> This may result in a lot of output.',
        'analyseTraversable' => 'Shall kreXX try to analyse possible traversable data?<br/> Depending on the underlying framework this info might be covered by the debug callback functions.',
        'debugMethods' => 'Comma-separated list of used debug callback functions. A lot of frameworks offer these, toArray and toString being the most common.<br/> kreXX will try to call them, if they are available and display their provided data.<br/> You can not change them on the frontend. If you want other settings here, you have to edit the kreXX configuration file.',
        'level' => 'Some frameworks have objects inside of objects inside of objects, and so on.<br/> Normally kreXX does not run in circles, but going to deep inside of an object tree can result in a lot of output.',
        'resetbutton' => 'Here you can reset your local settings, which are stored in a cookie.<br/> kreXX will then use the global settings (either ini-file or factory settings).',
        'destination' => 'kreXX can save it&apos;s output to a file, instead of outputting it to the frontend.<br/> The output will then be stored in the log folder.<br/>You can not change this on the frontend. If you want another destination, you have to edit the kreXX configuration file.',
        'maxCall' => 'A lot of output does not only slow down your server, it also slows down your browser. When using kreXX in a loop,<br/> it will create output every time the loop is executed. To limit this, you can configure the maximum call settings.',
        'disabled' => 'Here you can disable kreXX. Note that this is just a local setting, it does not affect other browsers.',
        'maxfiles' => 'How many logfiles do you want to store inside your logging folder?<br/> When there are more files than this number, the older files will get deleted.',
        'skin' => 'Choose a skin here. We have provided kreXX with two skins: smokygrey and hans.',
        'currentSettings' => 'kreXX&apos;s configuration can be edited here, changes will be stored in a cookie and overwrite the ini and factory settings.<br/> <strong>Please note, that these are only local settings. They only affect this browser.</strong>',
        'registerAutomatically' => 'This option registers the fatal errorhandler as soon as kreXX is included. When a fatal error occures,<br/> kreXX will offer a backtrace and an analysis of the all objects in it. PHP always clears the stack in case of a fatal error,<br/> so kreXX has to keep track of it. <strong>Be warned:</strong> This option will dramatically slow down your requests. Use this only when you have to.<br/> It is by far better to register the errorhandler yourself with <strong>\krexx::registerFatal();</strong> and later unregister it<br/> with <strong>\krexx::unregisterFatal();</strong> tp prevent a slowdown.',
        'detectAjax' => 'kreXX tries to detect whether a request is made via ajax. When it is detected, it will do no output at all. The AJAX detection can be disabled here.',
        'analyseMethodsAtall' => 'Here you can toggle if kreXX shall analyse the methods of a class.',
        'analyseProtectedMethods' => 'Here you can toggle if kreXX shall analyse the protected methods of a class. Of cause, they will only be analysed if kreXX is analysing class methods at all.',
        'analysePrivateMethods' => 'Here you can toggle if kreXX shall analyse the private methods of a class. Of cause, they will only be analysed if kreXX is analysing class methods at all.',
        '_getProperties' => 'TYPO3 debug function.<br />It takes the properties directly from the model, ignoring the getter function.<br />If the getter function is used to compute this value, the values from this function may be inaccurate.',
        'php7' => "It looks like you are using PHP7.\r\nFatal errors got removed in PHP7, meaning that they are now catchable like normal errors.",
        'php7yellow' => 'The fatal error handler does not work with PHP7!',
        'analyseConstants' => 'Here you can toggle, if kreXX shall analyse all constants of a class.',
        'maximumLevelReached' => "Maximum for analysis reached. I will not go any further.\n To increase this value, change the runtime => level setting.",
        'stringTooLarge' => "This is a very large string with a none-standard encoding.\n\n For security reasons, we must escape it, but it is too large for this. Sorry.",
        'configErrorMethods' => 'Wrong configuration for: "methods => analyseMethodsAtall"! Expected boolean. The configured setting was not applied!',
        'configErrorMethodsProtected' => 'Wrong configuration for: "methods => analyseProtectedMethods"! Expected boolean. The configured setting was not applied!',
        'configErrorMethodsPrivate' => 'Wrong configuration for: "methods => analysePrivateMethods"! Expected boolean. The configured setting was not applied!',
        'configErrorPropertiesProtected' => 'Wrong configuration for: "properties => analyseProtected"! Expected boolean. The configured setting was not applied!',
        'configErrorPropertiesPrivate' => 'Wrong configuration for: "properties => analysePrivate"! Expected boolean. The configured setting was not applied!',
        'configErrorPropertiesConstants' => 'Wrong configuration for: "properties => analyseConstants"! Expected boolean. The configured setting was not applied!',
        'configErrorTraversable' => 'Wrong configuration for: "properties => analyseTraversable"! Expected boolean. The configured setting was not applied!',
        'configErrorLevel' => 'Wrong configuration for: "runtime => level"! Expected integer. The configured setting was not applied!',
        'configErrorMaxCall' => 'Wrong configuration for: "runtime => maxCall"! Expected integer. The configured setting was not applied!',
        'configErrorDisabled' => 'Wrong configuration for: "runtime => disabled"! Expected boolean. The configured setting was not applied!',
        'configErrorDetectAjax' => 'Wrong configuration for: "runtime => detectAjax"! Expected boolean. The configured setting was not applied!',
        'configErrorDestination' => 'Wrong configuration for: "output => destination"! Expected "frontend" or "file". The configured setting was not applied!',
        'configErrorMaxfiles' => 'Wrong configuration for: "output => maxfiles"! Expected integer. The configured setting was not applied!',
        'configErrorFolderWritable' => 'Wrong configuration for: "output => folder"! Directory is not writable. The configured setting was not applied!',
        'configErrorFolderProtection' => 'Wrong configuration for: "output => folder"! Directory is not protected. The configured setting was not applied!',
        'configErrorSkin' => 'Wrong configuration for: "output => skin"! Skin not found. The configured setting was not applied!',
        'configErrorTraceFatals' => 'Wrong configuration for: "errorHandling => traceFatals"! Expected boolean. The configured setting was not applied!',
        'configErrorTraceWarnings' => 'Wrong configuration for: "errorHandling => traceWarnings"! Expected boolean. The configured setting was not applied!',
        'configErrorTraceNotices' => 'Wrong configuration for: "errorHandling => traceNotices"! Expected boolean. The configured setting was not applied!',
        'configErrorRegisterAuto' => 'Wrong configuration for: "backtraceAndError => registerAutomatically"! Expected boolean. The configured setting was not applied!',
        'configErrorPhp7' => 'Wrong configuration for: "backtraceAndError => registerAutomatically"! Fatal errors got removed in PHP 7. The handler will not work here!',
        'maxCallReached' => 'Maximum call-level reached. This is the last analysis for this request. To increase this value, please edit:<br />runtime => maxCall.',
        'noSourceAvailable' => 'No sourcecode available. Maybe this was an internal callback (call_user_func for example)?',
        'configErrorHandle' => 'You have entered a wrong developer handle. Please unse only letters between a-z and A-Z.',
        'configErrorLocal' => 'Could not read the Local Cookie configuration. Sorry :-/',
        'configErrorIpList' => 'Wrong configuration for: "output => iprange"! An empty IP list means that noone will be able to use kreXX. The configured setting was not applied!',
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
    public function getHelp($what)
    {
        $result = '';
        if (isset($this->helpArray[$what])) {
            $result = $this->helpArray[$what];
        }
        return $result;
    }
}
