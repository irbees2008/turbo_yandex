<?php

namespace Plugins;

// Исключения.
use RuntimeException;

// Базовые расширения PHP.
use stdClass;

/**
 * RSS поток для Yandex Turbo.
 */
class TurboYandex
{
    /**
     * Номер версии плагина.
     * @const string
     */
    const VERSION = '0.4.0';

    /**
     * Идентификатор плагина.
     * @var string
     */
    protected $plugin = 'turbo_yandex';

    /**
     * Создать экземпляр плагина.
     */
    public function __construct()
    {
        $this->pluginLink = generatePluginLink($this->plugin, null);

        global $SUPRESS_TEMPLATE_SHOW, $SUPRESS_MAINBLOCK_SHOW;

        // Disable executing of `index` action (widget plugins and so on..)
        actionDisable('index');

        // Suppress templates
        $SUPRESS_TEMPLATE_SHOW = 1;
        $SUPRESS_MAINBLOCK_SHOW = 1;
    }

    /**
     * Получить номер версии плагина.
     * @return string
     */
    public function version()
    {
        return self::VERSION;
    }

    public function generate()
    {
        global $lang, $config, $mysql, $catz, $catmap;

        // Generate cache file name [ we should take into account SWITCHER plugin ]
        // Take into account: FLAG: use_hide, check if user is logged in
        $cacheFileName = md5('turbo_yandex'.$config['theme'].$config['home_url'].$config['default_lang'].(is_array($xcat)?$xcat['id']:'').pluginGetVariable('turbo_yandex', 'use_hide').is_array($userROW)).'.txt';

        if (pluginGetVariable('turbo_yandex', 'cache')) {
            $cacheData = cacheRetrieveFile($cacheFileName, pluginGetVariable('turbo_yandex', 'cacheExpire'), 'turbo_yandex');
            if ($cacheData != false) {
                // We got data from cache. Return it and stop
                header("Content-Type: text/xml; charset=".$lang['encoding']);

                echo $rendered;

                return;
            }
        }

        // Generate output
        $entries = [];

        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'en_EN');

        // Fetch SQL record
        $query = "select * from ".prefix."_news where approve=1 order by id desc limit 100";
        $sqlData = $mysql->select($query);

        foreach ($sqlData as $row) {
            // Make standart system call in 'export' mode
            $newsVars = news_showone(
                $row['id'],
                '',
                array(
                    'emulate' => $row,
                    'style' => 'exportVars',
                    'extractEmbeddedItems' => pluginGetVariable('turbo_yandex', 'extractEmbeddedItems') ? 1 : 0,
                    'plugin' => 'turbo_yandex',
                )
            );

            // Calculate news category list
            $catList = array();
            foreach (explode(",", $row['catid']) as $v) {
                if (isset($catmap[$v])) {
                    $catList []= $catz[$catmap[$v]]['name'];
                }
            }

            $masterCategoryName = '';
            if (count($catList)) {
                $masterCategoryName = $catList[0];
            }

            $entry = new stdClass;
            $entry->link = newsGenerateLink($row, false, 0, true);
            $entry->pubDate = gmstrftime('%a, %d %b %Y %H:%M:%S GMT', $row['postdate']);
            $entry->title = $row['title'];

            $entry->content = $newsVars['short-story'].' '.$newsVars['full-story'];
            $entry->short = $this->stripTags($newsVars['short-story']);
            $entry->full = $this->stripTags($newsVars['full-story']);

            $entry->images = $newsVars['news']['embed']['images'] ?? [];

            if (getPluginStatusActive('xfields')) {
                $entry->xfields = $newsVars['p']['xfields'];
            }

            $entries[] = $entry;
        }

        setlocale(LC_TIME, $old_locale);

    	$rendered = $this->render([
            'link' => $config['home_url'],
            'title' => $config['home_title'],
            'description' => $config['description'],
            'language' => $config['default_lang'],
            'entries' => $entries,

        ]);

        if (pluginGetVariable('turbo_yandex', 'cache')) {
            cacheStoreFile($cacheFileName, $rendered, 'turbo_yandex');
        }

    	header("Content-Type: text/xml; charset=".$lang['encoding']);

        echo $rendered;
    }

    protected function stripTags(string $content)
    {
        return strip_tags(
            $content,
            '<p><figure><img><iframe><br><ul><ol><li><b><strong><i><em><sup><sub><ins><del><small><big><pre></pre><abbr><u><a>'
        );
    }

    public function render(array $vars)
    {
        $tpath = locatePluginTemplates([
                'channel',
            ], 'turbo_yandex',
            setting('turbo_yandex', 'localsource', 1)
        );

        return view($tpath['channel'] . 'channel.tpl', $vars);
    }
}
