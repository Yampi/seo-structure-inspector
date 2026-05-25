=== SEO Structure Inspector ===
Contributors:      tecnicoelho
Tags:              seo, schema, core web vitals, aeo, geo, llm seo, structured data, technical seo
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.1
Stable tag:        1.6.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Advanced SEO analysis: HTML structure, schema validation, AEO/GEO optimization,
Core Web Vitals, and AI visibility checks for modern search engines.

== Description ==

SEO Structure Inspector is a technical SEO audit tool built for developers,
agencies, and advanced WordPress users who need more than basic meta tag checks.

= Core Features =

* **HTML Structure Analysis** — Heading hierarchy, semantic issues, content structure
* **Schema.org Validator** — JSON-LD and microdata validation with rich result checks
* **Meta Tags Inspector** — Title, description, OG, Twitter Cards, canonical, robots
* **Core Web Vitals** — Mobile and desktop scores via PageSpeed API
* **AEO Optimization** — Answer Engine Optimization for featured snippets and AI answers
* **GEO / LLM Visibility** — Checks for AI crawler access, llms.txt, and AI-readiness
* **Links Analyzer** — Internal/external links, broken link detection, image alt text
* **Readability Analysis** — Content readability scoring adapted for SEO context
* **Keyword Analyzer** — Keyword presence, density, and positioning checks
* **Score History** — Track SEO score evolution over time per post
* **Batch Analysis** — Analyze multiple URLs at once from the dashboard
* **REST API** — Integrate analysis into CI/CD and external workflows
* **Scheduled Analysis** — Automatic periodic re-analysis with email alerts

= Why SEO Structure Inspector? =

Most SEO plugins focus on content. SEO Structure Inspector focuses on technical
structure — the layer that determines how search engines and AI systems parse,
understand, and rank your content.

Particularly relevant for:
- Sites targeting AI-powered search (Perplexity, ChatGPT, Gemini)
- Technical SEO audits
- Schema and structured data validation
- Agencies managing multiple sites

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`
2. Activate through the Plugins menu
3. Go to SEO Inspector in the admin menu
4. Optionally add a Google PageSpeed API key in Settings for Core Web Vitals

== Frequently Asked Questions ==

= Do I need an API key? =
No. All features work without an API key. A Google PageSpeed API key is optional
and only required for Core Web Vitals analysis.

= Does it work with any theme? =
Yes. SEO Structure Inspector analyzes the rendered HTML of any URL, regardless
of theme or page builder.

= Is it compatible with Yoast, RankMath, or other SEO plugins? =
Yes. It complements existing SEO plugins by providing structural analysis
that meta-focused plugins do not cover.

= Can I analyze URLs outside my WordPress site? =
Yes. You can analyze any public URL from the admin dashboard, making it useful
for competitor analysis and client audits.

= What data does the plugin store? =
The plugin stores analysis results in WordPress transients (temporary cache) and
optionally in post meta for history tracking. No data is sent to external services
except for Google PageSpeed API when Core Web Vitals analysis is enabled.

== Changelog ==

= 1.6.0 =
* Introduced a dedicated AI Recommendations Panel ("TIP IA") showing smart, actionable technical tips.
* Implemented an extensible AIManager and AIProviderInterface architecture to support custom and external AI engines in the future.
* Designed a premium, modern, and interactive glassmorphic UI for recommendations.
* Added AJAX handler to fetch recommendations asynchronously.

= 1.5.0 =
* Introduced a dedicated Reversion Engine allowing granular or bulk rolls-back of applied SEO metadata, custom URL overrides, and structural fixes.
* Implemented Single-Click Bulk Auto-Fixes to automatically resolve multiple issues simultaneously from the central post/page list.
* Solved CSS alignment issues in the admin panel by widening the diagnostic problem container layout and fixing inline display of action triggers.

= 1.4.0 =
* Implemented dynamic Auto-Fix visibility, displaying the "Auto-fix" button only when an automated solution is available.
* Integrated WordPress Post Quick Wins to execute multiple SEO optimizations on native posts and pages directly from the central dashboard.
* Added real-time problem search and priority filter chips (All, Critical, Warnings) to the dashboard for faster diagnostics.
* Polished overall dark theme layouts, CSS variables, and alignment across the admin dashboard.

= 1.3.0 =
* Completely redesigned the exported SEO report with a premium, enterprise-grade visual identity.
* Added radial SVG gauge for global score and mini-gauge indicators for each analysis category.
* Implemented dual-theme system (Dark/Light) with interactive toggle button and system preference detection.
* Replaced text emojis with clean SVG vector icons for pass, warning, and fail states.
* Added horizontal performance bars to Core Web Vitals metrics table.
* Redesigned badges as pill-shaped elements with semantic color borders.
* Upgraded typography to Outfit (headings) + Inter (body) for a modern, premium feel.
* Optimized print/PDF output with dedicated light-theme, high-contrast @media print styles and page-break control.

= 1.2.0 =
* Added Sitemap Explorer & Batch Analyzer to scan wp-sitemap.xml and process URLs sequentially in the background.
* Implemented SPA-style view routing and localStorage double-state caching for persistent navigation.
* Added "Detail & Fix" hot restoration to deep-analyze discovered pages with one click.

= 1.1.0 =
* Added dynamic structural fixer engine (Output Buffering & DOM Rewriting) with wrap and replace strategies.
* Implemented Global Theme System (Light, Dark, Auto-System) with CSS variables.
* Completely removed inline styles from Glossary page and refactored Settings to premium glassmorphic theme.

= 1.0.0 =
* Initial public release
* HTML structure, schema, meta tags, CWV, AEO, GEO, links analysis
* Score history per post
* Batch analysis dashboard
* REST API endpoint
* Scheduled analysis with email alerts

== Upgrade Notice ==

= 1.6.0 =
Dedicated AI Recommendations Panel ("TIP IA") with an extensible AI provider architecture, real-time AJAX fetching, and premium UI/UX.

= 1.5.0 =
Granular rollback reversion engine, single-click bulk auto-fixes, and refined CSS responsive button layouts.

= 1.4.0 =
Premium UX updates, dynamic Auto-Fix visibility, instant problem filtering/search, and post quick-wins integration.

= 1.3.0 =
Premium redesign of the exported SEO report with SVG gauges, dual-theme toggle, vector icons, and optimized PDF output.

= 1.2.0 =
Added Sitemap Explorer & Batch Analyzer with persistent localStorage caching.

= 1.1.0 =
Added structural auto-fixer and global dark/light/system theme options.

= 1.0.0 =
Initial release.

