<?php

// Защита от попыток взлома.
if (! defined('NGCMS')) {
    die('HAL');
}

// Дублирование глобальных переменных.
$plugin = 'turbo_yandex';
$pluginLink = generatePluginLink($plugin, null, [], [], true, true);

// Подгрузка библиотек-файлов плагина.
plugins_load_config();
LoadPluginLang($plugin, 'backend', '', '', ':');
loadPluginLibrary($plugin, 'helpers');

// Используем функции из пространства `Plugins`.
use function Plugins\catz;
use function Plugins\setting;
use function Plugins\dd;

// Подготовка переменных.

// IF plugin 'XFIELDS' is enabled - load it to prepare `enclosure` integration
$xfEnclosureValues = [
    '' => null,

];

if (getPluginStatusActive('xfields')) {
    loadPluginLibrary('xfields', 'common');

    // Load XFields config
    if (is_array($xarray = xf_configLoad())) {
        foreach ($xarray['news'] as $id => $data) {
            $xfEnclosureValues[$id] = "{$id} ($data[title])";
        }
    }
}

// For example - find 1st category with news for demo URL
$demoCategory = null;

foreach (catz() as $category) {
    if ($category['posts'] > 0) {
        $demoCategory = $category['alt'];
        break;
    }
}

// Заполнить параметры конфигурации.
$cfg = [];

// Описание плагина.
array_push($cfg, [
    'descr' => $lang[$plugin.':description'],

]);

array_push($cfg, [
    'descr' => sprintf(
        $lang[$plugin.':description_all'],
        $pluginLink,
        $pluginLink
    ),

]);

if ($demoCategory) {
    array_push($cfg, [
        'descr' => sprintf(
            $lang[$plugin.':description_category'],
            generatePluginLink($plugin, 'category', ['category' => $demoCategory], [], true, true),
            $catz[$demoCategory]['name']
        ),

    ]);
}


// Основные настройки.
array_push($cfg, [
    'mode' => 'group',
    'title' => $lang[$plugin.':group_main'],
    'entries' => [
        [
            'name' => 'skipcat',
            'title' => $lang[$plugin.':skipcat'],
            'type' => 'input',
            'value' => setting($plugin, 'skipcat', null),

        ], [
            'name' => 'extractEmbeddedItems',
            'title' => $lang[$plugin.':extractEmbeddedItems'],
            'descr' => $lang[$plugin.':extractEmbeddedItems#descr'],
            'type' => 'select',
            'values' => [
                $lang['noa'],
                $lang['yesa']
            ],
            'value' => (int) setting($plugin, 'extractEmbeddedItems', 0),

        ],

    ],

]);

// Настройки отображения.
array_push($cfg, [
    'mode' => 'group',
    'title' => $lang[$plugin.':group_view'],
    'entries' => [
        [
            'name' => 'localsource',
            'title' => $lang[$plugin.':localsource'],
            'descr' => $lang[$plugin.':localsource#descr'],
            'type' => 'select',
            'values' => [
                0 => $lang[$plugin.':localsource_0'],
                1 => $lang[$plugin.':localsource_1']
            ],
            'value' => (int) setting($plugin, 'localsource', 1),

        ],

    ],

]);

// Настройки кеширования.
array_push($cfg, [
    'mode' => 'group',
    'title' => $lang[$plugin.':group_cache'],
    'entries' => [
        [
            'name' => 'cache',
            'title' => $lang[$plugin.':cache'],
            'descr' => $lang[$plugin.':cache#descr'],
            'type' => 'select',
            'values' => [
                $lang['noa'],
                $lang['yesa']
            ],
            'value' => (int) setting($plugin, 'cacheExpire', 0),

        ], [
            'name' => 'cacheExpire',
            'title' => $lang[$plugin.':cacheExpire'],
            'descr' => $lang[$plugin.':cacheExpire#descr'],
            'type' => 'input',
            'value' => (int) setting($plugin, 'cacheExpire', 60),

        ],

    ],

]);

// Если была отправлена форма, то сохраняем настройки.
if ('commit' === $_REQUEST['action']) {
    commit_plugin_config_changes($plugin, $cfg);

    return print_commit_complete($plugin);
}

generate_config_page($plugin, $cfg);
