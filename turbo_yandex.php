<?php

// ������ �� ������� ������.
if (!defined('NGCMS')) {
    die('HAL');
}

// ���������� ���� c �������� �������� `news_showlist`,
// �������������� ������ � �� �� ���������� �������.
// ������������ ��������� ����� ��������� ��������.
if (! function_exists('news_showlist')) {
    include_once root.'includes/news.php';
}

// ��������� ���������-������ �������.
// LoadPluginLang('turbo_yandex', 'frontend', '', 'turbo_yandex', ':');
loadPluginLibrary('turbo_yandex', false);

use Plugins\TurboYandex;

// ����������� ������� �������.
register_plugin_page('turbo_yandex', '', 'plugin_turbo_yandex', 0);
register_plugin_page('turbo_yandex', 'category', 'plugin_turbo_yandex_category', 0);

function plugin_turbo_yandex()
{
	$turboYandex = TurboYandex::getInstance();

    $turboYandex->generate();
}

function plugin_turbo_yandex_category($params)
{
	$turboYandex = TurboYandex::getInstance();

    $turboYandex->generate($params['category']);
}
