<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "includekrexx".
 *
 * Auto generated 14-12-2015 17:12
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
  'title' => 'kreXX Debugger',
  'description' => 'kreXX is a php debugger with a fatal error handler. It displays debug information about objects and variables in it\'s own draggable output.',
  'category' => 'misc',
  'version' => '1.4.1',
  'state' => 'stable',
  'uploadfolder' => 1,
  'clearCacheOnLoad' => 1,
  'author' => 'BRAINWORXX GmbH',
  'author_email' => 'tobias.guelzow@brainworxx.de',
  'author_company' => 'BRAINWORXX GmbH',
  'constraints' => array(
    'depends' => array(
      'typo3' => '4.5.0-8.0.99',
      'php' => '5.3.0-7.0.99',
      'extbase' => '1.3.0-0.0.0',
      'fluid' => '1.3.0-0.0.0',
    ),
    'conflicts' => array(
    ),
    'suggests' => array(
    ),
  ),
);

