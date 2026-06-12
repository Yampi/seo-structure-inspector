=== Baloa Structure Auditor for SEO ===
Contributors:      bbaloa
Donate link:       https://paypal.me/brianbaloa
Tags:              seo, schema, structured-data, technical-seo, core-web-vitals
Requires at least: 6.0
Tested up to:      7.0
Requires PHP:      8.1
Stable tag:        2.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Advanced SEO analysis: HTML structure, schema validation, AEO/GEO optimization,
Core Web Vitals, and AI visibility checks for modern search engines.

== Description ==

Baloa Structure Auditor for SEO is a technical SEO audit tool built for developers,
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

= Why Baloa Structure Auditor for SEO? =

Most SEO plugins focus on content. Baloa Structure Auditor for SEO focuses on technical
structure — the layer that determines how search engines and AI systems parse,
understand, and rank your content.

Particularly relevant for:
- Sites targeting AI-powered search (Perplexity, ChatGPT, Gemini)
- Technical SEO audits
- Schema and structured data validation
- Agencies managing multiple sites

= Privacy, Local Execution & External Connections =

We highly value your data privacy. The advanced AI Recommendations Panel ("TIP IA") operates entirely locally by default on your WordPress server using our pre-configured expert diagnostic matrices and the Bookman local catalog database. No data is sent to external services under this default configuration.

For advanced features, the plugin offers optional integration with external AI cloud providers (OpenAI GPT-4o, Anthropic Claude 3.5 Sonnet, and Google Gemini 1.5 Pro). If you choose to configure these providers with your own API keys, outgoing HTTP requests will be performed. Only the plain-text content of the post or page being analyzed is transmitted to these APIs for analysis. No database credentials, admin details, passwords, or personal user data are ever sent.

To provide its complete auditing capabilities, the plugin may perform outgoing HTTP requests to the following external services:
1. **Google PageSpeed Insights API**: Used (optionally, only if PageSpeed API key is set or requested) to query Core Web Vitals performance scores.
2. **Google Web Cache**: Used strictly as a fallback mechanism to fetch the public HTML structure of the target URL when direct local requests are blocked by firewalls or security plugins. No personal data or credentials are sent.
3. **OpenAI API**: Used optionally if OpenAI API key is configured by the admin to fetch premium GPT-4o recommendations. (Privacy Policy: https://openai.com/policies/privacy-policy)
4. **Anthropic Claude API**: Used optionally if Claude API key is configured by the admin to fetch Claude 3.5 Sonnet recommendations. (Privacy Policy: https://www.anthropic.com/legal/privacy)
5. **Google Gemini API**: Used optionally if Gemini API key is configured by the admin to fetch Gemini 1.5 Pro recommendations. (Privacy Policy: https://policies.google.com/privacy)

All operations are transparent, public, and do not require third-party registration or accounts.

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
Yes. Baloa Structure Auditor for SEO analyzes the rendered HTML of any URL, regardless
of theme or page builder.

= Is it compatible with Yoast, RankMath, or other SEO plugins? =
Yes. It complements existing SEO plugins by providing structural analysis
that meta-focused plugins do not cover.

= Can I analyze URLs outside my WordPress site? =
Yes. You can analyze any public URL from the admin dashboard, making it useful
for competitor analysis and client audits.

= What data does the plugin store? =
The plugin stores analysis results in WordPress transients (temporary cache) and optionally in post meta for history tracking. No personal user data, admin credentials, or API keys are ever transmitted externally.

= Does the plugin make external API connections? =
Yes, but only for specific, transparent operations:
1. Google PageSpeed API: When Core Web Vitals analysis is enabled, the target URL is sent to Google's public API to retrieve performance scores.
2. Local AI Recommendations (TIP IA): By default, all recommendations from our three virtual experts (UI/UX, SEO-GEO-AEO, and WordPress Architect) are computed 100% LOCALLY on your server.
3. Optional Cloud AI Services: If configured by the administrator, the plugin connects to OpenAI, Anthropic Claude, or Google Gemini to fetch dynamic recommendations, transmitting only the plain text of the post/page being analyzed.
4. Target URL Fetching: The plugin fetches the HTML of target URLs (using standard WordPress HTTP APIs) to audit and evaluate their structures locally.

== Changelog ==

= 2.0.0 =
* Restructured codebase using Clean Architecture & Domain-Driven Design (DDD) patterns for better maintainability and decoupled layers.
* Introduced GEO Analyzer to audit and optimize visibility for Generative Engine Optimization (GEO/LLM).
* Modularized AJAX handlers into granular specialized components for improved security and performance.
* Added telemetry monitoring and dynamic Schema.org (JSON-LD) generation capabilities.
* Hardened security across all administration screens, forms, and AJAX endpoints via strict sanitization, output escaping, and nonces.
* Expanded unit tests and integration tests to verify new architectural components.

= 1.9.0 =
* Renamed main plugin file to baloa-structure-auditor-seo.php for WordPress.org compliance.
* Extracted inline JavaScript and CSS from Settings, Glossary, and Reversion panels into separate enqueued asset files.
* Externalized action plan styles (action-plan.css/js) and PDF report styles (pdf-report.css) to optimize asset loading.
* Standardized text-domain translations to 'baloa-structure-auditor-seo' globally across all classes, AJAX endpoints, and templates.
* Hardened nonces, request validation, and localized parameters inside admin panels.

= 1.8.0 =
* Compliance updates for WordPress Plugin Check standards.
* Sanitized input variables using filter_input() across all AJAX handlers.
* Hardened data escaping, output sanitization, and DB queries across administrative panels.
* Refactored system error logs to use a secure, centralized logging infrastructure.
* Improved cleanup during plugin uninstallation.

= 1.7.0 =
* Standardized text domain from 'seo-si' to 'baloa-structure-auditor-seo' for full compatibility.
* Implemented transient caching for scheduled posts in SchedulerService to boost performance.
* Strengthened Ajax Handlers security, nonces, and capability checks.
* Added translators notes and positional parameter support for i18n translations.
* Added strict output escaping and sanitization in administration templates and setup/teardown hooks.
* Ensured secure option and transient cleaning on uninstall.php.

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

= 2.0.0 =
Major upgrade implementing Clean Architecture, GEO analysis, modular AJAX handlers, and enhanced security hardening.

= 1.9.0 =
WordPress.org standards compliance: main file renaming, extraction of inline assets to separate files, global text-domain standardization, and improved script security.

= 1.8.0 =
WordPress Plugin Check standards compliance, enhanced input sanitization, and administrative panel security hardening.

= 1.7.0 =
Security hardening, text domain standardization, translation improvements, and transient query caching in the scheduling engine.

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

== Screenshots ==

1. Main Dashboard / General Overview: A comprehensive diagnostic panel displaying the global SEO score, readability analysis, AI/LLM visibility checks, and a real-time list of prioritized issues with single-click auto-fixes.
2. Sitemap Explorer & Batch Analyzer: A bulk diagnostic interface that discovers pages via the wp-sitemap.xml, performs sequential background analyses with an interactive progress bar, and exports results to CSV.
3. AI Recommendations Panel ("TIP IA"): Displays collaborative, actionable technical recommendations from a virtual expert group (UI-UX, Technical SEO, and WordPress Architect) with step-by-step implementation guides.
4. Changes & Reversion Control Panel: A secure management interface that logs all active optimizations, letting you perform granular single-click rollbacks or execute a bulk database purge to safely restore the site to its original state.


== External Services ==

This plugin relies on the following third-party services to perform advanced audits and generate AI recommendations:

1. Google PageSpeed Insights API (https://www.googleapis.com/pagespeedonline/v5/runPagespeed)
   - What is sent: The analyzed page URL is sent to Google's API. If configured, an optional API Key is sent to bypass default rate limits.
   - Purpose: To retrieve performance, accessibility, best practices, and SEO scores (Core Web Vitals).
   - Terms of Use: https://developers.google.com/speed/docs/insights/v5/get-started
   - Privacy Policy: https://policies.google.com/privacy

2. Google Web Cache (https://webcache.googleusercontent.com/search)
   - What is sent: The target URL (safely rawurlencoded) is sent to query Google's public cache if direct HTML fetching fails or gets blocked by WAFs.
   - Purpose: Fallback mechanism to parse HTML structure when local server fetches are blocked.
   - Privacy Policy: https://policies.google.com/privacy

3. OpenAI API (https://api.openai.com/v1/chat/completions)
   - What is sent: The analyzed URL and local technical diagnostic scores (no private database credentials, admin details, passwords, or personal user data are ever sent).
   - Purpose: Used optionally to fetch premium GPT-4o recommendations if configured by the administrator with their own API key.
   - Terms of Use: https://openai.com/policies/terms-of-use
   - Privacy Policy: https://openai.com/policies/privacy-policy

4. Anthropic Claude API (https://api.anthropic.com/v1/messages)
   - What is sent: The analyzed URL and local technical diagnostic scores (no private database credentials, admin details, passwords, or personal user data are ever sent).
   - Purpose: Used optionally to fetch premium Claude 3.5 Sonnet recommendations if configured by the administrator with their own API key.
   - Terms of Use: https://www.anthropic.com/legal/terms
   - Privacy Policy: https://www.anthropic.com/legal/privacy

5. Google Gemini API (https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent)
   - What is sent: The analyzed URL and local technical diagnostic scores (no private database credentials, admin details, passwords, or personal user data are ever sent).
   - Purpose: Used optionally to fetch premium Gemini 1.5 Pro recommendations if configured by the administrator with their own API key.
   - Terms of Use: https://developers.google.com/terms
   - Privacy Policy: https://policies.google.com/privacy


