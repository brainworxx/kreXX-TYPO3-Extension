<?php

return [
    'web_examples' => [
        'extensionName' => 'Includekrexx',
        'parent' => 'tools',
        'position' => ['bottom'],
        'access' => 'user,group',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf',
        'path' => '/module/tools/includekrexx',
        'icon' => 'EXT:includekrexx/Resources/Public/Icons/Extension.svg',
        'controllerActions' => [
            \Brainworxx\Includekrexx\Controller\IndexController::class => 'index, save, dispatch'
        ],
    ],
];
