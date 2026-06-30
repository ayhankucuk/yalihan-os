{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
    <channel>
        <title>{{ config('app.name') }} Blog</title>
        <atom:link href="{{ url('blog/rss') }}" rel="self" type="application/rss+xml" />
        <link>{{ url('/') }}</link>
        <description>YalıhanAI Blog Yazıları</description>
        <language>tr-TR</language>
        <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
        @foreach($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ route('blog.show', $post->slug) }}</link>
                <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
                <dc:creator>{{ $post->author->name ?? 'Admin' }}</dc:creator>
                <guid isPermaLink="false">{{ $post->id }}</guid>
                <description><![CDATA[{!! Str::limit(strip_tags($post->content), 200) !!}]]></description>
                <content:encoded><![CDATA[{!! $post->content !!}]]></content:encoded>
            </item>
        @endforeach
    </channel>
</rss>
