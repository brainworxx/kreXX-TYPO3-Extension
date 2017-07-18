.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _changelog:

=============================================================
Changelog
=============================================================

2.2.1
    - [Feature] Added a Fluid specific caller finder for the fluid debugger
    - [Feature] Added a configuration for the backtrace, to limit the analysed steps.
    - [Feature] Added property comments to the analysis
    - [Feature] Added property declaration place to the analysis.
    - [Feature] Added better unicode support for the HTML output.
    - [Change] Several performance tweaks for runtime optimization.
    - [Change] Fallback setting runtime => level set to 5.
    - [Change] Fallback setting runtime => maxCall set to 10.
    - [Change] Refactored the half-assed messaging implementation.
    - [Change] The cookie editor is now much better readable.
    - [Bugfix] Several tweaks to get a smaller HTML footprint.
    - [Bugfix] Prevent the debug methods from creating new analysis calls, resulting in an infinite loop.
    - [Bugfix] Better cleanup of HTML fragments left open from the hosting CMS.
    - [Bugfix] Reverted the 'Output -> File' change from 2.2.0
    - [Bugfix] Prevent a notice in case a property has a default value which is NULL.
    - [Bugfix] Fixed a possible endless loop when iterating a traversable object.
    - [Bugfix] Limit the preview of method analysis with a lot of parameters or long namespaces.
    - [Bugfix] Removed a notice, in case krexx was called from normal php and then again from a registered shutdown function.
    - [Bugfix] Removed the multiple escaping of inherited comments.
    - [Bugfix] Use the filepath filter in the method and function analysis.
    - [Bugfix] Made use of the language file (nearly) everywhere.
    - [Bugfix] Make sure that there are no leftover chunks after a run.
    - [Bugfix] Prevent large output in case of arrays with more than 100 items.
    - [Bugfix] Escaped info text about the maximum resting level.
    - [Bugfix] Missing leading backslash in classname display in several places.
    - [Bugfix] Code generation respects the scope analysis.
    - [Bugfix] The method analysis now displays the default parameter values correctly (or at all).
    - [Bugfix] No more getter analysis for internal php classes.
    - [Bugfix] The registry now can really tell if a value was set, or not.
    - [Bugfix] The short text of an expandable child is now searchable.
    - [Bugfix] Use the filepath filter for the location of the ini file.
    - [Bugfix] Removed a warning in the filterFilePath, in case kreXX was called via CLI.
    - [Bugfix] Proper message output in case of a shell call.
    - [Bugfix] Proper handling of dynamic declared class properties with PHP forbidden chars.
    - [Bugfix] The sorting of the configuration now stay the same as the fallback settings.
    - [Bugfix] The traversable analysis may forget to lower the nesting level again.
    - [Bugfix] The file path filter now uses realpath() to resolve possible symlinks.
    - [Bugfix] Fixed a warning in PHP 5.3 when trying to get a object hash from an array.
    - [Bugfix] Fixed a autoloading triggering event, when processing a string.
    - [Bugfix] Fixed an issue with the path filter and the directory separator string on windows systems.
    - [Bugfix] Fixed an issue, where the preview of the string was first escaped, and then truncated.

2.2.0
    - [Feature] Added a fluid debugger viewhelper.
    - [Feature] Added more search pattern and source code parsing to the getter analysis.
    - [Feature] Added a metatag to both skins to have a little chance to prevent crawler from indexing a kreXX output. Remember kids: never debug a productive site. This will only lead to trouble.
    - [Feature] Added a Filter for the server document root from the file path of the calling file.
    - [Internals] A lot of small changes for the fluid debugger.
    - [Change] The log chunk and config folder are now residing in the :literal:`typo3temp` folder.
    - [Change] Output -> File will now save the logfile directly after the analysis.
    - [Change] Renamed the Output -> Frontend configuration to Output -> browser.
    - [Bugfix] Removed a warning in the IP-Whitelisting, in case there is no actual IP available.
    - [Bugfix] Source generation for resolved recursions works now as expected.
    - [Bugfix] Removed a warnings and some notices in case the $_SERVER variable was messed with.
    - [Bugfix] Prevent a thrown error, in case a class implements some sort of debugger trap by explicitely throwing errors when trying to get the traversable data.


2.1.2
    - [Bugfix] Fixed that annoying warning with PHP7.
    - [Change] :literal:`includekrexx` and :literal:`krexx` version numbers are out of sync (for now).

2.1.1
    - [Feature] Added the info, if a property / method is inherited.
    - [Feature] Added a configuration for the scope analysis.
    - [Feature] Added the search option "Search whole value".
    - [Feature] Added the additional info from Smoky-Grey to the Hans, which will be displayed inside the help-box on hover.
    - [Feature] Readded the removed configuration options in the backend (see v2.0.1).
    - [Internals] Refactored what did not make it into v2.0.0 due to time constraints and introduced a factory.
    - [Bugfix] The comments will not break out of the getter analysis json anymore
    - [Bugfix] Removed a warning in case kreXX was called from eval'd code.
    - [Bugfix] Dumping of inherited private properties works now.
    - [Bugfix] Inherited properties and methods are now regarded by the scope analysis.
    - [Bugfix] Blacklisted all reflection classes for configured debug methods.
    - [Bugfix] Getter analysis is now respecting the scope analysis result.
    - [Bugfix] Removed the type-spam in the additional data.

2.1.0
    - [Feature] Added getter method analysis for models.
    - [Feature] Added search options to both skins.
    - [Feature] Added the '=' to the Hans Skin for better readability.
    - [Feature] Added a delete button in the logfile access
    - [Internals] Moved the configuration file to it's own folder.
    - [Internals] Refactored code comment analysis.
    - [Change] Made the callback display in both skins a little less obtrusive
    - [Change] [runtime]level is now '10' in the factory settings.
    - [Change] [runtime]maxCall is now '15' in the factory settings.
    - [Bugfix] Added LazyLoadingProxy->__toString() to the debug blacklist to prevent a fatal.
    - [Bugfix] Fixed the (XX) logo interference with the search box in the Hans skin.
    - [Bugfix] The search count is not zero-based anymore.
    - [Bugfix] Recursion resolving works now for closures.

2.0.1
    - [Feature] Added a ip mask to whitelist ip's that can trigger kreXX.
    - [Feature] Added the method arguments to the method analysis in the smokygrey skin.
    - [Internals] Refactored the configuration and introduced models there.
    - [Change] Removed the \Krexx::enable() call.
    - [Change] Removed configurations, that nobody was editing anyway.
    - [Bugfix] Rendering of the 'extra' part for long string works now correctly.
    - [Bugfix] Sourcecode generation for traversable classes should work now for none ArrayAccess classes.
    - [Bugfix] A string with the value of '0' will get displayed again.
    - [Bugfix] Fixed a notice in the \Krexx::backtrace();

2.0.0
    - [Feature] Added sourcecode to the closure analysis.
    - [Feature] Prettified the sourcecode display in the smokygrey skin.
    - [Internals] Refactored pretty much everything and introduced something that looks remotely like MVC. This results in a major increase in speed.
    - [Change] Removed the unnecessary fluff from the source generation. Stuff like '$kresult =' is now gone.
    - [Bugfix] Removed the code generation for traversable classes that can not be accessed via chaining.
    - [Bugfix] Code generation is now working when krexx is called via :literal:`Krexx::`.
    - [Bugfix] Wrong line number in the fatal error handler.
    - [Bugfix] Code generation for class constants now works properly.
    - [Bugfix] Removed a  warning with the glob() function which may occur on some systems.
    - [Bugfix] Added a check to the developer handle to prevent warnings.

1.4.2
    - [Feature] Added analysis metadata to the file output.
    - [Feature] Added metadata to the backend logging
    - [Internals] Adopted PSR-2
    - [Change] Restructured the configuration options. The sorting does now make more sense than before.
    - [Change] Removed logging options and debug methods from the frontend editing configuration optins.
    - [Change] When the destination is set to 'file' via config file, this value can not be overwritten with local cookie settings.
    - [Change] Unclunked the Smokey-Grey skin.
    - [Change] [Bugfix] Removed the whole dual-output mess.
    - [Bugfix] No help text displayed for readonly cookie config.
    - [Bugfix] The Hans skin renders the config option name twice.
    - [Bugfix] When setting the logfiles to '10', kreXX will now keep 10 files, and not 9.
    - [Bugfix] The debug output might jump around, in some special css environments.
    - [Bugfix] Proper handling of broken html output from the hosting CMS.

1.4.1
    - [Feature] Added class constants analysis.
    - [Feature] Added a new backend menu to access the log files
    - [Internals] Added the possibility to remove message keys from the message class
    - [Internals] Cleaned up the object analysis as well as the namespace usage.
    - [Change] Search is now case-insensitive. This should make searching much more easy.
    - [Change] Output destination can not be changed anymore via the cookie editor by default. This should prevent people from locking themselves out.
    - [Bugfix] Removed hardcoded logfolder path in the bootstrap phase.

1.4.0
    - [Feature] Added smoky-grey as the new standard skin.
    - [Feature] Updated to PHP7
    - [Internals] Added the SkinRender class to the skin directory, so every skin can do some special stuff.
    - [Internals] Added rudimentary translation support for the messaging class.
    - [Internals] Added minimized js libraries for smaller frontend output.
    - [Internals] Refactored rendering process.
    - [Change] Removed the useless array nest from the traversable info, to produce a better readability.
    - [Change] Changed the extension name to kreXX Debugger
    - [Bugfix] Added some primary formatting to the Hans skin to prevent the host system from messing with the css formatting of the skin.
    - [Bugfix] Removed the jQuery library. RequireJS should now work normally when used on the frontend.
    - [Bugfix] Generated sourcecode now works with IteratorAggregate when trying to access a single element from the iterator.
    - [Bugfix] Configured debugging methods will not be called anymore, if they require a parameter.
    - [Bugfix] Prevent the calling of configured debug methods which are callable, but do not exist thanks to the __call() function.
    - [Bugfix] When kreXX encounters an emergency break, the frontend configuration will be accessible, giving the dev the opportunity to change the settings.
    - [Bugfix] Fixed an issue with the benchmarking, when the dev has forgotten to start the timer.
    - [Bugfix] A click on the generated php code does not bubble anymore.

1.3.6
    - [Feature] Added scope analysis. Protected an private variables are treated as public in case they are reachable with the called scope.
    - [Feature] Class properties are now sorted alphabetically.
    - [Feature] Improved the automatic code generation for recursions.
    - [Change] Replaced the option analysePublicMethods with analyseMethodsAtall. The old option does not really make sense anymore
    - [Change] Standard value for 'backtraceAnalysis' is now 'deep'.
    - [Bugfix] Added the "$" in front of static properties in code generation.
    - [Bugfix] Automatic selection of the generated source code now works correct.
    - [Bugfix] Code generation now works in IE and Edge.
    - [Bugfix] Several JS fixes for IE9.

1.3.5
    - [Feature] Added code analysis to determine the name of the variable we are analysing.
    - [Feature] Added warning to tell the user that we are not starting an other analysis, because we will reach output => maxCall.
    - [Bugfix] Recursion clicking does not produce double ids anymore.

1.3.4
    - [Feature] Added closure analysis.
    - [Change] Removed the jQuery setting
    - [Bugfix] Hans skin tries to close some left-over html tag to get a proper display
    - [Bugfix] The display of the settings in the footer doesn't do a callable analysis anymore, which may be a little bit confusing.
    - [Bugfix] Fixed a small display issue with the search in the Hans skin
    - [Bugfix] The Collapse-Everything-Else function from the hans skin does not affect other debug output anymore.

1.3.3
    - [Feature] kreXX will now work without a writable chunks folder, but this will require much more memory.
    - [Feature] Added a new backend menu to access local cookie settings.
    - [Internals] Refactored file handling (chunks and logfiles).
    - [Internals] Cleaned up the file structure.
    - [Internals] kreXX will now evaluate all cookie settings right away, and not when the value is actually needed.
    - [Bugfix] String encoding detection now works as intended. This should speed up things a lot.
    - [Bugfix] Dual output (file and frontend) works again.
    - [Bugfix] CLI detection now respects file output configuration.
    - [Bugfix] X-Browser Adjustments for the Hans skin.
    - [Change] Restructured the output, to make it (hopefully) better readable. The format is now much more similar to the actual code.

1.3.2
    - [Internals] Added a small blacklist of classname/debugfunction combination which may cause problems during object analysis.
    - [Internals] Added composer.json
    - [Change] Removed the old and ugly schablon skin.
    - [Bugfix] Removed that annoying "Hidden internal properties" message.

1.1.1
    - [Change] Removed the Debug Cookie in favor for the local open function
    - [Bugfix] Local open function is working again.
    - [Bugfix] Displaying the local configuration does not re-enable kreXX anymore.
    - [Bugfix] Emergency break does not trigger a false positive anymore.
    - [Bugfix] Display of wrong values in the settings, in case those settings are not editable and there are some leftover values in the settings cookie.
    - [Bugfix] Proper display of static values in objects.
    - [Bugfix] Proper display of internal properties of predefined php classes.

1.1.0
    - [Feature] Added search function to the Hans skin.
    - [Feature] Added collapse-everything-else to the Hans skin.
    - [Feature] Added better recursion handling in the Hans skin.
    - [Feature] Added administration for the FE config.
    - [Feature] Added CLI detection and message handling in CLI.
    - [Feature] Added another editor to the backend to configure the frontend editing of the settings.
    - [Bugfix] Possible jQuery errors when the host site is using the noConflict mode.
    - [Bugfix] Refactored css of the Hans skin for minimal interference with the host template. Most base64 images were replaced by unicode characters.
    - [Bugfix] Possible false string encoding.
1.0.0
    - [Feature] Reduced overall memory usage.
    - [Feature] Added memory usage check during frontend rendering.
    - [Feature] Added check if log and chunk folders are writable.
    - [Feature] Added analysis for protected and private class methods.
    - [Feature] Emergency break is now configurable.
    - [Feature] Moved output to a shutdown callback.
    - [Feature] Added an editor to the backend for the configuration file.
    - [Internals] Adjustments for the backend editor of the config file.
    - [Bugfix] The config display now resets the hive.
    - [Bugfix] Sourcecode in the backtrace does not display strange char count anymore.
    - [Bugfix] Configuration file get loaded again.
    - [Bugfix] Fatal error for a private or protected configured debug method
    - [Bugfix] Catchable error for a configured debug method with parameters

