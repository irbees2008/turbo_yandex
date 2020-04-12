<?xml version="1.0" encoding="{{ lang['encoding'] }}"?>
<rss xmlns:yandex="http://news.yandex.ru"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:turbo="http://turbo.yandex.ru"
    version="2.0">
    <channel>
        <link>{{ link }}</link>
        <title>{{ title }}</title>
        <description>{{ description }}</description>
        {# <turbo:analytics id="88888888" type="Yandex"></turbo:analytics> #}
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
                                <a href="{{ link }}">√лавна€</a>
                                <a href="{{ link }}/catalog"> аталог</a>
                                <a href="{{ link }}/static/contacts.html"> онтакты</a>
                            </menu>
                        #}
                    </header>

                    {# ќтображение короткой и полной новости с предварительно вырезанными недопустимыми тегами. #}
                    {{ entry.short }} {{ entry.full }}

                    {% for image in entry.images %}
                        <figure>
                            <img src="{{ image.url }}" alt="" />
                        </figure>
                    {% endfor %}

                    {#
                        ѕримеры самосто€тельного вырезани€ тегов и укорачивани€ содержимого.

                        <p>{{ entry.content | striptags }}</p>
                        <p>{{ entry.content | striptags('<figure><img><p><br><ul><ol><li><b><i><u><pre></pre><a>') }}</p>
                        <p>{{ entry.content | striptags | truncateHTML(350, '...') }}</p>
                    #}

                    {#
                        ѕример замены относительных ссылок на абсолютные.

                        {{ entry.full | replace({
                            'src="/': 'src="' ~ link ~ '/',
                            'src="../': 'src="' ~ link ~ '/',
                        }) }}
                    #}

                    {#
                        ѕримерв использовани€ доп. полей.

                        1) ≈сли поле имеет текстовый тип.
                        {{ entry.xfields.specification.value ? entry.xfields.specification.value : '’арактеристики не указаны' }}

                        2) ≈сли поле имеет числовой тип.
                        {% if entry.xfields.price.value >= 0 %}
                            стоимость от {{ entry.xfields.price.value }} рублей
                        {% endif %}

                        3) ≈сли поле представл€ет собой группу изображений.
                        {% for image in entry.xfields.poster.entries %}
                            <figure>
                                <img src="{{ image.url }}" alt="" />
                            </figure>
                        {% endfor %}
                    #}

                    {#
                        ѕример отображени€ главной категории и дерева категорий новости.

                        {{ entry.category }} {{ entry.categories }}
                    #}
                ]]>
            </turbo:content>
        </item>

        {% else %}
            {{ lang.theme.msgi_no_news }}
        {% endfor %}

    </channel>
</rss>
