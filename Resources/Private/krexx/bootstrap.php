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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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


// "Autoloader" for kreXX.
// There may or may not be an active autoloader, which may or may not
// be able to autoload the krexx files. There may or may not be an
// unwanted interaction with the rest of the system when registering
// another autoloader. This leaves us with loading every single file
// via include.

if (defined('KREXX_DIR')) {
    // Been here, done that.
    return;
}

define('KREXX_DIR', __DIR__ . DIRECTORY_SEPARATOR);
include 'src/Analyse/Callback/AbstractCallback.php';

include 'src/Analyse/Callback/Analyse/Objects/AbstractObjectAnalysis.php';
include 'src/Analyse/Callback/Analyse/Objects/Constants.php';
include 'src/Analyse/Callback/Analyse/Objects/DebugMethods.php';
include 'src/Analyse/Callback/Analyse/Objects/Getter.php';
include 'src/Analyse/Callback/Analyse/Objects/Methods.php';
include 'src/Analyse/Callback/Analyse/Objects/PrivateProperties.php';
include 'src/Analyse/Callback/Analyse/Objects/ProtectedProperties.php';
include 'src/Analyse/Callback/Analyse/Objects/PublicProperties.php';
include 'src/Analyse/Callback/Analyse/Objects/Traversable.php';

include 'src/Analyse/Callback/Analyse/BacktraceStep.php';
include 'src/Analyse/Callback/Analyse/ConfigSection.php';
include 'src/Analyse/Callback/Analyse/Debug.php';
include 'src/Analyse/Callback/Analyse/Objects.php';

include 'src/Analyse/Callback/Iterate/ThroughArray.php';
include 'src/Analyse/Callback/Iterate/ThroughConfig.php';
include 'src/Analyse/Callback/Iterate/ThroughConstants.php';
include 'src/Analyse/Callback/Iterate/ThroughGetter.php';
include 'src/Analyse/Callback/Iterate/ThroughLargeArray.php';
include 'src/Analyse/Callback/Iterate/ThroughMethodAnalysis.php';
include 'src/Analyse/Callback/Iterate/ThroughMethods.php';
include 'src/Analyse/Callback/Iterate/ThroughProperties.php';

include 'src/Analyse/Caller/AbstractCaller.php';
include 'src/Analyse/Caller/CallerFinder.php';

include 'src/Analyse/Code/Codegen.php';
include 'src/Analyse/Code/Connectors.php';
include 'src/Analyse/Code/Scope.php';

include 'src/Analyse/Comment/AbstractComment.php';
include 'src/Analyse/Comment/Functions.php';
include 'src/Analyse/Comment/Methods.php';
include 'src/Analyse/Comment/Properties.php';

include 'src/Analyse/Routing/AbstractRouting.php';
include 'src/Analyse/Routing/Routing.php';

include 'src/Analyse/Routing/Process/AbstractProcess.php';
include 'src/Analyse/Routing/Process/ProcessArray.php';
include 'src/Analyse/Routing/Process/ProcessBacktrace.php';
include 'src/Analyse/Routing/Process/ProcessBoolean.php';
include 'src/Analyse/Routing/Process/ProcessClosure.php';
include 'src/Analyse/Routing/Process/ProcessFloat.php';
include 'src/Analyse/Routing/Process/ProcessInteger.php';
include 'src/Analyse/Routing/Process/ProcessNull.php';
include 'src/Analyse/Routing/Process/ProcessObject.php';
include 'src/Analyse/Routing/Process/ProcessResource.php';
include 'src/Analyse/Routing/Process/ProcessString.php';
include 'src/Analyse/Routing/Process/ProcessOther.php';

include 'src/Analyse/AbstractModel.php';
include 'src/Analyse/Model.php';

include 'src/Controller/AbstractController.php';
include 'src/Controller/BacktraceController.php';
include 'src/Controller/DumpController.php';
include 'src/Controller/TimerController.php';
include 'src/Controller/EditSettingsController.php';
include 'src/Controller/ErrorController.php';

include 'src/Errorhandler/AbstractError.php';
include 'src/Errorhandler/Fatal.php';

include 'src/Service/Config/Fallback.php';
include 'src/Service/Config/Config.php';
include 'src/Service/Config/Model.php';
include 'src/Service/Config/Security.php';

include 'src/Service/Config/From/Cookie.php';
include 'src/Service/Config/From/Ini.php';

include 'src/Service/Factory/EventHandlerInterface.php';
include 'src/Service/Factory/Event.php';
include 'src/Service/Factory/Factory.php';
include 'src/Service/Factory/Pool.php';

include 'src/Service/Flow/Emergency.php';
include 'src/Service/Flow/Recursion.php';

include 'src/Service/Misc/Encoding.php';
include 'src/Service/Misc/File.php';
include 'src/Service/Misc/Registry.php';

include 'src/Service/Reflection/UndeclaredProperty.php';
include 'src/Service/Reflection/ReflectionClass.php';

include 'src/Service/Plugin/Registration.php';
include 'src/Service/Plugin/PluginConfigInterface.php';

include 'src/View/Output/AbstractOutput.php';
include 'src/View/Output/Chunks.php';
include 'src/View/Output/File.php';
include 'src/View/Output/Browser.php';

include 'src/View/RenderInterface.php';
include 'src/View/AbstractRender.php';
include 'src/View/Messages.php';
include 'src/View/Render.php';

// Point the configuration to the right directories
\Brainworxx\Krexx\Service\Config\Config::$directories = array(
    'chunks' => KREXX_DIR . 'chunks/',
    'log' => KREXX_DIR . 'log/',
    'config' => KREXX_DIR . 'config/Krexx.ini',
);

if (!function_exists('krexx')) {
    /**
     * Alias function for object analysis.
     *
     * Register an alias function for object analysis,
     * so you will not have to type \Krexx::open($data);
     * all the time.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $handle
     *   The developer handle.
     */
    function krexx($data = null, $handle = '')
    {
        if (empty($handle)) {
            \Krexx::open($data);
            return;
        }

        \Krexx::$handle($data);
    }
}