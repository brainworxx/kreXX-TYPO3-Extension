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


// "Autoloader" for kreXX.
// There may or may not be an active autoloader, which may or may not
// be able to autoload the krexx files. There may or may not be an
// unwanted interaction with the rest of the system when registering
// another autoloader. This leaves us with loading every single file
// via include.
// There is also the possibility that the working directory was globally
// changed, hence we need to add the 'KREXX_DIR' in front of every include.
// *Sigh*

if (defined('KREXX_DIR')) {
    // Been here, done that.
    return;
}

define('KREXX_DIR', __DIR__ . DIRECTORY_SEPARATOR);

include KREXX_DIR . 'src/Analyse/ConstInterface.php';
include KREXX_DIR . 'src/Analyse/Callback/AbstractCallback.php';

include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/AbstractObjectAnalysis.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Constants.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/DebugMethods.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Getter.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Methods.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/PrivateProperties.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/ProtectedProperties.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/PublicProperties.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects/Traversable.php';

include KREXX_DIR . 'src/Analyse/Callback/Analyse/BacktraceStep.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/ConfigSection.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Debug.php';
include KREXX_DIR . 'src/Analyse/Callback/Analyse/Objects.php';

include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughArray.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughConfig.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughConstants.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughGetter.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughLargeArray.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMethodAnalysis.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughMethods.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughProperties.php';
include KREXX_DIR . 'src/Analyse/Callback/Iterate/ThroughResource.php';

include KREXX_DIR . 'src/Analyse/Caller/AbstractCaller.php';
include KREXX_DIR . 'src/Analyse/Caller/CallerFinder.php';

include KREXX_DIR . 'src/Analyse/Code/Codegen.php';
include KREXX_DIR . 'src/Analyse/Code/Connectors.php';
include KREXX_DIR . 'src/Analyse/Code/Scope.php';

include KREXX_DIR . 'src/Analyse/Comment/AbstractComment.php';
include KREXX_DIR . 'src/Analyse/Comment/Functions.php';
include KREXX_DIR . 'src/Analyse/Comment/Methods.php';
include KREXX_DIR . 'src/Analyse/Comment/Properties.php';

include KREXX_DIR . 'src/Analyse/Routing/AbstractRouting.php';
include KREXX_DIR . 'src/Analyse/Routing/Routing.php';

include KREXX_DIR . 'src/Analyse/Routing/Process/AbstractProcess.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessArray.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessBacktrace.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessBoolean.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessClosure.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessFloat.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessInteger.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessNull.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessObject.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessResource.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessString.php';
include KREXX_DIR . 'src/Analyse/Routing/Process/ProcessOther.php';

include KREXX_DIR . 'src/Analyse/AbstractModel.php';
include KREXX_DIR . 'src/Analyse/Model.php';

include KREXX_DIR . 'src/Controller/AbstractController.php';
include KREXX_DIR . 'src/Controller/BacktraceController.php';
include KREXX_DIR . 'src/Controller/DumpController.php';
include KREXX_DIR . 'src/Controller/TimerController.php';
include KREXX_DIR . 'src/Controller/EditSettingsController.php';
include KREXX_DIR . 'src/Controller/ErrorController.php';

include KREXX_DIR . 'src/Errorhandler/AbstractError.php';
include KREXX_DIR . 'src/Errorhandler/Fatal.php';

include KREXX_DIR . 'src/Service/Config/Fallback.php';
include KREXX_DIR . 'src/Service/Config/Config.php';
include KREXX_DIR . 'src/Service/Config/Model.php';
include KREXX_DIR . 'src/Service/Config/Security.php';

include KREXX_DIR . 'src/Service/Config/From/Cookie.php';
include KREXX_DIR . 'src/Service/Config/From/Ini.php';

include KREXX_DIR . 'src/Service/Factory/EventHandlerInterface.php';
include KREXX_DIR . 'src/Service/Factory/Event.php';
include KREXX_DIR . 'src/Service/Factory/Factory.php';
include KREXX_DIR . 'src/Service/Factory/Pool.php';

include KREXX_DIR . 'src/Service/Flow/Emergency.php';
include KREXX_DIR . 'src/Service/Flow/Recursion.php';

include KREXX_DIR . 'src/Service/Misc/Encoding.php';
include KREXX_DIR . 'src/Service/Misc/File.php';
include KREXX_DIR . 'src/Service/Misc/Registry.php';
include KREXX_DIR . 'src/Service/Misc/FileinfoDummy.php';

include KREXX_DIR . 'src/Service/Reflection/UndeclaredProperty.php';
include KREXX_DIR . 'src/Service/Reflection/ReflectionClass.php';

include KREXX_DIR . 'src/Service/Plugin/Registration.php';
include KREXX_DIR . 'src/Service/Plugin/SettingsGetter.php';
include KREXX_DIR . 'src/Service/Plugin/PluginConfigInterface.php';

include KREXX_DIR . 'src/View/Output/AbstractOutput.php';
include KREXX_DIR . 'src/View/Output/Chunks.php';
include KREXX_DIR . 'src/View/Output/File.php';
include KREXX_DIR . 'src/View/Output/Browser.php';

include KREXX_DIR . 'src/View/RenderInterface.php';
include KREXX_DIR . 'src/View/AbstractRender.php';
include KREXX_DIR . 'src/View/Messages.php';
include KREXX_DIR . 'src/View/Render.php';

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
