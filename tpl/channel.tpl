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
                            <h2>����� ��������</h2>
                            <menu>
                                <a href="{{ link }}">�������</a>
                                <a href="{{ link }}/catalog">�������</a>
                                <a href="{{ link }}/static/contacts.html">��������</a>
                            </menu>
                        #}
                    </header>

                    {# ����������� �������� � ������ ������� #}
                    {{ entry.short }} {{ entry.full }}

                    {#
                        ������ ��������� ����� � ������������ �����������

                        <p>{{ entry.short | striptags | truncateHTML(350, '...') }}</p>
                    #}

                    {#
                        ������ ������ ������������� ������ �� ����������

                        {{ entry.full | replace({
                            'src="/': 'src="' ~ link ~ '/',
                            'src="../': 'src="' ~ link ~ '/',
                        }) }}
                    #}

                    {#
                        ������ ������ ��������� �����������

                        {% for image in entry.xfields.poster.entries %}
                            <img src="{{ image.url }}" alt="">
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
