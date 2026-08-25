<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * Public indexable storefront URLs (excludes admin, cart, checkout, account, auth tokens).
     */
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $urls = [];

        foreach ($this->staticPaths() as $path => $priority) {
            $urls[] = [
                'loc' => $base.$path,
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $priority,
            ];
        }

        Category::query()
            ->catalog()
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->each(function (Category $category) use (&$urls, $base): void {
                $urls[] = [
                    'loc' => $base.'/menu/'.$category->slug,
                    'lastmod' => $this->atom($category->updated_at),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            });

        Product::query()
            ->catalog()
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->each(function (Product $product) use (&$urls, $base): void {
                $urls[] = [
                    'loc' => $base.'/product/'.$product->slug,
                    'lastmod' => $this->atom($product->updated_at),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        $xml = $this->toXml($urls);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function staticPaths(): array
    {
        return [
            '/' => '1.0',
            '/menu' => '0.9',
            '/menu-book' => '0.7',
            '/offers' => '0.8',
            '/order' => '0.8',
            '/about' => '0.6',
            '/gallery' => '0.6',
            '/reviews' => '0.6',
            '/contact' => '0.6',
            '/locations' => '0.7',
            '/login' => '0.3',
            '/register' => '0.3',
        ];
    }

    private function atom(?Carbon $value): string
    {
        return ($value ?? now())->toAtomString();
    }

    /**
     * @param  list<array{loc: string, lastmod: string, changefreq: string, priority: string}>  $urls
     */
    private function toXml(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.e($url['loc']).'</loc>';
            $lines[] = '    <lastmod>'.e($url['lastmod']).'</lastmod>';
            $lines[] = '    <changefreq>'.e($url['changefreq']).'</changefreq>';
            $lines[] = '    <priority>'.e($url['priority']).'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }
}
