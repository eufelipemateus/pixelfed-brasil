<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($urls as $url)
    <sitemap>
        <loc>{{ $url }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
    </sitemap>
    @endforeach
</sitemapindex>
