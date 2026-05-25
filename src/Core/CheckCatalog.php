<?php

namespace SEOSI\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class CheckCatalog {
    public static function present( array $check, callable $t ): ?array {
        $id       = (string) ( $check['id'] ?? '' );
        $severity = (string) ( $check['severity'] ?? '' );
        $context  = is_array( $check['context'] ?? null ) ? $check['context'] : [];

        if ( $id === '' ) return null;

        // Query Bookman glossary registry for a unified explanation
        $term = \SEOSI\Core\Bookman::get_term( $id );
        if ( $term ) {
            $title = $t( $term['name'] );
            $why   = $t( $term['why_it_matters'] );
            $how   = [ $t( $term['recommendation'] ) ];
            
            // Format dynamic message based on context or severity
            if ( ! empty( $check['message'] ) ) {
                $problem = $t( $check['message'] );
            } else {
                $problem = $severity === 'pass' 
                    ? sprintf( $t( 'Correcto: %s' ), $t( $term['short_definition'] ) )
                    : $t( $term['recommendation'] );
            }

            return [
                'title'   => $title,
                'problem' => $problem,
                'why'     => $why,
                'how'     => $how,
            ];
        }

        if ( str_starts_with( $id, 'schema_field_' ) ) {
            $type  = (string) ( $context['type'] ?? '' );
            $field = (string) ( $context['field'] ?? '' );

            $title   = $t( 'Recommended Schema field' );
            $problem = sprintf( $t( 'Schema %s: missing recommended field "%s".' ), $type ?: $t( 'Unknown' ), $field ?: $t( 'unknown' ) );
            $why     = $t( 'Completing recommended Schema fields increases eligibility for rich results and helps search engines and AI systems understand and cite your content correctly.' );

            return [
                'title'   => $title,
                'problem' => $problem,
                'why'     => $why,
                'how'     => [],
            ];
        }

        if ( str_starts_with( $id, 'schema_complete_' ) ) {
            $type  = (string) ( $context['type'] ?? '' );
            $title = $t( 'Schema completeness' );
            return [
                'title'   => $title,
                'problem' => sprintf( $t( 'Schema %s: basic fields look complete.' ), $type ?: $t( 'Unknown' ) ),
                'why'     => $t( 'A complete Schema block is more likely to qualify for rich results and be reliably interpreted by crawlers.' ),
                'how'     => [],
            ];
        }

        return match ( $id ) {
            'single_body' => self::present_single_body( $severity, $context, $t ),
            'single_h1' => self::present_single_h1( $severity, $context, $t ),
            'heading_hierarchy' => self::present_heading_hierarchy( $severity, $context, $t ),
            'has_footer' => self::present_has_footer( $severity, $context, $t ),
            'has_main' => self::present_has_main( $severity, $context, $t ),
            'semantic_tags' => self::present_semantic_tags( $severity, $context, $t ),
            'has_paragraphs' => self::present_has_paragraphs( $severity, $context, $t ),
            'article_in_section' => [
                'title'   => $t( 'Semantic content grouping' ),
                'problem' => $t( '<article> inside <section> detected.' ),
                'why'     => $t( 'Semantic grouping improves structure for accessibility, crawling, and AI extraction.' ),
                'how'     => [],
            ],

            'meta_title' => self::present_meta_title( $severity, $context, $t ),
            'meta_description' => self::present_meta_description( $severity, $context, $t ),
            'meta_canonical' => self::present_meta_canonical( $severity, $context, $t ),
            'meta_robots' => self::present_meta_robots( $severity, $context, $t ),
            'meta_viewport' => self::present_meta_viewport( $severity, $context, $t ),
            'og_incomplete' => self::present_og_incomplete( $severity, $context, $t ),
            'twitter_card' => self::present_twitter_card( $severity, $context, $t ),
            'twitter_incomplete' => self::present_twitter_incomplete( $severity, $context, $t ),

            'schema_present' => self::present_schema_present( $severity, $context, $t ),
            'schema_json_ld_valid' => self::present_schema_json_ld_valid( $severity, $context, $t ),
            'schema_microdata' => self::present_schema_microdata( $severity, $context, $t ),
            'ai_crawlers_wildcard_blocked' => self::present_ai_crawlers_wildcard( $severity, $context, $t ),
            default => self::present_dynamic_crawler( $id, $severity, $context, $t ),
        };
    }

    private static function present_single_body( string $severity, array $context, callable $t ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Valid HTML body' ),
                'problem' => $t( 'Exactly one <body> tag was found.' ),
                'why'     => $t( 'Valid HTML reduces crawler confusion and prevents rendering edge cases.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Valid HTML body' ),
            'problem' => sprintf( $t( 'Found %d <body> tags (should be 1).' ), $count ),
            'why'     => $t( 'Invalid HTML can confuse crawlers and affect rendering, which can reduce SEO and AI extraction quality.' ),
            'how'     => [
                $t( 'Ensure the page output contains exactly one <body> element.' ),
                $t( 'Check your theme/page builder templates for duplicated wrappers.' ),
            ],
        ];
    }

    private static function present_single_h1( string $severity, array $context, callable $t ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            $value = (string) ( $context['value'] ?? '' );
            return [
                'title'   => $t( 'Single H1' ),
                'problem' => $value !== ''
                    ? sprintf( $t( 'Exactly one <h1> was found: "%s".' ), $value )
                    : $t( 'Exactly one <h1> was found.' ),
                'why'     => $t( 'The H1 is a primary on-page topic signal used by search engines and AI systems to understand what the page is about.' ),
                'how'     => [],
            ];
        }

        if ( $count <= 0 ) {
            return [
                'title'   => $t( 'Single H1' ),
                'problem' => $t( 'No <h1> was found on the page.' ),
                'why'     => $t( 'Without a clear main heading, relevance signals are weaker and it is harder for crawlers and AI to summarize the page.' ),
                'how'     => [
                    $t( 'Add a single <h1> that matches the main topic of the page.' ),
                    $t( 'Use <h2>/<h3> for section headings, not additional <h1> tags.' ),
                ],
            ];
        }

        return [
            'title'   => $t( 'Single H1' ),
            'problem' => sprintf( $t( 'Found %d <h1> tags (should be 1).' ), $count ),
            'why'     => $t( 'Multiple H1 tags can dilute topic focus and reduce clarity for both search engines and AI systems.' ),
            'how'     => [
                $t( 'Keep one <h1> for the main page title.' ),
                $t( 'Convert other H1 tags into <h2> or <h3> depending on the hierarchy.' ),
            ],
        ];
    }

    private static function present_heading_hierarchy( string $severity, array $context, callable $t ): array {
        if ( $severity === 'pass' ) {
            $count = (int) ( $context['h2_count'] ?? 0 );
            return [
                'title'   => $t( 'Heading hierarchy' ),
                'problem' => sprintf( $t( '<h2> headings detected (%d).' ), $count ),
                'why'     => $t( 'A logical hierarchy helps crawlers and AI segment content into understandable sections.' ),
                'how'     => [],
            ];
        }

        $h3_count = (int) ( $context['h3_count'] ?? 0 );
        return [
            'title'   => $t( 'Heading hierarchy' ),
            'problem' => $h3_count > 0
                ? $t( 'There are <h3> headings without any preceding <h2> headings (broken hierarchy).' )
                : $t( 'Heading hierarchy has issues.' ),
            'why'     => $t( 'Skipping heading levels reduces structural clarity, which can harm SEO and AI extraction.' ),
            'how'     => [
                $t( 'Structure headings in order: h1 → h2 → h3 without skipping levels.' ),
                $t( 'If you need a sub-section under an H1, start with an H2.' ),
            ],
        ];
    }

    private static function present_has_footer( string $severity, array $context, callable $t ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Semantic footer' ),
                'problem' => $t( 'A <footer> element was found.' ),
                'why'     => $t( 'Semantic landmarks improve accessibility and document structure for crawlers.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Semantic footer' ),
            'problem' => $t( 'No <footer> element was found.' ),
            'why'     => $t( 'Without semantic landmarks, page structure is less explicit for accessibility tools and crawlers.' ),
            'how'     => [
                $t( 'Add a semantic <footer> for the page or site footer.' ),
                $t( 'If a page builder uses generic <div> wrappers, consider adjusting the template or adding a hook to output <footer>.' ),
            ],
        ];
    }

    private static function present_has_main( string $severity, array $context, callable $t ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Main content landmark' ),
                'problem' => $t( 'A <main> element was found.' ),
                'why'     => $t( 'The <main> landmark helps crawlers and AI identify the primary content versus navigation and boilerplate.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Main content landmark' ),
            'problem' => $t( 'No <main> element was found.' ),
            'why'     => $t( 'When the main content is not clearly delimited, AI and crawlers may misinterpret navigation or footer content as part of the main topic.' ),
            'how'     => [
                $t( 'Wrap the primary page content in a <main> element.' ),
                $t( 'If your theme/page builder does not output <main>, consider updating the template or adding it via a hook.' ),
            ],
        ];
    }

    private static function present_semantic_tags( string $severity, array $context, callable $t ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Semantic sections' ),
                'problem' => $t( 'Semantic tags (<section> and/or <article>) were detected.' ),
                'why'     => $t( 'Semantic chunking helps crawlers and AI segment and interpret content more accurately.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Semantic sections' ),
            'problem' => $t( 'No <section> or <article> elements were detected.' ),
            'why'     => $t( 'Without semantic blocks, pages are harder to process into meaningful chunks for search engines and AI systems.' ),
            'how'     => [
                $t( 'Wrap related content blocks in <section> or <article>.' ),
                $t( 'Use headings inside those blocks to reinforce structure.' ),
            ],
        ];
    }

    private static function present_has_paragraphs( string $severity, array $context, callable $t ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Paragraph structure' ),
                'problem' => sprintf( $t( '<p> tags detected (%d).' ), $count ),
                'why'     => $t( 'Paragraph markup makes content easier to parse for SEO, accessibility, and AI extraction.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Paragraph structure' ),
            'problem' => $t( 'No <p> tags were found.' ),
            'why'     => $t( 'If content is not marked up as paragraphs, it becomes harder to process and can reduce readability signals and extraction quality.' ),
            'how'     => [
                $t( 'Use <p> tags for body text instead of plain text nodes or generic containers.' ),
                $t( 'Ensure your editor/page builder outputs semantic paragraph markup.' ),
            ],
        ];
    }

    private static function present_meta_title( string $severity, array $context, callable $t ): array {
        $length = (int) ( $context['length'] ?? 0 );
        $value  = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => $t( 'Title tag' ),
                'problem' => $t( 'The <title> tag is missing.' ),
                'why'     => $t( 'The title tag is one of the strongest on-page relevance signals and is shown in search results.' ),
                'how'     => [
                    $t( 'Add a unique, descriptive title between 30 and 60 characters.' ),
                    $t( 'Include the primary keyword and a clear value proposition.' ),
                ],
            ],
            'warning' => [
                'title'   => $t( 'Title tag' ),
                'problem' => $value !== ''
                    ? sprintf( $t( 'The <title> length is not optimal (%d characters): "%s".' ), $length, $value )
                    : sprintf( $t( 'The <title> length is not optimal (%d characters).' ), $length ),
                'why'     => $t( 'Very short titles miss context; very long titles may be truncated in search results.' ),
                'how'     => [
                    $t( 'Keep the title between 30 and 60 characters.' ),
                    $t( 'Front-load the important terms.' ),
                ],
            ],
            default => [
                'title'   => $t( 'Title tag' ),
                'problem' => $value !== ''
                    ? sprintf( $t( 'The <title> looks good (%d characters): "%s".' ), $length, $value )
                    : sprintf( $t( 'The <title> looks good (%d characters).' ), $length ),
                'why'     => $t( 'A well-formed title improves relevance and click-through rate.' ),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_description( string $severity, array $context, callable $t ): array {
        $length = (int) ( $context['length'] ?? 0 );
        $value  = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => $t( 'Meta description' ),
                'problem' => $t( 'The meta description is missing.' ),
                'why'     => $t( 'While not a direct ranking factor, meta descriptions strongly affect CTR and are often used by AI systems to summarize pages.' ),
                'how'     => [
                    $t( 'Add a meta description between 70 and 155 characters.' ),
                    $t( 'Include the primary keyword and a clear benefit or call to action.' ),
                ],
            ],
            'warning' => [
                'title'   => $t( 'Meta description' ),
                'problem' => $value !== ''
                    ? sprintf( $t( 'The meta description length is not optimal (%d characters).' ), $length )
                    : sprintf( $t( 'The meta description length is not optimal (%d characters).' ), $length ),
                'why'     => $t( 'Very short descriptions miss persuasion; very long ones may be truncated.' ),
                'how'     => [
                    $t( 'Keep it between 70 and 155 characters.' ),
                    $t( 'Make it descriptive and specific to the page.' ),
                ],
            ],
            default => [
                'title'   => $t( 'Meta description' ),
                'problem' => sprintf( $t( 'The meta description looks good (%d characters).' ), $length ),
                'why'     => $t( 'A good meta description improves click-through rate and snippet quality.' ),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_canonical( string $severity, array $context, callable $t ): array {
        $value = (string) ( $context['value'] ?? '' );
        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Canonical URL' ),
                'problem' => $value !== '' ? sprintf( $t( 'Canonical is set: %s' ), $value ) : $t( 'Canonical is set.' ),
                'why'     => $t( 'Canonicals help consolidate duplicate URLs and ensure the correct version is indexed.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Canonical URL' ),
            'problem' => $t( 'No canonical URL was found (<link rel="canonical">).' ),
            'why'     => $t( 'Without a canonical, search engines may index unintended URL variants and split ranking signals.' ),
            'how'     => [
                $t( 'Add a canonical tag pointing to the preferred URL of the page.' ),
                $t( 'Ensure it matches the final URL after redirects.' ),
            ],
        ];
    }

    private static function present_meta_robots( string $severity, array $context, callable $t ): array {
        $value = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => $t( 'Robots meta' ),
                'problem' => $value !== ''
                    ? sprintf( $t( 'Robots meta contains "noindex": %s' ), $value )
                    : $t( 'Robots meta contains "noindex".' ),
                'why'     => $t( 'Pages marked noindex will not appear in search results and are often skipped by AI crawlers.' ),
                'how'     => [
                    $t( 'Remove "noindex" if this page should be indexed.' ),
                    $t( 'Confirm the directive is not being injected by another plugin or template.' ),
                ],
            ],
            'warning' => [
                'title'   => $t( 'Robots meta' ),
                'problem' => $t( 'Robots meta is missing (defaults to index, follow).' ),
                'why'     => $t( 'Explicit directives reduce ambiguity and prevent accidental indexing issues.' ),
                'how'     => [
                    $t( 'Add <meta name="robots" content="index, follow"> if appropriate.' ),
                ],
            ],
            default => [
                'title'   => $t( 'Robots meta' ),
                'problem' => $value !== '' ? sprintf( $t( 'Robots meta is set: %s' ), $value ) : $t( 'Robots meta is set.' ),
                'why'     => $t( 'Correct indexing directives help search engines crawl and index your content as intended.' ),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_viewport( string $severity, array $context, callable $t ): array {
        $value = (string) ( $context['value'] ?? '' );

        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Viewport meta' ),
                'problem' => $value !== '' ? sprintf( $t( 'Viewport meta is set: %s' ), $value ) : $t( 'Viewport meta is set.' ),
                'why'     => $t( 'Viewport configuration is required for mobile-friendly rendering, which affects SEO.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Viewport meta' ),
            'problem' => $t( 'Viewport meta is missing.' ),
            'why'     => $t( 'Without it, pages may not render correctly on mobile devices, hurting usability and rankings.' ),
            'how'     => [
                $t( 'Add <meta name="viewport" content="width=device-width, initial-scale=1">.' ),
            ],
        ];
    }

    private static function present_og_incomplete( string $severity, array $context, callable $t ): array {
        $missing = is_array( $context['missing'] ?? null ) ? $context['missing'] : [];

        return [
            'title'   => $t( 'Open Graph tags' ),
            'problem' => ! empty( $missing )
                ? sprintf( $t( 'Open Graph is incomplete. Missing: %s.' ), implode( ', ', $missing ) )
                : $t( 'Open Graph is incomplete.' ),
            'why'     => $t( 'Open Graph tags control how the page appears when shared and can provide extra context for some AI systems.' ),
            'how'     => [
                $t( 'Add og:title, og:description, og:image, og:type, and og:url.' ),
            ],
        ];
    }

    private static function present_twitter_card( string $severity, array $context, callable $t ): array {
        $value = (string) ( $context['value'] ?? '' );

        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Twitter Card' ),
                'problem' => $value !== '' ? sprintf( $t( 'Twitter Card is set: %s' ), $value ) : $t( 'Twitter Card is set.' ),
                'why'     => $t( 'It controls how content appears when shared on X/Twitter.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Twitter Card' ),
            'problem' => $t( 'Twitter Card meta is missing (twitter:card).' ),
            'why'     => $t( 'Without it, shared previews may be inconsistent and less clickable.' ),
            'how'     => [
                $t( 'Add <meta name="twitter:card" content="summary_large_image">.' ),
            ],
        ];
    }

    private static function present_twitter_incomplete( string $severity, array $context, callable $t ): array {
        $missing = is_array( $context['missing'] ?? null ) ? $context['missing'] : [];

        return [
            'title'   => $t( 'Twitter meta tags' ),
            'problem' => ! empty( $missing )
                ? sprintf( $t( 'Twitter meta tags are incomplete. Missing: %s.' ), implode( ', ', $missing ) )
                : $t( 'Twitter meta tags are incomplete.' ),
            'why'     => $t( 'Completing Twitter meta improves share previews and consistency across platforms.' ),
            'how'     => [
                $t( 'Add twitter:title, twitter:description, and twitter:image.' ),
            ],
        ];
    }

    private static function present_schema_present( string $severity, array $context, callable $t ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => $t( 'Schema.org presence' ),
                'problem' => $t( 'Schema.org markup was detected.' ),
                'why'     => $t( 'Schema markup improves eligibility for rich results and helps AI systems understand the content type.' ),
                'how'     => [],
            ];
        }

        return [
            'title'   => $t( 'Schema.org presence' ),
            'problem' => $t( 'No Schema.org markup was detected (no JSON-LD and no microdata).' ),
            'why'     => $t( 'Without Schema, your content is less likely to qualify for rich results and harder for AI systems to classify and cite.' ),
            'how'     => [
                $t( 'Add Schema.org markup using JSON-LD.' ),
                $t( 'Start with the schema type that matches your content (Article, Product, FAQPage, LocalBusiness, etc.).' ),
            ],
        ];
    }

    private static function present_schema_json_ld_valid( string $severity, array $context, callable $t ): array {
        return [
            'title'   => $t( 'JSON-LD validity' ),
            'problem' => $t( 'JSON-LD was found but could not be parsed (invalid JSON).' ),
            'why'     => $t( 'Invalid JSON-LD is ignored by search engines and AI crawlers.' ),
            'how'     => [
                $t( 'Validate your JSON-LD with Google Rich Results Test.' ),
                $t( 'Fix syntax errors such as missing quotes, trailing commas, or broken braces.' ),
            ],
        ];
    }

    private static function present_schema_microdata( string $severity, array $context, callable $t ): array {
        $types = is_array( $context['types'] ?? null ) ? $context['types'] : [];
        return [
            'title'   => $t( 'Microdata detected' ),
            'problem' => ! empty( $types )
                ? sprintf( $t( 'Microdata detected: %s' ), implode( ', ', $types ) )
                : $t( 'Microdata detected.' ),
            'why'     => $t( 'Microdata can provide structured information for crawlers, but JSON-LD is typically easier to maintain.' ),
            'how'     => [],
        ];
    }

    // ── AI Crawler consolidated warning ───────────────────────────────────────

    private static function present_ai_crawlers_wildcard( string $severity, array $context, callable $t ): array {
        $count      = (int) ( $context['blocked_count'] ?? 0 );
        $bots       = is_array( $context['blocked_bots'] ?? null ) ? $context['blocked_bots'] : [];
        $directives = (string) ( $context['allow_directives'] ?? '' );

        $bot_list = ! empty( $bots ) ? implode( ', ', $bots ) : $t( 'Multiple AI crawlers' );

        return [
            'title'   => $t( 'AI crawlers blocked by wildcard rule' ),
            'problem' => sprintf(
                $t( '%d AI crawler(s) are blocked indirectly by a wildcard (*) Disallow rule in robots.txt: %s.' ),
                $count,
                $bot_list
            ),
            'why'     => $t( 'A blanket "User-agent: * / Disallow: /" rule prevents all AI crawlers from indexing your content. This means services like ChatGPT, Claude, Perplexity, and Google AI Overviews cannot access your pages, significantly reducing your AI visibility. The plugin does NOT modify robots.txt automatically for security reasons.' ),
            'how'     => [
                $t( 'Copy the Allow directives shown below and paste them into your robots.txt file, BEFORE the wildcard Disallow rule.' ),
                $t( 'Each directive explicitly allows a specific AI crawler to access your site.' ),
                $t( 'After editing, verify your robots.txt is accessible at yourdomain.com/robots.txt.' ),
            ],
            'context' => [
                'allow_directives' => $directives,
            ],
        ];
    }

    /**
     * Fallback presenter for dynamic crawler check IDs (crawler_blocked_*, crawler_unlisted_*).
     */
    private static function present_dynamic_crawler( string $id, string $severity, array $context, callable $t ): ?array {
        if ( str_starts_with( $id, 'crawler_blocked_' ) || str_starts_with( $id, 'crawler_unlisted_' ) || str_starts_with( $id, 'crawler_allowed_' ) ) {
            $bot = (string) ( $context['bot'] ?? '' );

            if ( $severity === 'pass' ) {
                return [
                    'title'   => $t( 'AI crawler policy' ),
                    'problem' => $bot !== '' ? sprintf( $t( 'AI crawler allowed: %s' ), $bot ) : $t( 'AI crawler is allowed.' ),
                    'why'     => $t( 'Allowing AI crawlers to access your content improves your visibility in AI-generated answers.' ),
                    'how'     => [],
                ];
            }

            $is_blocked = str_starts_with( $id, 'crawler_blocked_' );
            return [
                'title'   => $t( 'AI crawler policy' ),
                'problem' => $bot !== ''
                    ? sprintf( $t( $is_blocked ? 'AI crawler explicitly blocked: %s' : 'AI crawler has no explicit rule: %s' ), $bot )
                    : $t( 'AI crawler access issue detected.' ),
                'why'     => $t( 'Blocking or not explicitly allowing AI crawlers reduces the chance of your content being cited in AI-generated responses.' ),
                'how'     => [
                    $t( 'Review and adjust the relevant User-agent rules in your robots.txt file manually.' ),
                ],
            ];
        }

        return null;
    }
}

