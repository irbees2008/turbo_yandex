<?php

// Защита от попыток взлома.
if (!defined('NGCMS')) {
    die('HAL');
}

// Подключаем файл c основной функцией `news_showlist`,
// обрабатывающей запрос к БД по извлечению записей.
// Обеспечивает отработку хуков сторонних плагинов.
if (! function_exists('news_showlist')) {
    include_once root.'includes/news.php';
}

// Подгрузка библиотек-файлов плагина.
loadPluginLibrary('turbo_yandex', false);

use Plugins\TurboYandex;

// Регистрация страниц плагина.
register_plugin_page('turbo_yandex', '', 'plugin_turbo_yandex', 0);
register_plugin_page('turbo_yandex', 'category', 'plugin_turbo_yandex', 0);

function plugin_turbo_yandex(array $params = [])
{
	$turboYandex = new TurboYandex($params);

    $turboYandex->generate();
}
