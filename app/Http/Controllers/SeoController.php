<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $sitemap = url('/sitemap.xml');
        $lines = [
            'User-agent: *',
            'Disallow: /questionnaires',
            'Disallow: /play',
            'Disallow: /logout',
            '',
            'Sitemap: '.$sitemap,
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $entries = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/faq'), 'priority' => '0.85'],
            ['loc' => url('/terms'), 'priority' => '0.85'],
            ['loc' => url('/privacy'), 'priority' => '0.9'],
            ['loc' => url('/login'), 'priority' => '0.5'],
            ['loc' => url('/register'), 'priority' => '0.5'],
            ['loc' => url('/forgot-password'), 'priority' => '0.3'],
        ];

        $lastmod = now()->toAtomString();
        $urlElements = '';
        foreach ($entries as $entry) {
            $loc = e($entry['loc']);
            $priority = e($entry['priority']);
            $urlElements .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= $urlElements;
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
