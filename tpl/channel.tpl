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

                    {# ����������� �������� � ������ ������� � �������������� ����������� ������������� ������. #}
                    {{ entry.short }} {{ entry.full }}

                    {% for image in entry.images %}
                        <figure>
                            <img src="{{ image.url }}" alt="" />
                        </figure>
                    {% endfor %}

                    {#
                        ������� ���������������� ��������� ����� � ������������ �����������.

                        <p>{{ entry.content | striptags }}</p>
                        <p>{{ entry.content | striptags('<figure><img><p><br><ul><ol><li><b><i><u><pre></pre><a>') }}</p>
                        <p>{{ entry.content | striptags | truncateHTML(350, '...') }}</p>
                    #}

                    {#
                        ������ ������ ������������� ������ �� ����������.

                        {{ entry.full | replace({
                            'src="/': 'src="' ~ link ~ '/',
                            'src="../': 'src="' ~ link ~ '/',
                        }) }}
                    #}

                    {#
                        ������� ������������� ���. �����.

                        1) ���� ���� ����� ��������� ���.
                        {{ entry.xfields.specification.value ? entry.xfields.specification.value : '�������������� �� �������' }}

                        2) ���� ���� ����� �������� ���.
                        {% if entry.xfields.price.value >= 0 %}
                            ��������� �� {{ entry.xfields.price.value }} ������
                        {% endif %}

                        3) ���� ���� ������������ ����� ������ �����������.
                        {% for image in entry.xfields.poster.entries %}
                            <figure>
                                <img src="{{ image.url }}" alt="" />
                            </figure>
                        {% endfor %}
                    #}

                    {#
                        ������ ����������� ������� ��������� � ������ ��������� �������.

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
