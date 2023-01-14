.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _changelog:

=============================================================
Changelog
=============================================================

4.1.8
    - [Feature] TYPO3 12.1 support.
	- [Feature] PHP 8.2 support.
    - [Bugfix] Fixed the Aimeos settings in the ext_emconf.
    - [Bugfix] Added missing double escaping to the code generation.
    - [Bugfix] Add Backslashes to quotation marks of generated source.
    - [Bugfix] Reworked the backend module registering according to the updated documentation.
    - [Bugfix] Fix the JS initializing in TYPO3 12.

4.1.7
    - [Feature] TYPO3 12.0 support
    - [Bugfix] Removed a faulty 'use' doc comment.
    - [Bugfix] Use the correct method signature for the error handler callback.
    - [Bugfix] Fixed the BOM detection in property names.
    - [Bugfix] Fixed the SQL query debugger in PHP 8 strict mode.
    - [Bugfix] Prevent a second the JS initialization if the hosting cms thinks that calling 'DOMContentLoaded' more than once is a good idea.
    - [Bugfix] Prevent a type hint for none variables.
    - [Bugfix] Add the missing file path filter to the backtrace analysis.
    - [Bugfix] Fix the handling of the 8.1 Enum as a default value in the source generation.

4.1.6
    - [Bugfix] Link to the documentation of the debug preset does not work anymore in the documentation.
    - [Bugfix] Added the missing path filter to the EXT: path resolver.
    - [Bugfix] Added the missing path filter to the real path resolver.
    - [Bugfix] Use strict encoding detection for strings for more reliable results.
    - [Bugfix] Fetch traversable data as soon as possible, because we do not want other analysis methods fetch traversable results, that are only fetchable once. DB results are a good example for this.

4.1.5
    - [Feature] Aimeos 2022 support
    - [Bugfix] Remove the use of the deprecated \TYPO3\CMS\Extbase\Mvc\View\ViewInterface.
    - [Bugfix] Give valid feedback, in case the DI fails during query debugging.
    - [Bugfix] Unnecessary uri encoding in the smoky grey skin prevents the display of certain additional info values.
    - [Bugfix] Prevent an overflow in the additional info of the smoky grey skin.
    - [Bugfix] Register the scalar translation analyser.
    - [Bugfix] Do not basename() an unknown file path in the backend logging.

4.1.4
    - [Feature] Added support for readonly properties
    - [Bugfix] Correctly identify uninitialized properties.
    - [Bugfix] Prevent unnecessary filesystem calls in the filepath analysis.

4.1.3
    - [Feature] Added microtime analysis to the float routing.
    - [Feature] Added microtime analysis to the string scalar analysis.
    - [Feature] Added type hint to the additional data for the first element.
    - [Feature] PHP 8.1 support
    - [Change] Code cleanup.
    - [Change] Added the Limitation page to the Tips'n'Tricks documentation.
    - [Bugfix] Display info about public properties in predeclared classes.
    - [Bugfix] Comment inheritance resolving works more reliable.
    - [Bugfix] Method return type comment parsing works more reliable.
    - [Bugfix] Added missing parameters to the source generation of the Aimeos debug methods.
    - [Bugfix] Mitigated the deprecated page renderer retrieval from the ModuleTemplate instance.
    - [Bugfix] Standard loading of the configuration file works again. (Standard loading was never used with the TYPO3 extension.)
    - [Bugfix] The SQL debugger works again.
    - [Bugfix] Mime type string and file do not overwrite each others anymore.
    - [Bugfix] K-Type padding for the Hans skin is too small.

4.1.2
    - [Feature] PHP 8.0 support
    - [Feature] Updated to TYPO3 11.5.
    - [Change] Migrate TYPO3 11.4 changes and/or deprecations.
    - [Change] Do not display an empty array, when there are no attributes in the XML analysis.
    - [Bugfix] Loglevel evaluation works correctly in TYPO3 9 and older.
    - [Bugfix] Add additional error handling to the file service to get by with high traffic sites (Please, never debug a productive site. At least make sure that the logger can only get triggered by the dev.).
    - [Bugfix] Applied sorting to the list of getter methods.
    - [Bugfix] Make better use of the recursion detection for the XML analysis.
    - [Bugfix] Fixed / updated the doc comments.
    - [Bugfix] Object recursions in the "$this protected" context can not generate source code.

4.1.1
    - [Change] Remove the usage of the ObjectManager whenever possible.
    - [Change] Code cleanup.
    - [Change] Make use of the Symfony DI.
    - [Change] Use the message and not the component for the logging overview.
    - [Bugfix] Predefined objects do not get their properties analysed.
    - [Bugfix] Display the DateTime anomaly "public" properties.
    - [Bugfix] Consolidate PHP 8.0 compatibility.
    - [Bugfix] Make use of the :literal:`Oops an error occurred!` analysis when the error got renamed.
    - [Bugfix] Minor styling fixes for the backend module.

4.1.0
    - [Feature] Added a log model to use for a logger implementation.
    - [Feature] Added .min. support for css files.
    - [Feature] Added apostrophes around string array keys to the SmokyGrey skin for better readability.
    - [Feature] Added support for a JSON configuration file.
    - [Feature] Added PHP 8.0 support (bundled kreXX library only).
    - [Feature] Allow plugins to register their own settings.
    - [Feature] Added a complete backtrace analysis to the log writer.
    - [Feature] Added the debug method definition for service attributes to the aimeos debugger.
    - [Feature] Added a backend configuration for the integration into the TYPO3 file logging.
    - [Feature] Added a special analysis for the dreaded :literal:`Oops an error occurred!` error.
    - [Change] When analysing a logmodel or an exception, kreXX now analyses the special log/error stuff before the getter.
    - [Bugfix] Endless scrolling when clicking too fast through the search.
    - [Bugfix] Exception when using one php statement and a krexx call in the same line.
    - [Bugfix] Prevent long analysis meta data from breaking the BE layout.
    - [Bugfix] Exception, when a mb_detect_encoding() could not determine the encoding of a string.

4.0.0
    - [Feature] Added process resource analysis.
    - [Feature] Added better callback analysis.
    - [Feature] Added better string analysis (json, file path, callback, xml)
    - [Feature] Added timestamp analysis for large integers.
    - [Feature] Added throw away messages.
    - [Feature] Added return type to the method and function analysis.
    - [Feature] Make code generation possible for the getProperties debug method in Fluid.
    - [Feature] Added EXT: file path analysis
    - [Feature] Added Lll string analysis
    - [Feature] Added Icons to the backend log list.
    - [Feature] Added additional data to the constants analysis for PHP 7.1 and higher. The scope analysis now respects their visibility.
    - [Feature] Added logging shorthand "krexxlog();".
    - [Feature] The SQL Debugger now tells the dev if there was an error in the sql statement.
    - [Change] Removed all deprecations.
    - [Change] Removed the PHP 5.x fatal error handler.
    - [Change] Dropped PHP 5.x support.
    - [Change] Remove all double Exception / Throwable catching
    - [Change] Introduced PSR-12 coding style
    - [Change] Simplified the skin rendering.
    - [Change] Deprecations for the fallback settings class.
    - [Change] Introduced strict mode.
    - [Change] Introduced scalar type hints.
    - [Change] Introduced method return types.
    - [Change] Simplified the Model.
    - [Change] Do not display the encoding info, if we have a buffer info available.
    - [Change] Different analysis order, when coming from the $this scope, for better source generation.
    - [Change] Different order in the backtrace analysis, for better readability.
    - [Change] Use compressed css for the SmokyGrey skin.
    - [Change] Refactored the code generation.
    - [Change] Refactored the routing.
    - [Change] "Called from" is always expanded in the smokygrey skin.
    - [Change] The connector constants are now strings.
    - [Change] Removed the "local opening function" aka. devHandle.
    - [Bugfix] The search does now respects the selected tab.
    - [Bugfix] Added missing meta data to a handled exception.
    - [Bugfix] Prevent an open <pre> from messing with the output
    - [Bugfix] The Aimeos decorator analysis works now as it should.
    - [Bugfix] Added missing Aimeos debug method 'getAttributeItems'.

3.3.6
    - [Bugfix] Removed the composer definition for the class alias loader and use an alternative implementation.

3.3.5
    - [Bugfix] Added missing composer definition for the class alias loader.

3.3.4
    - [Bugfix] Analysing of __PHP_Incomplete_Class does not throw errors anymore.

3.3.3
    - [Bugfix] Fixed the composer dependencies.

3.3.2
    - [Feature] TYPO3 10.4 support.
    - [Bugfix] Added missing closing li tag to the expandableChild template.
    - [Bugfix] The fe configuration does not update the render type.
    - [Bugfix] Do not mixup and/or combine escaping for keys and/or code generation.
    - [Bugfix] Remove a possible warning when cleaning up old log files.
    - [Bugfix] Minimise interference with strange CSS styles.

3.3.1
    - [Feature] Updated to PHP 7.4
    - [Bugfix] The process other routing is never called.
    - [Bugfix] The cookie editor needs to be "initialized" prior usage.
    - [Bugfix] Wrong meta data, when using dual output.
    - [Bugfix] Missing css definitions for label.
    - [Bugfix] Unwanted re-enabling of the source generation.
    - [Bugfix] Environment check may fail

3.3.0
    - [Feature] Introduce php-mock/php-mock-phpunit.
    - [Feature] TYPO3 10.1 support.
    - [Feature] Added event system to the process classes.
    - [Feature] Added better model analysis for TYPO3 standard models.
    - [Feature] Added SQL debugger.
    - [Feature] Clean(er) interface list inside the meta analysis.
    - [Feature] Added current url to the caller finder output.
    - [Feature] Better timer-emergency management on CLI.
    - [Change] Remove the event prefix and use static::class instead.
    - [Change] Move cleanup methods to their own class.
    - [Change] Move the output check methods to an appropriate class.
    - [Change] Deprecated classes and methods.
    - [Change] Complete refactor of the rendering mechanism.
    - [Change] Ported the javascript to type script.
    - [Change] Removed TYPO3 6.2 compatibility.
    - [Change] Removed DataViewer support.
    - [Bugfix] Missing encoding info in the error handler output.
    - [Bugfix] Removed the TER-SonarQube findings from the unit tests.
    - [Bugfix] Warning when accessing the backend module.
    - [Bugfix] Warning when saving the settings.
    - [Bugfix] Getter analysis of the Aimeos debugger misses mtime and ctime.
    - [Bugfix] Wrong class list in the aimeos decorator analysis.
    - [Bugfix] Wrong PHP constraints in the ext_emconf.
    - [Bugfix] Wrong null values for dynamically declared properties.
    - [Bugfix] Inaccessible array values from array casted objects.
    - [Bugfix] Wrong variable name retrieval when used inline.
    - [Bugfix] Wrong return value from the developer handle.
    - [Bugfix] Wrong error handler restoration after deleting a file.

3.2.0
    - [Feature] Use some real autoloading, with a fallback to manually including all files.
    - [Feature] Plugins can now register additional skins.
    - [Feature] kreXX debug calls will return the original analysis value.
    - [Feature] Leading and trailing spaces are now better visible in the output.
    - [Feature] The backtrace action accepts now an already existing one. Great for debugging error objects.
    - [Feature] Minor usability changes to both skins.
    - [Feature] Added an automatic backtrace analysis for error objects.
    - [Feature] Added the sourcecode dump to the error object analysis.
    - [Feature] Added proper handling for BOM chars in array keys and properties.
    - [Feature] Added an exception handler, to replace the PHP5 Fatal Error Handler.
    - [Feature] Added the date time to the output.
    - [Feature] Added analysis of the meta data of an object.
    - [Feature] Added getRefItems, getPropertyItems, getListItems handling to the debug methods.
    - [Change] Lots of deprecations.
    - [Change] Moved the skin render classes to the source folder.
    - [Change] Dropped PHP 5.3 and PHP 5.4 support.
    - [Change] Moved the last hardcoded html tags to the skin renderers.
    - [Change] When registering a plugin, you must use a class instance, instead of a name of a static class.
    - [Bugfix] Fluid code generation for variable names with dots in them.
    - [Bugfix] CSS selectors are too weak in the backend module.
    - [Bugfix] Fixes some "bugs" SonarCube found in the unit test fixtures, to prevent bad ratings.
    - [Bugfix] Check if the developer handle is actually a string.
    - [Bugfix] Added a missing check in the url determination in the timer controller
    - [Bugfix] The registering of blacklisted methods and classes for the debug methods work now, as they should.
    - [Bugfix] The rewriting of singleton classes in the pool does not work.
    - [Bugfix] Adding additional data in the code generation is not rendered.
    - [Bugfix] Added the plugin list to the (fatal) error handler display of the smokygrey skin.
    - [Bugfix] Wrong display of null and boolean default values in the code generation and method analysis.
    - [Bugfix] Display of wrong filename when a kreXX resource is not readable.
    - [Bugfix] The registry will not return values that are considered empty().
    - [Bugfix] Missing translation keys.
    - [Bugfix] Invalid php doc comments may trigger errors

3.1.0
    - [Feature] Nearly complete rewrite of the backend module.
    - [Feature] Logfile access in the debugbar.
    - [Feature] Added classname to the declaration analysis of properties.
    - [Feature] Added analysis of cUrl resources.
    - [Feature] Added a check for the content type to the ajax detection.
    - [Change] :literal:`includekrexx` and :literal:`krexx` version numbers are out of sync, because of the complete rewrite of the backend module.
    - [Change] Protected properties are now wrapped again.
    - [Bugfix] Flush cache on update/install in 9.5 does not work anymore.
    - [Bugfix] Malformed table in the extension documentation.
    - [Bugfix] Replace the $hellip; in the file service, it may cause double escaping issues in the backend of some systems.
    - [Bugfix] The method analysis doesn't take traits into account.
    - [Bugfix] The property analysis doesn't take traits into account.
    - [Bugfix] Remove the copy-pasta spaces from the skins.
    - [Bugfix] Cut off parameter analysis.
    - [Bugfix] Property analysis does not handle predefined classes correctly.
    - [Bugfix] "Autoloading" may fail with a weird directory path.
    - [Bugfix] The fatal error handler backtrace is broken.
    - [Bugfix] Fix the styles of the Hans skin.

3.0.1
    - [Bugfix] Added the missing end event to the property analysis.
    - [Bugfix][Change] Configured debug methods are now checked on configuration loading.
    - [Bugfix] Preserve the line breaks from the string-extra.
    - [Bugfix] Repair the UndeclaredProperty class and use it.
    - [Bugfix] Lower the nesting level again after a failed traversable analysis.
    - [Bugfix] Analysis of private getter do not respect the context.
    - [Bugfix] Interesting display of parameters in the method analysis.
    - [Bugfix] Infinite loop when configuring the ip range.
    - [Bugfix] PHP5.x pars error in class ViewFactory
    - [Bugfix] Double escaped path value in the config-help page
    - [Change] All singelton classes now add themself to the pool as soon as they are created.

3.0.0
    - [Feature] Added 'is' and 'has' to the getter analysis.
    - [Feature] Added plugin support, to replace the half-asses overwrites.
    - [Feature] Added a event dispatcher.
    - [Feature] Added deeper search for the source code getter analysis for better results.
    - [Feature] Added Aimeos shop debugger.
    - [Feature] Added a forced logger, which van be reached by \Krexx:log();
    - [Feature] Added a forced fluid logger, which van be reached by <krexx:log value={_all} />
    - [Feature] Added a jumpTo element after uncollapsing the breadcrumbs for better usability.
    - [Feature] Added support for "\0" chars.
    - [Feature] Added the count info to the traversable analysis.
    - [Feature] Added meta data analysis to the stream resource.
    - [Change] Removed the old 4.5 compatibility.
    - [Change] The file logger writes the logfile right after the analysis is complete.
    - [Change] Some internal renaming.
    - [Change] Removed the constants analysis configuration.
    - [Change] Moved the bootstrapping to it's own file.
    - [Change] Removed the annoying spaces from the generated DOM, for better copy-paste.
    - [Change] Resorted the settings.
    - [Change] Prettified the output of the Hans skin.
    - [Change] Moved the existing overwrites into plugins.
    - [Change] Used the introduced event system in the plugins when possible.
    - [Change] Mime type analysis threshold is now 20 chars for strings.
    - [Change] The file logger writes the logfile right after the analysis is complete.
    - [Bugfix] The position of the search field of the hans skin is now calculated correct when the viewport is not on top.
    - [Bugfix] The scroll container detection of the hans skin works now.
    - [Bugfix] Added help text for the arrayCountLimit.
    - [Bugfix] "Resolving" of unresolvable inherited comment parts work now as expected.
    - [Bugfix] Prevent the registering of multiple fatal error handlers.
    - [Bugfix] Minimise interference with strange CSS styles.
    - [Bugfix] Do not render an unresolvable method analysis recursion when there are no methods to analyse in that specific class.
    - [Bugfix] The file service can now read the bottom of file more reliably.
    - [Bugfix] Prevent code generation for explicitly forbidden paths, when the recursion resolving is copying the original analysis into the forbidden path
    - [Bugfix] Removing of message keys should work again.
    - [Bugfix] Duplicate messages will not be displayed anymore.
    - [Bugfix] Fixed a possible fatal, when trying to analyse dynamically declared properties, which have a name collusion with private properties somewhere deeper in the class inheritance.
    - [Bugfix] Detect unset properties in classes.
    - [Bugfix] Added closing style tags to both skins
    - [Bugfix] Catch throwable in PHP 7.
    - [Bugfix] Added two missing translation keys.
    - [Bugfix] Added 'Krexx' with a capital 'K' to the caller finder pattern.
    - [Bugfix] Prevent a possible fatal when analysing methods or closures, and the typehinted class for this parameter does not exist.
    - [Bugfix] timer::moment() now disrespects the ajax or shell detection, and works better with the forced logging.
    - [Bugfix] Prevent other js libraries from messing with the search form.
    - [Bugfix] Prevent a fatal when trying to read the file time from a not existing file.
    - [Bugfix] Prevent unnecessary width "jumping" in the Smokey Grey skin.
    - [Bugfix] Resource recognition works more accurate.
    - [Bugfix] Fixed a fatal, when the fileinfo extension is not installed.
    - [Bugfix] Fixed a fatal, when the mb-string extension is not installed.
    - [Bugfix] The search of the Hans skin scrolls now more reliably.

2.4.0
    - [Feature] Added the method analysis to the recursion detection, to prevent analysing the same methods over and over again.
    - [Feature] Added Javascript optimisation for very large output.
    - [Feature] Added mimetype analysis for strings.
    - [Feature] Added variable resolving to the fluid debugger.
    - [Feature] Added prosessing class for "other" variable types.
    - [Feature] Added info button to the hans skin, to replace the somewhat intrusive hover info.
    - [Feature] Added a special analysis for the dataviewer values in fluid.
    - [Change] Moved the overwrites from the GLOBALS to a static class
    - [Change] Prettified the display of sourcecode in the backtrace in the smoky grey skin.
    - [Change] Removed the option for the automatic registration of the fatal error handler.
    - [Change] Lots of micro optimizations.
    - [Change] Simplified array analysis is now configurable.
    - [Change] Renamed the 'Backtrace' config group to 'pruneOutput'.
    - [Change] Updated to TYPO3 9.0
    - [Change] Updated to PHP 7.2
    - [Bugfix] Minimise CSS interference from the hosting cms with marked text.
    - [Bugfix] Disabling via sourcecode works again.
    - [Bugfix] Removed the special backtrace configuration, which resulted in a output overkill, crashing the backtrace.
    - [Bugfix] Removed the comma in the method parameter analysis.
    - [Bugfix] Fixed in issue, where the correct nesting level was not set correctly, resulting in output overkill.
    - [Bugfix] Fixed codewrapper2 for the code generation in the Hans skin.
    - [Bugfix] Source generation for closures now work as expected.
    - [Bugfix] Better cleanup for still open HTML tags.

2.3.1
    - [Bugfix] Fixed shell detection.
    - [Bugfix] Fixed shell message feedback
    - [Bugfix] Fixed ajax detection

2.3.0
    - [Feature] Added a Fluid specific caller finder for the fluid debugger
    - [Feature] Added a configuration for the backtrace, to limit the analysed steps.
    - [Feature] Added property comments to the analysis
    - [Feature] Added property declaration place to the analysis.
    - [Feature] Added better unicode support for the HTML output.
    - [Feature] Added better support for debugging onepagers.
    - [Internals] Several performance tweaks for runtime optimization.
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
    - [Bugfix] Fixed a warning, in case there is a special compatibility layer active in conjunction with T>PO3 8.7
    - [Bugfix] The string analysis is now respecting line breaks in short string.
    - [Bugfix] Make sure that the marking of text will be displayed in the browser.
    - [Bugfix] Prevent a search with no search text at all.
    - [Bugfix] The cache handling of searches is now working correctly.
    - [Bugfix] Fixed the display of the search-options-symbol on Macs.
    - [Bugfix] Fixed a possible JS error in the search, in case we are searching through no payload.
    - [Bugfix] Fixed the rendering color of the connector 2 in the Hans skin.

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
    - [Bugfix] Prevent a thrown error, in case a class implements some sort of debugger trap by explicitly throwing errors when trying to get the traversable data.


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
    - [Change] Removed logging options and debug methods from the frontend editing configuration options.
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

