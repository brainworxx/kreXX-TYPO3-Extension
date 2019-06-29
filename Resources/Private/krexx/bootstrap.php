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

call_user_func(function () {
    // "Autoloader" for kreXX.
    // There may or may not be an active autoloader, which may or may not be able to
    // autoload the krexx files. There may or may not be an unwanted interaction
    // with the rest of the system when registering another autoloader. There is
    // also the possibility, that the existing autoloader throws an error, warning
    // or notice. And if it throws a fatal, there are bigger problems at work here.
    // There is also the possibility that the working directory was globally
    // changed, hence we need to add the 'KREXX_DIR' in front of every include, if
    // the existing autoloader can not load kreXX.
    // Meh, this file looks like sh*t.

    if (defined('KREXX_DIR') === true) {
        // Been here, done that.
        return;
    }

    define('KREXX_DIR', __DIR__ . DIRECTORY_SEPARATOR);

    // Defining our "autoloader". We may, or may not need this one.
    $krexxLoader = function () {
        include_once KREXX_DIR . 'src/Analyse/ConstInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/AbstractCallback.php';

        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/AbstractObjectAnalysis.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Constants.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/DebugMethods.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Getter.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Methods.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/PrivateProperties.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/ProtectedProperties.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/PublicProperties.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Traversable.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/ErrorObject.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Meta.php';

        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/BacktraceStep.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/ConfigSection.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Debug.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects.php';

        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughArray.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughConfig.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughConstants.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughGetter.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughLargeArray.php';
        // deprecated
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMethodAnalysis.php';
        // deprecated
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMethods.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughProperties.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughResource.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMeta.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMetaReflections.php';

        include_once KREXX_DIR . 'src/Analyse/Caller/AbstractCaller.php';
        include_once KREXX_DIR . 'src/Analyse/Caller/CallerFinder.php';

        include_once KREXX_DIR . 'src/Analyse/Code/Codegen.php';
        include_once KREXX_DIR . 'src/Analyse/Code/Connectors.php';
        include_once KREXX_DIR . 'src/Analyse/Code/Scope.php';

        include_once KREXX_DIR . 'src/Analyse/Comment/AbstractComment.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Functions.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Methods.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Properties.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Classes.php';

        include_once KREXX_DIR . 'src/Analyse/Routing/AbstractRouting.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Routing.php';

        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessInterface.php';
        // deprecated
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/AbstractProcess.php';
        // deprecated
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessArray.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessBacktrace.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessBoolean.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessClosure.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessFloat.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessInteger.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessNull.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessObject.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessResource.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessString.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessOther.php';

        include_once KREXX_DIR . 'src/Analyse/AbstractModel.php';
        include_once KREXX_DIR . 'src/Analyse/Model.php';

        include_once KREXX_DIR . 'src/Controller/AbstractController.php';
        include_once KREXX_DIR . 'src/Controller/BacktraceController.php';
        include_once KREXX_DIR . 'src/Controller/DumpController.php';
        include_once KREXX_DIR . 'src/Controller/TimerController.php';
        include_once KREXX_DIR . 'src/Controller/EditSettingsController.php';
        include_once KREXX_DIR . 'src/Controller/ErrorController.php';
        include_once KREXX_DIR . 'src/Controller/ExceptionController.php';

        include_once KREXX_DIR . 'src/Errorhandler/AbstractError.php';
        include_once KREXX_DIR . 'src/Errorhandler/Fatal.php';

        include_once KREXX_DIR . 'src/Service/Config/Fallback.php';
        include_once KREXX_DIR . 'src/Service/Config/Config.php';
        include_once KREXX_DIR . 'src/Service/Config/Model.php';
        include_once KREXX_DIR . 'src/Service/Config/Validation.php';
        // deprecated
        include_once KREXX_DIR . 'src/Service/Config/Security.php';
        // deprecated

        include_once KREXX_DIR . 'src/Service/Config/From/Cookie.php';
        include_once KREXX_DIR . 'src/Service/Config/From/Ini.php';

        include_once KREXX_DIR . 'src/Service/Factory/EventHandlerInterface.php';
        include_once KREXX_DIR . 'src/Service/Factory/Event.php';
        include_once KREXX_DIR . 'src/Service/Factory/AbstractFactory.php';
        include_once KREXX_DIR . 'src/Service/Factory/Pool.php';

        include_once KREXX_DIR . 'src/Service/Flow/Emergency.php';
        include_once KREXX_DIR . 'src/Service/Flow/Recursion.php';

        include_once KREXX_DIR . 'src/Service/Misc/Encoding.php';
        include_once KREXX_DIR . 'src/Service/Misc/File.php';
        include_once KREXX_DIR . 'src/Service/Misc/Registry.php';
        include_once KREXX_DIR . 'src/Service/Misc/FileinfoDummy.php';

        include_once KREXX_DIR . 'src/Service/Reflection/UndeclaredProperty.php';
        include_once KREXX_DIR . 'src/Service/Reflection/ReflectionClass.php';

        include_once KREXX_DIR . 'src/Service/Plugin/Registration.php';
        include_once KREXX_DIR . 'src/Service/Plugin/SettingsGetter.php';
        include_once KREXX_DIR . 'src/Service/Plugin/PluginConfigInterface.php';

        include_once KREXX_DIR . 'src/View/Output/AbstractOutput.php';
        include_once KREXX_DIR . 'src/View/Output/Chunks.php';
        include_once KREXX_DIR . 'src/View/Output/File.php';
        include_once KREXX_DIR . 'src/View/Output/Browser.php';

        include_once KREXX_DIR . 'src/View/RenderInterface.php';
        include_once KREXX_DIR . 'src/View/AbstractRender.php';
        include_once KREXX_DIR . 'src/View/Messages.php';
        include_once KREXX_DIR . 'src/View/Render.php';

        include_once KREXX_DIR . 'src/View/Skins/RenderHans.php';
        include_once KREXX_DIR . 'src/View/Skins/RenderSmokyGrey.php';

        include_once KREXX_DIR . 'src/Krexx.php';
    };

    // Try to use the original autoloader that may autoload kreXX.
    // When it does something stupid, krexxLoader will handle the rest.
    set_error_handler($krexxLoader);
    try {
        if (interface_exists(\Brainworxx\Krexx\Analyse\ConstInterface::class) === false) {
            $krexxLoader();
        }
    } catch (\Throwable $e) {
        // Meh. The autoloader did throw an error.
        $krexxLoader();
    } catch (\Exception $e) {
        // Meh. The autoloader did throw an error.
        $krexxLoader();
    }
    restore_error_handler();

    // Class alias shorthand for object analysis.
    class_alias('Brainworxx\\Krexx\\Krexx', 'Krexx');

    /**
     * Alias shorthand function for object analysis.
     *
     * Register an alias function for object analysis,
     * so you will not have to type \Krexx::open($data);
     * all the time.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $handle
     *   The developer handle.
     *
     * @return mixed
     *   Return the original anslysis value.
     */
    function krexx($data = null, $handle = '')
    {
        if (empty($handle)) {
            \Brainworxx\Krexx\Krexx::open($data);
            return $data;
        }

        $allArgs = func_get_args();
        if (count($allArgs) > 2) {
            // We got more arguments than we asked for.
            // Better dum them all.
            \Brainworxx\Krexx\Krexx::open($allArgs);
            return $data;
        }

        if (is_string($handle)) {
            \Brainworxx\Krexx\Krexx::$handle($data);
            return $data;
        }

        // Still here ?!?
        \Brainworxx\Krexx\Krexx::open([$data, $handle]);
        return $data;
    }
});
