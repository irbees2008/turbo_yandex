<?xml version="1.0" encoding="{{ lang['encoding'] }}"?>
<rss xmlns:yandex="http://news.yandex.ru"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:turbo="http://turbo.yandex.ru"
    version="2.0">
    <channel>
        <link>{{ link }}</link>
        <title>{{ title }}</title>
        <description>{{ description }}</description>
        <language>{{ language }}</language>

        {% for entry in entries %}

        <item turbo="true">
            <link>{{ entry.link }}</link>
            <title>{{ entry.title }}</title>
            <pubDate>{{ entry.pubDate }}</pubDate>
            <turbo:content>
                <![CDATA[
                    <header>
                        <h1>{{ entry.title }}</h1>
                        {#
                            <h2>номер телефона</h2>
                            <menu>
                                <a href="{{ link }}">Главная</a>
                                <a href="{{ link }}/catalog">Каталог</a>
                                <a href="{{ link }}/static/contacts.html">Контакты</a>
                            </menu>
                        #}
                    </header>

                    {# Отображение короткой и полной новости с предварительно вырезанными недопустимыми тегами. #}
                    {{ entry.short }} {{ entry.full }}

                    {#
                        Примеры самостоятельного вырезания тегов и укорачивания содержимого.

                        <p>{{ entry.content | striptags }}</p>
                        <p>{{ entry.content | striptags('<figure><img><p><br><ul><ol><li><b><i><u><pre></pre><a>') }}</p>
                        <p>{{ entry.content | striptags | truncateHTML(350, '...') }}</p>
                    #}

                    {#
                        Пример замены относительных ссылок на абсолютные.

                        {{ entry.full | replace({
                            'src="/': 'src="' ~ link ~ '/',
                            'src="../': 'src="' ~ link ~ '/',
                        }) }}
                    #}

                    {#
                        Примерв использования доп. полей.

                        1) Если поле имеет текстовый тип.
                        {{ entry.xfields.specification.value ? entry.xfields.specification.value : 'Характеристики не указаны' }}

                        2) Если поле имеет числовой тип.
                        {% if entry.xfields.price.value >= 0 %}
                            стоимость от {{ entry.xfields.price.value }} рублей
                        {% endif %}

                        3) Если поле представляет собой группу изображений.
                        {% for image in entry.xfields.poster.entries %}
                            <figure>
                                <img src="{{ image.url }}" alt="" />
                            </figure>
                        {% endfor %}
                    #}
                ]]>
            </turbo:content>
        </item>

        {% else %}
            {{ lang.theme.msgi_no_news }}
        {% endfor %}

    </channel>
</rss>
