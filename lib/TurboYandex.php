<?php

namespace Plugins;

// Исключения.
use RuntimeException;

// Базовые расширения PHP.
use stdClass;
use DateTime;

/**
 * RSS поток для Yandex Turbo.
 */
class TurboYandex
{
    /**
     * Номер версии плагина.
     * @const string
     */
    const VERSION = '0.5.1';

    /**
     * Идентификатор плагина.
     * @var string
     */
    protected $plugin = 'turbo_yandex';

    /**
     * Максимальное количество элементов в каждой ленте.
     * @var int
     */
    protected $countItems = 200;

    /**
     * Извлекать URL-адреса изображений из текста новости.
     * @var bool
     */
    protected $extractImages;

    /**
     * Текущая страница ленты.
     * @var int
     */
    protected $page = 1;

    /**
     * Текущая категория страницы ленты.
     * @var stdClass
     */
    protected $category;

    /**
     * Создать экземпляр плагина.
     */
    public function __construct(array $params = [])
    {
        $this->configure($params);
    }

    /**
     * Получить номер версии плагина.
     * @return string
     */
    public function version()
    {
        return self::VERSION;
    }

    protected function configure(array $params)
    {
        // Сначала зададим настройки из плагина.
        $this->countItems = setting($this->plugin, 'countItems', 200);
        $this->extractImages = setting($this->plugin, 'extractImages', false);

        // Теперь зададим переданные настройки.
        if (isset($params['page']) && is_numeric($params['page'])) {
            $this->page = (int) $params['page'];
        }

        $categories = catz();

        if (isset($params['category']) && array_key_exists($params['category'], $categories)) {
            $this->category = (object) $categories[$params['category']];
        }
    }

    protected function cacheFilename()
    {
        $cacheFilename = $this->plugin;
        $cacheFilename .= config('theme', 'default');
        $cacheFilename .= config('home_url', home);
        $cacheFilename .= config('default_lang', 'ru');
        $cacheFilename .= $this->countItems;
        $cacheFilename .= $this->extractImages;
        $cacheFilename .= $this->page;

        if ($this->category instanceof stdClass) {
            $cacheFilename .= $this->category->id;
        }

        return md5($cacheFilename).'.txt';
    }

    public function generate()
    {
        // Generate output
        $entries = [];

        // Fetch SQL record
        $where = "where approve = 1";

        if ($this->category instanceof stdClass) {
            $catid = (int) $this->category->id;

            $where .= " AND `catid` REGEXP '[[:<:]](".$catid.")[[:>:]]'";
        }

        $query = "select * from ".prefix."_news ".$where." order by id asc limit ".$this->countItems;
        $sqlData = database()->select($query);

        foreach ($sqlData as $row) {
            // Make standart system call in 'export' mode
            $newsVars = news_showone($row['id'], null, [
                'emulate' => $row,
                'style' => 'exportVars',
                'extractEmbeddedItems' => setting($this->plugin, 'extract_images', false),
                'plugin' => $this->plugin,

            ]);

            $entry = new stdClass;
            $entry->id = $row['id'];
            $entry->link = newsGenerateLink($row, false, 0, true);
            $entry->pubDate = date(DateTime::RFC822, $row['postdate']);
            $entry->title = $row['title'];

            $entry->content = $newsVars['short-story'].' '.$newsVars['full-story'];
            $entry->short = $this->stripTags($newsVars['short-story']);
            $entry->full = $this->stripTags($newsVars['full-story']);

            $entry->images = $newsVars['news']['embed']['images'] ?? [];

            if (getPluginStatusActive('xfields')) {
                $entry->xfields = $newsVars['p']['xfields'];
            }

            $entry->category = $newsVars['masterCategory'];
            $entry->categories = $newsVars['category'];

            $entries[] = $entry;
        }

        return $this->render([
            'link' => config('home_url', home),
            'title' => config('home_title', engineName),
            'description' => config('description', null),
            'language' => config('default_lang', 'ru'),
            'entries' => $entries,

        ]);
    }

    public function cachedContent()
    {
        return cacheRemember($this->plugin, $this->cacheFilename(), function () {
            return $this->generate();
        });
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
            ], $this->plugin,
            setting($this->plugin, 'localsource', 1)
        );

        return view($tpath['channel'].'channel.tpl', $vars);
    }
}
