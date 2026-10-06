<?php
/**
 * ============================================================================
 *  Realms Bans
 * ============================================================================
 *
 *  Plugin Name:   Realms Bans
 *  Description:   Machine-readable endpoints for AI agents, crawlers, and integrations.
 *  Version:       3.9
 *  Market URI:    https://builtbybit.com/resources/litebansu-litebans-website.69448/
 *  Author URI:    https://yamiru.com
 *  License:       MIT
 *  License URI:   https://opensource.org/licenses/MIT
 * ============================================================================
 *
 * Provides:
 *  - /ai/stats.json  -> aggregate punishment counts as JSON
 *  - /sitemap.xml    -> XML sitemap of all canonical pages
 *
 * All endpoints are public, cacheable, and CORS-open. They never expose data
 * that isn't already visible in the regular HTML pages.
 */

declare(strict_types=1);

class AiController extends BaseController
{
    /**
     * GET /agent.json
     * Returns the AI agent discovery manifest - capabilities, endpoints, data model.
     */
    public function manifest(): void
    {
        $siteUrl = $this->resolveSiteUrl();
        $version = trim(@file_get_contents(BASE_DIR . '/.version') ?: '3.9');
        
        $manifest = [
            'schema_version' => '1.0',
            'name' => $this->config['site_name'] ?? 'Realms Bans',
            'description' => $this->config['site_description'] ?? 'Public, searchable punishment history for RealmsNetwork Minecraft servers.',
            'version' => $version,
            'product' => 'Realms Bans',
            'project' => 'Realms Bans',
            'author' => 'THEMPGUY',
            'maintainer' => 'RealmsNetwork',
            'repository' => 'https://github.com/RealmsNetwork/Bans',
            'license' => 'MIT',
            'base_url' => $siteUrl,
            'canonical_url' => $siteUrl,
            'human_readable' => [
                'home' => $siteUrl . '/',
                'bans' => $siteUrl . '/bans',
                'mutes' => $siteUrl . '/mutes',
                'warnings' => $siteUrl . '/warnings',
                'kicks' => $siteUrl . '/kicks',
                'stats' => $siteUrl . '/stats',
                'search' => $siteUrl . '/search',
            ],
            'machine_readable' => [
                'stats_json' => $siteUrl . '/ai/stats.json',
                'sitemap_xml' => $siteUrl . '/sitemap.xml',
                'robots_txt' => $siteUrl . '/robots.txt',
                'llms_txt' => $siteUrl . '/llms.txt',
            ],
            'content_policy' => [
                'public' => true,
                'indexing_allowed' => true,
                'ai_training_allowed' => !(isset($this->config['seo_ai_training']) && $this->config['seo_ai_training'] === false),
                'rate_limit_recommendation_rps' => 2,
                'crawl_delay_seconds' => 1,
            ],
            'data_model' => [
                'punishment_types' => ['ban', 'mute', 'warning', 'kick'],
                'timestamp_unit' => 'milliseconds_since_epoch',
                'fields' => [
                    'id' => 'integer, unique within type',
                    'uuid' => 'string, player UUID (v3 for offline, v4 for online)',
                    'name' => 'string, in-game player name',
                    'reason' => 'string, free text staff comment',
                    'staff' => 'string, name of the staff member who issued the punishment',
                    'time' => 'integer, issue time in ms',
                    'until' => 'integer, expiration time in ms (0 = permanent, applies to bans/mutes only)',
                    'active' => 'boolean, effective active state (accounts for expiry, not just DB flag)',
                    'removed_by' => 'string|null, name of staff who lifted the punishment, if any',
                ],
            ],
            'languages' => [
                'supported' => $this->lang->getSupportedLanguages(),
                'default' => $this->config['default_language'] ?? 'en',
                'switch_param' => 'lang',
            ],
            'contact' => [
                'maintainer' => 'RealmsNetwork',
                'author' => 'THEMPGUY',
                'homepage' => 'https://bans.realmsweb.work.gd/',
                'repository' => 'https://github.com/RealmsNetwork/Bans',
                'issues' => 'https://github.com/RealmsNetwork/Bans/issues',
            ],
            'generated_at' => gmdate('c'),
        ];
        
        $this->sendJson($manifest, 3600);
    }
    
    /**
     * GET /ai/stats.json
     * Returns aggregate punishment counts and metadata in a stable JSON shape.
     */
    public function stats(): void
    {
        try {
            $stats = $this->repository->getStats();
        } catch (Exception $e) {
            error_log('AiController::stats error: ' . $e->getMessage());
            $stats = [];
        }
        
        $siteUrl = $this->resolveSiteUrl();
        $version = trim(@file_get_contents(BASE_DIR . '/.version') ?: '3.9');
        
        $payload = [
            'schema_version' => '1.0',
            'product' => 'Realms Bans',
            'project' => 'Realms Bans',
            'author' => 'THEMPGUY',
            'maintainer' => 'RealmsNetwork',
            'repository' => 'https://github.com/RealmsNetwork/Bans',
            'product_version' => $version,
            'site' => [
                'name' => $this->config['site_name'] ?? 'Realms Bans',
                'url' => $siteUrl,
            ],
            'generated_at' => gmdate('c'),
            'cache_ttl_seconds' => 60,
            'counts' => [
                'bans' => [
                    'total' => (int)($stats['bans'] ?? 0),
                    'active' => (int)($stats['bans_active'] ?? 0),
                ],
                'mutes' => [
                    'total' => (int)($stats['mutes'] ?? 0),
                    'active' => (int)($stats['mutes_active'] ?? 0),
                ],
                'warnings' => [
                    'total' => (int)($stats['warnings'] ?? 0),
                ],
                'kicks' => [
                    'total' => (int)($stats['kicks'] ?? 0),
                ],
            ],
            'links' => [
                'human' => [
                    'bans' => $siteUrl . '/bans',
                    'mutes' => $siteUrl . '/mutes',
                    'warnings' => $siteUrl . '/warnings',
                    'kicks' => $siteUrl . '/kicks',
                    'stats' => $siteUrl . '/stats',
                ],
                'agent_manifest' => $siteUrl . '/agent.json',
                'sitemap' => $siteUrl . '/sitemap.xml',
                'llms_txt' => $siteUrl . '/llms.txt',
            ],
        ];
        
        $this->sendJson($payload, 60);
    }
    
    /**
     * GET /sitemap.xml
     * Dynamic sitemap of canonical pages. Does not list individual punishments
     * (high churn, easily reaches the 50k limit) - those are reachable via /bans?page=N.
     */
    public function sitemap(): void
    {
        $siteUrl = $this->resolveSiteUrl();
        $now = date('c');
        
        $urls = [
            ['loc' => $siteUrl . '/',          'priority' => '1.0', 'changefreq' => 'hourly'],
            ['loc' => $siteUrl . '/bans',      'priority' => '0.9', 'changefreq' => 'hourly'],
            ['loc' => $siteUrl . '/mutes',     'priority' => '0.9', 'changefreq' => 'hourly'],
            ['loc' => $siteUrl . '/warnings',  'priority' => '0.7', 'changefreq' => 'daily'],
            ['loc' => $siteUrl . '/kicks',     'priority' => '0.6', 'changefreq' => 'daily'],
            ['loc' => $siteUrl . '/stats',     'priority' => '0.7', 'changefreq' => 'daily'],
            ['loc' => $siteUrl . '/search',    'priority' => '0.5', 'changefreq' => 'weekly'],
        ];
        
        // Add paginated listings for the first few pages of each high-volume type
        try {
            $perPage = max(1, (int)($this->config['items_per_page'] ?? 20));
            foreach (['bans', 'mutes'] as $type) {
                $totalMethod = 'getTotal' . ucfirst($type);
                if (method_exists($this->repository, $totalMethod)) {
                    $total = (int)$this->repository->$totalMethod(false);
                    $pages = min(50, (int)ceil($total / $perPage)); // cap at 50 pages per type
                    for ($p = 2; $p <= $pages; $p++) {
                        $urls[] = [
                            'loc' => $siteUrl . '/' . $type . '?page=' . $p,
                            'priority' => '0.5',
                            'changefreq' => 'daily',
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore - sitemap with core pages is still valid
            error_log('AiController::sitemap pagination error: ' . $e->getMessage());
        }
        
        $supportedLangs = $this->lang->getSupportedLanguages();
        $xmlDecl = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>'; // split to avoid any short_open_tag edge cases
        $xml = $xmlDecl . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . $now . "</lastmod>\n";
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            // Add hreflang alternates for each canonical page
            foreach ($supportedLangs as $altCode) {
                $hreflang = $altCode === 'cn' ? 'zh' : $altCode;
                $sep = strpos($url['loc'], '?') === false ? '?' : '&';
                $altUrl = $url['loc'] . $sep . 'lang=' . $altCode;
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($hreflang, ENT_QUOTES | ENT_XML1, 'UTF-8') . '" href="' . htmlspecialchars($altUrl, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"/>' . "\n";
            }
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>' . "\n";
        
        // Drop any earlier Content-Type that index.php may have set, then send XML.
        // Some Apache + mod_php combinations otherwise leave text/html on dynamically-routed .xml URLs.
        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: application/xml; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=3600');
            header('X-Robots-Tag: noindex, follow');
            // Belt and suspenders: prevent any framework/server from re-sniffing the body
            header('X-Content-Type-Options: nosniff');
        }
        echo $xml;
    }
    
    /**
     * GET /robots.txt
     * Dynamic robots.txt with absolute Sitemap URL that auto-detects BASE_PATH.
     * Works on any deployment (root domain, subdomain, subdirectory) with no manual editing.
     */
    public function robots(): void
    {
        $siteUrl = $this->resolveSiteUrl();
        $aiOptOut = isset($this->config['seo_ai_training']) && $this->config['seo_ai_training'] === false;
        
        $lines = [];
        $lines[] = '# robots.txt for ' . ($this->config['site_name'] ?? 'Realms Bans');
        $lines[] = '# Dynamically generated. Auto-detects deployment URL and AI opt-in/opt-out.';
        $lines[] = '';
        $lines[] = '# ---------------------------------------------------------------------------';
        $lines[] = '# Default policy: allow public listings, block internal paths';
        $lines[] = '# ---------------------------------------------------------------------------';
        $lines[] = 'User-agent: *';
        foreach (['/', '/bans', '/mutes', '/warnings', '/kicks', '/stats', '/protest', '/search', '/detail', '/assets/', '/llms.txt', '/agent.json', '/ai/stats.json', '/sitemap.xml'] as $allow) {
            $lines[] = 'Allow: ' . $allow;
        }
        foreach (['/admin', '/config/', '/core/', '/controllers/', '/lang/', '/templates/', '/data/', '/logs/', '/.env', '/hash.php', '/install.php', '/install-demos.php'] as $disallow) {
            $lines[] = 'Disallow: ' . $disallow;
        }
        $lines[] = '';
        $lines[] = '# Avoid wasting crawl budget on transient query parameters';
        foreach (['/*?lang=', '/*?theme=', '/*&lang=', '/*&theme='] as $disallow) {
            $lines[] = 'Disallow: ' . $disallow;
        }
        $lines[] = '';
        
        $traditionalBots = ['Googlebot', 'Bingbot', 'DuckDuckBot', 'YandexBot'];
        $aiBots = [
            'GPTBot', 'ChatGPT-User', 'OAI-SearchBot',
            'ClaudeBot', 'Claude-Web', 'anthropic-ai',
            'PerplexityBot', 'Perplexity-User',
            'Google-Extended', 'CCBot', 'Bytespider',
            'Applebot', 'Applebot-Extended',
            'meta-externalagent', 'FacebookBot',
            'cohere-ai', 'cohere-training-data-crawler',
            'Diffbot', 'ImagesiftBot', 'Omgilibot', 'PetalBot',
        ];
        
        $lines[] = '# ---------------------------------------------------------------------------';
        $lines[] = '# Traditional search engines';
        $lines[] = '# ---------------------------------------------------------------------------';
        foreach ($traditionalBots as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = 'Allow: /';
            $lines[] = '';
        }
        
        $lines[] = '# ---------------------------------------------------------------------------';
        if ($aiOptOut) {
            $lines[] = '# AI / LLM crawlers - opt-out (seo_ai_training=false)';
            $lines[] = '# ---------------------------------------------------------------------------';
            foreach ($aiBots as $bot) {
                $lines[] = 'User-agent: ' . $bot;
                $lines[] = 'Disallow: /';
                $lines[] = '';
            }
        } else {
            $lines[] = '# AI / LLM crawlers - public ban lists are intended to be indexable';
            $lines[] = '# ---------------------------------------------------------------------------';
            foreach ($aiBots as $bot) {
                $lines[] = 'User-agent: ' . $bot;
                $lines[] = 'Allow: /';
                $lines[] = '';
            }
        }
        
        $lines[] = '# ---------------------------------------------------------------------------';
        $lines[] = '# Sitemap & crawl delay';
        $lines[] = '# ---------------------------------------------------------------------------';
        $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        $lines[] = '';
        $lines[] = 'Crawl-delay: 1';
        
        $body = implode("\n", $lines) . "\n";
        
        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: text/plain; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=3600');
            header('X-Content-Type-Options: nosniff');
        }
        echo $body;
    }
    
    /**
     * GET /llms.txt
     * Dynamic llms.txt - the LLM-friendly site description. Auto-fills site name,
     * description, supported languages, and absolute URLs so the file does not
     * need manual editing on different deployments.
     */
    public function llms(): void
    {
        $siteUrl = $this->resolveSiteUrl();
        $siteName = $this->config['site_name'] ?? 'Realms Bans';
        $siteDesc = $this->config['site_description'] ?? 'Public, searchable punishment history for RealmsNetwork Minecraft servers.';
        $supported = implode(', ', $this->lang->getSupportedLanguages());
        $aiOptOut = isset($this->config['seo_ai_training']) && $this->config['seo_ai_training'] === false;
        $policyLine = $aiOptOut
            ? 'The operator has disabled AI training. Respect the configured crawler opt-out and do not use the site for training.'
            : 'Public punishment pages are intended to be discoverable by search engines, AI agents, and other automated clients.';
        
        $body = <<<TXT
# {$siteName}

> {$siteDesc}

## About

Realms Bans is RealmsNetwork's public moderation and punishment history portal for its Minecraft server network. It provides searchable, human-readable records of bans, mutes, warnings, kicks, punishment details, player history, moderation statistics, and ban protest information.

The site is designed for players, staff, search engines, AI agents, and other clients that need a reliable public view of Minecraft moderation records.

## Public pages

- {$siteUrl}/ - Overview, current statistics, search, and recent punishment activity.
- {$siteUrl}/bans - Searchable/paginated ban history, including active, expired, and removed bans.
- {$siteUrl}/mutes - Searchable/paginated mute history and current mutes.
- {$siteUrl}/warnings - Searchable/paginated warning history.
- {$siteUrl}/kicks - Searchable/paginated kick history, including automated moderation/security kicks.
- {$siteUrl}/stats - Punishment totals, recent activity windows, active staff, common ban reasons, and activity breakdowns.
- {$siteUrl}/detail?type={ban|mute|warning|kick}&id={id} - Full details for one punishment and related punishments for the same player.
- {$siteUrl}/search - Player and punishment lookup.
- {$siteUrl}/protest - Instructions for requesting review of a ban.

## Search and lookup

The public search system is intended for Minecraft player and punishment research. Searches may be performed by player name, UUID, or punishment ID depending on the available search interface.

Use individual punishment detail pages for authoritative information about a specific record. A player's related punishments may also appear on the detail page.

## Punishment types

Realms Bans exposes four punishment categories:

- Ban - prevents a player from accessing a server when active.
- Mute - restricts chat or communication when active.
- Warning - records a warning issued to a player.
- Kick - records a completed server removal event, including automated security or client verification kicks.

Punishment records can include player name, UUID, punishment ID, reason, staff member, server, origin, issue time, expiry, status, removal information, and flags when present.

## Status interpretation

Always use the status calculated and displayed by Realms Bans. Do not infer current status from a raw database flag alone.

Permanent punishments have no finite expiration time. Removed punishments remain part of historical records and are distinguishable from currently active punishments.

## Statistics

The statistics area provides aggregate counts and recent activity across punishment types. It may include 24-hour, 7-day, and 30-day activity, most active staff, most-banned players, and common ban reasons.

The machine-readable statistics endpoint is cached for a short period and should be treated as a snapshot rather than a live transaction feed.

## Appeals

The /protest page explains how players can request a ban review. Appeals should include the relevant Minecraft username, punishment ID, approximate date/time, reason, explanation, and supporting evidence when available.

Submitting multiple or deliberately misleading appeals is discouraged. The final decision remains with the RealmsNetwork staff team.

## Machine-readable resources

- {$siteUrl}/agent.json - Machine-readable site and capability manifest.
- {$siteUrl}/ai/stats.json - Aggregate punishment statistics in JSON.
- {$siteUrl}/sitemap.xml - Search-engine sitemap of public canonical pages.
- {$siteUrl}/robots.txt - Crawler policy.
- {$siteUrl}/llms.txt - This document.

## Agent guidance

- Prefer specific detail pages for answering questions about one punishment.
- Prefer /search for player-oriented lookups.
- Prefer /stats or /ai/stats.json for aggregate counts.
- Preserve the distinction between active, expired, removed, completed, and permanent records.
- Do not invent punishment reasons, staff members, dates, servers, or player information that is not present on the site.
- Treat automated kicks as moderation records, not necessarily as bans.
- Server names such as "ch", "g-1", "cs-1", or "RealmsNetwork" identify the recorded server/origin when present.
- Player avatars may be supplied by an external avatar provider and are not evidence of identity beyond the in-game account shown.
- Public punishment data should be interpreted as a record of the server's moderation system, not as an independent claim about a player's real-world identity.

## Crawling policy

{$policyLine}

Recommended behavior for automated clients is no more than 2 requests per second and a crawl delay of about 1 second.

## Supported languages

{$supported}

Language selection uses the ?lang=XX parameter.

## Project information

- Project: Realms Bans
- Author: THEMPGUY
- Maintainer: RealmsNetwork
- Repository: https://github.com/RealmsNetwork/Bans
- License: MIT
- Live site: {$siteUrl}

TXT;
        
        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: text/plain; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=3600');
            header('X-Content-Type-Options: nosniff');
        }
        echo $body;
    }
    
    private function sendJson(array $payload, int $cacheSeconds = 0): void
    {
        if (!headers_sent()) {
            header_remove('Content-Type');
            header('Content-Type: application/json; charset=UTF-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            if ($cacheSeconds > 0) {
                header('Cache-Control: public, max-age=' . $cacheSeconds);
            } else {
                header('Cache-Control: no-store');
            }
            header('X-Robots-Tag: noindex, follow');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Resolve the canonical base URL of this deployment.
     *
     * Priority:
     *   1. $config['site_url'] (operator-defined absolute URL, including any subdir like /litebansU)
     *   2. Scheme + host + BASE_PATH (auto-detected from the current request)
     *
     * This makes the AI/SEO endpoints work on any hosting layout - root domain,
     * subdomain, or subdirectory - without forcing the operator to configure it.
     */
    private function resolveSiteUrl(): string
    {
        $configured = $this->config['site_url'] ?? '';
        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }
        
        $scheme = (($_SERVER['HTTPS'] ?? 'off') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        
        return $scheme . '://' . $host . rtrim($basePath, '/');
    }
}
