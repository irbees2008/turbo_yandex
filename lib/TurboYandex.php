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

    public function generate($catname = '')
    {
        global $lang, $config, $mysql, $catz, $catmap;

        // Break if category specified & doesn't exist
        if (($catname != '') && (!isset($catz[$catname]))) {
            header('HTTP/1.1 404 Not found');
            exit;
        }

        // Generate header
        $xcat = (($catname != '') && isset($catz[$catname])) ? $catz[$catname] : '';

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
        $maxAge = pluginGetVariable('turbo_yandex', 'news_age');
        $delay = intval(pluginGetVariable('turbo_yandex', 'delay'));

        if ((!is_numeric($maxAge)) || ($maxAge<0) || ($maxAge>1100)) {
            $maxAge = 1110;
        }

        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'en_EN');
        $query = '';
        $orderBy = "id desc";

        if (is_array($xcat)) {
            $orderBy = ($xcat['orderby'] && in_array($xcat['orderby'], array('id desc', 'id asc', 'postdate desc', 'postdate asc', 'title desc', 'title asc')))?$xcat['orderby']:'id desc';
            $query = "select * from ".prefix."_news where catid regexp '[[:<:]](".$xcat['id'].")[[:>:]]' and approve=1 ";
        } else {
            $query = "select * from ".prefix."_news where approve=1 ";
        }

        $query .= (($delay>0)?(" and ((postdate + ".intval($delay*60).") < unix_timestamp(now())) "):'');
        $query .= " and ((postdate + ".intval($maxAge*86400).") > unix_timestamp(now())) ";
        $query .= ""	." order by ".$orderBy;

        // Fetch SQL record
        $sqlData = $mysql->select($query." limit 100");

        // Check if enclosure is requested and used for "images" field
        $xFList = array();
        $encImages = array();
        $enclosureIsImages = false;

        if (pluginGetVariable('turbo_yandex', 'xfEnclosureEnabled') && getPluginStatusActive('xfields')) {
            $xFList = xf_configLoad();
            $eFieldName = pluginGetVariable('turbo_yandex', 'xfEnclosure');
            if (isset($xFList['news'][$eFieldName]) && ($xFList['news'][$eFieldName]['type'] == 'images')) {
                $enclosureIsImages = true;
                // Prepare list of news with attached images
                $nAList = array();
                foreach ($sqlData as $row) {
                    if ($row['num_images'] > 0) {
                        $nAList []= $row['id'];
                    }
                }
                $iQuery = "select * from ".prefix."_images where (linked_ds = 1) and (linked_id in (".join(",", $nAList).")) and (plugin = 'xfields') and (pidentity = ".db_squote($eFieldName).")";
                foreach ($mysql->select($iQuery) as $row) {
                    if (!isset($encImages[$row['linked_id']])) {
                        $encImages[$row['linked_id']] = $row;
                    }
                }
            }
        }

        $newsTitleFormat = pluginGetVariable('turbo_yandex', 'news_title')?pluginGetVariable('turbo_yandex', 'news_title'):'{% if masterCategoryName %}{{masterCategoryName}} :: {% endif %}{{newsTitle}}';

        foreach ($sqlData as $row) {
            // Make standart system call in 'export' mode
            $newsVars = news_showone(
                $row['id'],
                '',
                array(
                    'emulate' => $row,
                    'style' => 'exportVars',
                    'extractEmbeddedItems' => pluginGetVariable('turbo_yandex', 'textEnclosureEnabled') ? 1 : 0,
                    'plugin' => 'turbo_yandex',
                )
            );

            $enclosureList = array();
            // Check if Enclosure `xfields` integration is activated
            if (pluginGetVariable('turbo_yandex', 'xfEnclosureEnabled') && (true || getPluginStatusActive('xfields'))) {
                // Load (if needed XFIELDS plugin
                include_once(root."/plugins/xfields/xfields.php");
                if (is_array($xfd = xf_decode($row['xfields'])) && isset($xfd[pluginGetVariable('turbo_yandex', 'xfEnclosure')])) {
                    // Check enclosure field type
                    if ($enclosureIsImages) {
                        // images
                        if (isset($encImages[$row['id']])) {
                            $enclosureList []= '   <figure>	<img src="'.($encImages[$row['id']]['storage']?$config['attach_url']:$config['images_url']).'/'.$encImages[$row['id']]['folder'].'/'.$encImages[$row['id']]['name'].'" /></figure>';
                        }
                    } else {
                        // text
                        $enclosureList []= '   <figure>	<img src="'.$xfd[pluginGetVariable('turbo_yandex', 'xfEnclosure')].'" /></figure>';
                    }
                }
            }

            // Check if embedded items should be exported in enclosure
            if (pluginGetVariable('turbo_yandex', 'textEnclosureEnabled') && isset($newsVars['news']['embed']['images']) && is_array($newsVars['news']['embed']['images'])) {
                foreach ($newsVars['news']['embed']['images'] as $url) {
                    // Check for absolute link
                    if (!preg_match('#^http(s{0,1})\:\/\/#', $url)) {
                        $url = home . $url;
                    }
                    $enclosureList []= '   <figure>	<img src="'.$url.'" /></figure>';
                }
            }

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
            // $output .= join("\n", $enclosureList);
            $entry->short = strip_tags($newsVars['short-story'], '<p><img><iframe><br><ul><ol><li><b><strong><i><em><sup><sub><ins><del><small><big><pre></pre><abbr><u><a>');
            $entry->full = strip_tags($newsVars['full-story'], '<p><img><iframe><br><ul><ol><li><b><strong><i><em><sup><sub><ins><del><small><big><pre></pre><abbr><u><a>');

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
