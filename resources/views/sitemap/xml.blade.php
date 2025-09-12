<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($urls as $url)
        <url>
            <loc>{{ $url }}</loc>
            <lastmod>{{ now()->toDateString() }}</lastmod>
            <changefreq>{{ $frequency }}</changefreq>
            <priority>{{ $priority }}</priority>
        </url>
    @endforeach
</urlset>
