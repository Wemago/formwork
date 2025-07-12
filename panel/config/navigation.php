<?php

use Formwork\Cms\Site;
use Formwork\Translations\Translation;

return fn(Site $site, Translation $translation) => [
    'dashboard' => [
        'label'       => $translation->translate('panel.dashboard.dashboard'),
        'uri'         => '/dashboard/',
        'permissions' => 'panel.dashboard',
        'badge'       => null,
    ],
    'pages' => [
        'label'       => $translation->translate('panel.pages.pages'),
        'uri'         => '/pages/',
        'permissions' => 'panel.pages',
        'badge'       => $site->descendants()->count(),
    ],
    'files' => [
        'label'       => $translation->translate('panel.files.files'),
        'uri'         => '/files/',
        'permissions' => 'panel.files',
        'badge'       => null,
    ],
    'statistics' => [
        'label'       => $translation->translate('panel.statistics.statistics'),
        'uri'         => '/statistics/',
        'permissions' => 'panel.statistics',
        'badge'       => null,
    ],
    'users' => [
        'label'       => $translation->translate('panel.users.users'),
        'uri'         => '/users/',
        'permissions' => 'panel.users',
        'badge'       => $site->users()->count(),
    ],
    'options' => [
        'label'       => $translation->translate('panel.options.options'),
        'uri'         => '/options/',
        'permissions' => 'panel.options',
        'badge'       => null,
    ],
    'tools' => [
        'label'       => $translation->translate('panel.tools.tools'),
        'uri'         => '/tools/',
        'permissions' => 'panel.tools',
        'badge'       => null,
    ],
    'logout' => [
        'label'       => $translation->translate('panel.login.logout'),
        'uri'         => '/logout/',
        'permissions' => null,
        'badge'       => null,
    ],
];
