# Realms Bans

Public punishment history and moderation transparency for the RealmsNetwork Minecraft network.

## What it is

Realms Bans is a self-hosted PHP web application that turns server punishment records into a fast, searchable public website.

Players can inspect ban, mute, warning, and kick history without needing staff access. Staff can use the authenticated administration area to manage site configuration and moderation data where enabled.

The public interface is designed around useful records rather than a social profile system. It exposes Minecraft usernames, UUIDs when configured, punishment reasons, staff names, servers, dates, expiration information, status, and related punishment history.

## Public features

- Public ban history with active and historical records
- Public mute history
- Public warning history
- Public kick history
- Individual punishment detail pages
- Player and punishment lookup
- Aggregate moderation statistics
- Recent activity windows
- Most active staff and common ban reason statistics
- Ban protest and review instructions
- Light and dark themes
- Responsive desktop and mobile layout
- 25 selectable interface languages
- Search-engine metadata, canonical URLs, hreflang, Open Graph, and structured data
- llms.txt, agent.json, JSON statistics, sitemap, and crawler policy endpoints
- Configurable AI-training/crawler opt-out behavior
- Optional password, Google OAuth, and Discord OAuth administration
- CSRF protection, prepared database statements, rate limiting, secure sessions, and security headers

## Supported languages

English, العربية, Čeština, Deutsch, Ελληνικά, Español, Français, Magyar, Italiano, 日本語, Polski, Română, Русский, Slovenčina, Srpski, Türkçe, 中文 (简体), Nederlands, Português, 한국어, Українська, Tiếng Việt, Bahasa Indonesia, Svenska, and Norsk.

Select a language using ?lang=XX.

## Machine-readable resources

The public site publishes information intended for automated clients:

- /llms.txt - Human-readable description and agent guidance
- /agent.json - Machine-readable project, endpoint, schema, language, and crawl metadata
- /ai/stats.json - Aggregate punishment counts
- /sitemap.xml - Public page discovery
- /robots.txt - Crawler policy

Automated clients should prefer stable discovery endpoints and avoid unnecessary repeated requests.

## Requirements

- PHP 8.0+
- PDO with MySQL or PostgreSQL support
- mbstring
- intl
- curl
- json
- A MySQL 5.7+ / MariaDB 10.3+ compatible database, or PostgreSQL when configured
- Apache with rewrite support or Nginx

## Configuration

Copy the example environment file and fill in the database, site, authentication, contact, and SEO values.

The example configuration is intentionally safe for public source control and does not contain credentials.

Important settings include:

DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_DRIVER, TABLE_PREFIX
 SITE_NAME, SITE_URL, SITE_DESCRIPTION, SITE_AUTHOR, SITE_KEYWORDS
 DEFAULT_THEME, DEFAULT_LANGUAGE, SHOW_PLAYER_UUID
 PROTEST_DISCORD, PROTEST_EMAIL, PROTEST_FORUM
 SEO_AI_TRAINING, SEO_ENABLE_SCHEMA, SEO_ORGANIZATION_NAME
 ADMIN_ENABLED, ADMIN_PASSWORD, ALLOW_PASSWORD_LOGIN

## Security

Use HTTPS in production.

Do not commit a real .env file or OAuth credentials. Remove installation and credential-generation utilities when they are no longer needed.

Use a database account with only the permissions required by the application.

Keep PHP, the operating system, web server, and dependencies updated.

## Project identity

**Project:** Realms Bans  
**Author:** THEMPGUY  
**Maintainer:** RealmsNetwork  
**Repository:** https://github.com/RealmsNetwork/Bans/  
**Website:** https://bans.realmsweb.work.gd/  
**License:** MIT

## Design

The public UI is deliberately restrained: dark surfaces, subtle purple accents, clear typography, compact tables, and responsive layouts. The goal is to make a large punishment list easy to scan without turning the site into a dashboard full of decorative cards.

Built for the RealmsNetwork Minecraft community.
