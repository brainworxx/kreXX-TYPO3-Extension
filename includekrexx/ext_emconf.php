<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "includekrexx".
 *
 * Auto generated 02-12-2014 14:06
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
  'title' => 'Include kreXX',
  'description' => 'kreXX is a php debugger with a fatal error handler. It displays debug information about objects and variables in it\'s own draggable output.',
  'category' => 'fe',
  'version' => '1.3.3',
  'state' => 'stable',
  'uploadfolder' => 1,
  'clearCacheOnLoad' => 1,
  'author' => 'BRAINWORXX GmbH',
  'author_email' => 'tobias.guelzow@brainworxx.de',
  'author_company' => 'BRAINWORXX GmbH',
  'constraints' => array(
    'depends' => array(
      'typo3' => '4.5.0-7.3.99',
      'php' => '5.3.0-5.5.99',
      'extbase' => '1.3.0-0.0.0',
      'fluid' => '1.3.0-0.0.0',
    ),
    'conflicts' => array(
    ),
    'suggests' => array(
    ),
  ),
);
