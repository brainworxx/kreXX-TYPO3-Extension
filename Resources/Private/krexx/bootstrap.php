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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
        include_once KREXX_DIR . 'src/Analyse/Callback/CallbackConstInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessConstInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Caller/BacktraceConstInterface.php';
        include_once KREXX_DIR . 'src/View/ViewConstInterface.php';
        include_once KREXX_DIR . 'src/Controller/ControllerConstInterface.php';
        include_once KREXX_DIR . 'src/Service/Config/ConfigConstInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Code/CodegenConstInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Code/ConnectorsConstInterface.php';
        include_once KREXX_DIR . 'src/Service/Plugin/PluginConstInterface.php';
        // Deprecated
        include_once KREXX_DIR . 'src/Analyse/ConstInterface.php';
        // Deprecated
        include_once KREXX_DIR . 'src/Analyse/Callback/AbstractCallback.php';

        include_once KREXX_DIR . 'src/Analyse/Code/Codegen.php';
        include_once KREXX_DIR . 'src/Analyse/Code/Connectors.php';
        include_once KREXX_DIR . 'src/Analyse/Code/Scope.php';

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
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMethods.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughProperties.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughResource.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMeta.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMetaReflections.php';

        include_once KREXX_DIR . 'src/Analyse/Caller/AbstractCaller.php';
        include_once KREXX_DIR . 'src/Analyse/Caller/CallerFinder.php';
        include_once KREXX_DIR . 'src/Analyse/Caller/ExceptionCallerFinder.php';

        include_once KREXX_DIR . 'src/Analyse/Comment/AbstractComment.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Functions.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Methods.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Properties.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/Classes.php';
        include_once KREXX_DIR . 'src/Analyse/Comment/ReturnType.php';

        include_once KREXX_DIR . 'src/Analyse/Routing/AbstractRouting.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Routing.php';

        include_once KREXX_DIR . 'src/Analyse/Routing/Process/ProcessInterface.php';
        include_once KREXX_DIR . 'src/Analyse/Routing/Process/AbstractProcessNoneScalar.php';
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

        include_once KREXX_DIR . 'src/Analyse/Scalar/AbstractScalar.php';
        include_once KREXX_DIR . 'src/Analyse/Scalar/ScalarString.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/AbstractScalarAnalysis.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/Callback.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/FilePath.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/Json.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/Xml.php';
        include_once KREXX_DIR . 'src/Analyse/Callback/Analyse/Scalar/TimeStamp.php';

        include_once KREXX_DIR . 'src/Analyse/Model/ConnectorService.php';
        include_once KREXX_DIR . 'src/Analyse/Model/Callback.php';
        include_once KREXX_DIR . 'src/Analyse/Model/Data.php';
        include_once KREXX_DIR . 'src/Analyse/Model/Name.php';
        include_once KREXX_DIR . 'src/Analyse/Model/Normal.php';
        include_once KREXX_DIR . 'src/Analyse/Model/Json.php';
        include_once KREXX_DIR . 'src/Analyse/Model/AdditionalType.php';
        include_once KREXX_DIR . 'src/Analyse/Model/DomId.php';
        include_once KREXX_DIR . 'src/Analyse/Model/HasExtra.php';
        include_once KREXX_DIR . 'src/Analyse/Model/CodeGenType.php';
        include_once KREXX_DIR . 'src/Analyse/Model/KeyType.php';
        // Deprecated
        include_once KREXX_DIR . 'src/Analyse/Model/MultiLineCodeGen.php';
        include_once KREXX_DIR . 'src/Analyse/Model/IsPublic.php';
        include_once KREXX_DIR . 'src/Analyse/Model/IsCallback.php';
        include_once KREXX_DIR . 'src/Analyse/Model/IsMetaConstants.php';
        include_once KREXX_DIR . 'src/Analyse/AbstractModel.php';
        // Deprecated
        include_once KREXX_DIR . 'src/Analyse/Model.php';

        include_once KREXX_DIR . 'src/Controller/AbstractController.php';
        include_once KREXX_DIR . 'src/Controller/BacktraceController.php';
        include_once KREXX_DIR . 'src/Controller/DumpController.php';
        include_once KREXX_DIR . 'src/Controller/TimerController.php';
        include_once KREXX_DIR . 'src/Controller/EditSettingsController.php';
        include_once KREXX_DIR . 'src/Controller/ExceptionController.php';

        include_once KREXX_DIR . 'src/Service/Config/Fallback.php';
        include_once KREXX_DIR . 'src/Service/Config/Config.php';
        include_once KREXX_DIR . 'src/Service/Config/Model.php';
        include_once KREXX_DIR . 'src/Service/Config/Validation.php';

        include_once KREXX_DIR . 'src/Service/Config/From/Cookie.php';
        include_once KREXX_DIR . 'src/Service/Config/From/File.php';
        // Deprecated
        include_once KREXX_DIR . 'src/Service/Config/From/Ini.php';
        //

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
        include_once KREXX_DIR . 'src/Service/Misc/Cleanup.php';

        include_once KREXX_DIR . 'src/Service/Reflection/UndeclaredProperty.php';
        include_once KREXX_DIR . 'src/Service/Reflection/ReflectionClass.php';

        include_once KREXX_DIR . 'src/Service/Plugin/PluginConfigInterface.php';
        include_once KREXX_DIR . 'src/Service/Plugin/Registration.php';
        include_once KREXX_DIR . 'src/Service/Plugin/SettingsGetter.php';
        include_once KREXX_DIR . 'src/Service/Plugin/NewSetting.php';

        include_once KREXX_DIR . 'src/View/Output/AbstractOutput.php';
        include_once KREXX_DIR . 'src/View/Output/Chunks.php';
        include_once KREXX_DIR . 'src/View/Output/File.php';
        include_once KREXX_DIR . 'src/View/Output/Browser.php';
        include_once KREXX_DIR . 'src/View/Output/CheckOutput.php';

        include_once KREXX_DIR . 'src/View/RenderInterface.php';
        include_once KREXX_DIR . 'src/View/AbstractRender.php';
        include_once KREXX_DIR . 'src/View/Messages.php';
        include_once KREXX_DIR . 'src/View/Message.php';

        include_once KREXX_DIR . 'src/View/Skins/Hans/SingleEditableChild.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/ExpandableChild.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/SingleChild.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/BacktraceSourceLine.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Button.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/CssJs.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/FatalHeader.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/FatalMain.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Footer.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Header.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Linebreak.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Messages.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Recursion.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/SingeChildHr.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/PluginList.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Help.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/ConnectorLeft.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/ConnectorRight.php';
        include_once KREXX_DIR . 'src/View/Skins/Hans/Search.php';
        // Deprecated
        include_once KREXX_DIR . 'src/View/Skins/Hans/ConstInterface.php';
        // Deprecated
        include_once KREXX_DIR . 'src/View/Skins/RenderHans.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/Button.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/ExpandableChild.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/FatalMain.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/Footer.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/Header.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/Recursion.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/SingleChild.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/SingleEditableChild.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/ConnectorRight.php';
        include_once KREXX_DIR . 'src/View/Skins/SmokyGrey/Help.php';
        include_once KREXX_DIR . 'src/View/Skins/RenderSmokyGrey.php';

        include_once KREXX_DIR . 'src/Logging/LoggingTrait.php';
        include_once KREXX_DIR . 'src/Logging/Model.php';
        include_once KREXX_DIR . 'src/Krexx.php';
    };

    // Try to use the original autoloader that may autoload kreXX.
    // When it does something stupid, krexxLoader will handle the rest.
    set_error_handler($krexxLoader);
    try {
        if (class_exists(\Brainworxx\Krexx\Krexx::class) === false) {
            $krexxLoader();
        }
    } catch (\Throwable $e) {
        // Meh. The autoloader did throw an error.
        $krexxLoader();
    }
    restore_error_handler();

    /**
     * Class shorthand for object analysis.
     *
     * The alias method does not work in all IDEs.
     * So, we extend the namespaced class.
     */
    class Krexx extends Brainworxx\Krexx\Krexx {}

    /**
     * Alias shorthand function for object analysis.
     *
     * Register an alias function for object analysis,
     * so you will not have to type \Krexx::open($data);
     * all the time.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     *
     * @return mixed
     *   Return the original analysis value.
     */
    function krexx($data = null)
    {
        return \Brainworxx\Krexx\Krexx::open($data);
    }

    /**
     * Alias shorthand function for object analysis logging.
     *
     * Register an alias function for object analysis,
     * so you will not have to type \Krexx::log($data);
     * all the time.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     *
     * @return mixed
     *   Return the original anslysis value.
     */
    function krexxlog($data = null)
    {
        return \Brainworxx\Krexx\Krexx::log($data);
    }
});
