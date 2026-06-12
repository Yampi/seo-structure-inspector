<?php

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class CheckCatalog {
    public static function present( array $check ): ?array {
        $id       = (string) ( $check['id'] ?? '' );
        $severity = (string) ( $check['severity'] ?? '' );
        $context  = is_array( $check['context'] ?? null ) ? $check['context'] : [];

        if ( $id === '' ) return null;

        // Query Bookman glossary registry for a unified explanation
        $term = \BaloaStructureAuditorSEO\Core\Bookman::get_term( $id );
        if ( $term ) {
            $title = $term['name'];
            $why   = $term['why_it_matters'];
            $how   = [ $term['recommendation'] ];
            
            // Format dynamic message based on context or severity
            if ( ! empty( $check['message'] ) ) {
                $problem = $check['message'];
            } else {
                if ( $severity === 'pass' ) {
                    /* translators: %s: Short definition of the term. */
                    $problem = sprintf( __('Correcto: %s', 'baloa-structure-auditor-seo'), $term['short_definition'] );
                } else {
                    $problem = $term['recommendation'];
                }
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

            $title   = __('Recommended Schema field', 'baloa-structure-auditor-seo');
            /* translators: 1: Schema type, 2: Schema field name. */
            $problem = sprintf( __('Schema %1$s: missing recommended field "%2$s".', 'baloa-structure-auditor-seo'), $type ?: __('Unknown', 'baloa-structure-auditor-seo'), $field ?: __('unknown', 'baloa-structure-auditor-seo') );
            $why     = __('Completing recommended Schema fields increases eligibility for rich results and helps search engines and AI systems understand and cite your content correctly.', 'baloa-structure-auditor-seo');

            return [
                'title'   => $title,
                'problem' => $problem,
                'why'     => $why,
                'how'     => [],
            ];
        }

        if ( str_starts_with( $id, 'schema_complete_' ) ) {
            $type  = (string) ( $context['type'] ?? '' );
            $title = __('Schema completeness', 'baloa-structure-auditor-seo');
            return [
                'title'   => $title,
                /* translators: %s: Schema type. */
                'problem' => sprintf( __('Schema %s: basic fields look complete.', 'baloa-structure-auditor-seo'), $type ?: __('Unknown', 'baloa-structure-auditor-seo') ),
                'why'     => __('A complete Schema block is more likely to qualify for rich results and be reliably interpreted by crawlers.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return match ( $id ) {
            'single_body' => self::present_single_body( $severity, $context ),
            'single_h1' => self::present_single_h1( $severity, $context ),
            'heading_hierarchy' => self::present_heading_hierarchy( $severity, $context ),
            'has_footer' => self::present_has_footer( $severity, $context ),
            'has_main' => self::present_has_main( $severity, $context ),
            'semantic_tags' => self::present_semantic_tags( $severity, $context ),
            'has_paragraphs' => self::present_has_paragraphs( $severity, $context ),
            'article_in_section' => [
                'title'   => __('Semantic content grouping', 'baloa-structure-auditor-seo'),
                'problem' => __('<article> inside <section> detected.', 'baloa-structure-auditor-seo'),
                'why'     => __('Semantic grouping improves structure for accessibility, crawling, and AI extraction.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ],

            'meta_title' => self::present_meta_title( $severity, $context ),
            'meta_description' => self::present_meta_description( $severity, $context ),
            'meta_canonical' => self::present_meta_canonical( $severity, $context ),
            'meta_robots' => self::present_meta_robots( $severity, $context ),
            'meta_viewport' => self::present_meta_viewport( $severity, $context ),
            'og_incomplete' => self::present_og_incomplete( $severity, $context ),
            'twitter_card' => self::present_twitter_card( $severity, $context ),
            'twitter_incomplete' => self::present_twitter_incomplete( $severity, $context ),

            'schema_present' => self::present_schema_present( $severity, $context ),
            'schema_json_ld_valid' => self::present_schema_json_ld_valid( $severity, $context ),
            'schema_microdata' => self::present_schema_microdata( $severity, $context ),
            'ai_crawlers_wildcard_blocked' => self::present_ai_crawlers_wildcard( $severity, $context ),
            default => self::present_dynamic_crawler( $id, $severity, $context ),
        };
    }

    private static function present_single_body( string $severity, array $context ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Valid HTML body', 'baloa-structure-auditor-seo'),
                'problem' => __('Exactly one <body> tag was found.', 'baloa-structure-auditor-seo'),
                'why'     => __('Valid HTML reduces crawler confusion and prevents rendering edge cases.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Valid HTML body', 'baloa-structure-auditor-seo'),
            /* translators: %d: Number of body tags found. */
            'problem' => sprintf( __('Found %d <body> tags (should be 1).', 'baloa-structure-auditor-seo'), $count ),
            'why'     => __('Invalid HTML can confuse crawlers and affect rendering, which can reduce SEO and AI extraction quality.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Ensure the page output contains exactly one <body> element.', 'baloa-structure-auditor-seo'),
                __('Check your theme/page builder templates for duplicated wrappers.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_single_h1( string $severity, array $context ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            $value = (string) ( $context['value'] ?? '' );
            if ( $value !== '' ) {
                /* translators: %s: Title or content of the H1 heading. */
                $problem = sprintf( __('Exactly one <h1> was found: "%s".', 'baloa-structure-auditor-seo'), $value );
            } else {
                $problem = __('Exactly one <h1> was found.', 'baloa-structure-auditor-seo');
            }
            return [
                'title'   => __('Single H1', 'baloa-structure-auditor-seo'),
                'problem' => $problem,
                'why'     => __('The H1 is a primary on-page topic signal used by search engines and AI systems to understand what the page is about.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        if ( $count <= 0 ) {
            return [
                'title'   => __('Single H1', 'baloa-structure-auditor-seo'),
                'problem' => __('No <h1> was found on the page.', 'baloa-structure-auditor-seo'),
                'why'     => __('Without a clear main heading, relevance signals are weaker and it is harder for crawlers and AI to summarize the page.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Add a single <h1> that matches the main topic of the page.', 'baloa-structure-auditor-seo'),
                    __('Use <h2>/<h3> for section headings, not additional <h1> tags.', 'baloa-structure-auditor-seo'),
                ],
            ];
        }

        return [
            'title'   => __('Single H1', 'baloa-structure-auditor-seo'),
            /* translators: %d: Number of H1 tags found. */
            'problem' => sprintf( __('Found %d <h1> tags (should be 1).', 'baloa-structure-auditor-seo'), $count ),
            'why'     => __('Multiple H1 tags can dilute topic focus and reduce clarity for both search engines and AI systems.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Keep one <h1> for the main page title.', 'baloa-structure-auditor-seo'),
                __('Convert other H1 tags into <h2> or <h3> depending on the hierarchy.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_heading_hierarchy( string $severity, array $context ): array {
        if ( $severity === 'pass' ) {
            $count = (int) ( $context['h2_count'] ?? 0 );
            return [
                'title'   => __('Heading hierarchy', 'baloa-structure-auditor-seo'),
                /* translators: %d: Number of H2 headings detected. */
                'problem' => sprintf( __('<h2> headings detected (%d).', 'baloa-structure-auditor-seo'), $count ),
                'why'     => __('A logical hierarchy helps crawlers and AI segment content into understandable sections.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        $h3_count = (int) ( $context['h3_count'] ?? 0 );
        return [
            'title'   => __('Heading hierarchy', 'baloa-structure-auditor-seo'),
            'problem' => $h3_count > 0
                ? __('There are <h3> headings without any preceding <h2> headings (broken hierarchy).', 'baloa-structure-auditor-seo')
                : __('Heading hierarchy has issues.', 'baloa-structure-auditor-seo'),
            'why'     => __('Skipping heading levels reduces structural clarity, which can harm SEO and AI extraction.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Structure headings in order: h1 Ã¢â€ â€™ h2 Ã¢â€ â€™ h3 without skipping levels.', 'baloa-structure-auditor-seo'),
                __('If you need a sub-section under an H1, start with an H2.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_has_footer( string $severity, array $context ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Semantic footer', 'baloa-structure-auditor-seo'),
                'problem' => __('A <footer> element was found.', 'baloa-structure-auditor-seo'),
                'why'     => __('Semantic landmarks improve accessibility and document structure for crawlers.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Semantic footer', 'baloa-structure-auditor-seo'),
            'problem' => __('No <footer> element was found.', 'baloa-structure-auditor-seo'),
            'why'     => __('Without semantic landmarks, page structure is less explicit for accessibility tools and crawlers.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add a semantic <footer> for the page or site footer.', 'baloa-structure-auditor-seo'),
                __('If a page builder uses generic <div> wrappers, consider adjusting the template or adding a hook to output <footer>.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_has_main( string $severity, array $context ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Main content landmark', 'baloa-structure-auditor-seo'),
                'problem' => __('A <main> element was found.', 'baloa-structure-auditor-seo'),
                'why'     => __('The <main> landmark helps crawlers and AI identify the primary content versus navigation and boilerplate.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Main content landmark', 'baloa-structure-auditor-seo'),
            'problem' => __('No <main> element was found.', 'baloa-structure-auditor-seo'),
            'why'     => __('When the main content is not clearly delimited, AI and crawlers may misinterpret navigation or footer content as part of the main topic.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Wrap the primary page content in a <main> element.', 'baloa-structure-auditor-seo'),
                __('If your theme/page builder does not output <main>, consider updating the template or adding it via a hook.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_semantic_tags( string $severity, array $context ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Semantic sections', 'baloa-structure-auditor-seo'),
                'problem' => __('Semantic tags (<section> and/or <article>) were detected.', 'baloa-structure-auditor-seo'),
                'why'     => __('Semantic chunking helps crawlers and AI segment and interpret content more accurately.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Semantic sections', 'baloa-structure-auditor-seo'),
            'problem' => __('No <section> or <article> elements were detected.', 'baloa-structure-auditor-seo'),
            'why'     => __('Without semantic blocks, pages are harder to process into meaningful chunks for search engines and AI systems.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Wrap related content blocks in <section> or <article>.', 'baloa-structure-auditor-seo'),
                __('Use headings inside those blocks to reinforce structure.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_has_paragraphs( string $severity, array $context ): array {
        $count = (int) ( $context['count'] ?? 0 );

        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Paragraph structure', 'baloa-structure-auditor-seo'),
                /* translators: %d: Number of paragraph tags detected. */
                'problem' => sprintf( __('<p> tags detected (%d).', 'baloa-structure-auditor-seo'), $count ),
                'why'     => __('Paragraph markup makes content easier to parse for SEO, accessibility, and AI extraction.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Paragraph structure', 'baloa-structure-auditor-seo'),
            'problem' => __('No <p> tags were found.', 'baloa-structure-auditor-seo'),
            'why'     => __('If content is not marked up as paragraphs, it becomes harder to process and can reduce readability signals and extraction quality.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Use <p> tags for body text instead of plain text nodes or generic containers.', 'baloa-structure-auditor-seo'),
                __('Ensure your editor/page builder outputs semantic paragraph markup.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_meta_title( string $severity, array $context ): array {
        $length = (int) ( $context['length'] ?? 0 );
        $value  = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => __('Title tag', 'baloa-structure-auditor-seo'),
                'problem' => __('The <title> tag is missing.', 'baloa-structure-auditor-seo'),
                'why'     => __('The title tag is one of the strongest on-page relevance signals and is shown in search results.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Add a unique, descriptive title between 30 and 60 characters.', 'baloa-structure-auditor-seo'),
                    __('Include the primary keyword and a clear value proposition.', 'baloa-structure-auditor-seo'),
                ],
            ],
            'warning' => [
                'title'   => __('Title tag', 'baloa-structure-auditor-seo'),
                'problem' => $value !== ''
                    /* translators: 1: Title length in characters, 2: Actual title text content. */
                    ? sprintf( __('The <title> length is not optimal (%1$d characters): "%2$s".', 'baloa-structure-auditor-seo'), $length, $value )
                    /* translators: %d: Title length in characters. */
                    : sprintf( __('The <title> length is not optimal (%d characters).', 'baloa-structure-auditor-seo'), $length ),
                'why'     => __('Very short titles miss context; very long titles may be truncated in search results.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Keep the title between 30 and 60 characters.', 'baloa-structure-auditor-seo'),
                    __('Front-load the important terms.', 'baloa-structure-auditor-seo'),
                ],
            ],
            default => [
                'title'   => __('Title tag', 'baloa-structure-auditor-seo'),
                'problem' => $value !== ''
                    /* translators: 1: Title length in characters, 2: Actual title text content. */
                    ? sprintf( __('The <title> looks good (%1$d characters): "%2$s".', 'baloa-structure-auditor-seo'), $length, $value )
                    /* translators: %d: Title length in characters. */
                    : sprintf( __('The <title> looks good (%d characters).', 'baloa-structure-auditor-seo'), $length ),
                'why'     => __('A well-formed title improves relevance and click-through rate.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_description( string $severity, array $context ): array {
        $length = (int) ( $context['length'] ?? 0 );
        $value  = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => __('Meta description', 'baloa-structure-auditor-seo'),
                'problem' => __('The meta description is missing.', 'baloa-structure-auditor-seo'),
                'why'     => __('While not a direct ranking factor, meta descriptions strongly affect CTR and are often used by AI systems to summarize pages.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Add a meta description between 70 and 155 characters.', 'baloa-structure-auditor-seo'),
                    __('Include the primary keyword and a clear benefit or call to action.', 'baloa-structure-auditor-seo'),
                ],
            ],
            'warning' => [
                'title'   => __('Meta description', 'baloa-structure-auditor-seo'),
                /* translators: %d: Meta description length in characters. */
                'problem' => sprintf( __('The meta description length is not optimal (%d characters).', 'baloa-structure-auditor-seo'), $length ),
                'why'     => __('Very short descriptions miss persuasion; very long ones may be truncated.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Keep it between 70 and 155 characters.', 'baloa-structure-auditor-seo'),
                    __('Make it descriptive and specific to the page.', 'baloa-structure-auditor-seo'),
                ],
            ],
            default => [
                'title'   => __('Meta description', 'baloa-structure-auditor-seo'),
                /* translators: %d: Meta description length in characters. */
                'problem' => sprintf( __('The meta description looks good (%d characters).', 'baloa-structure-auditor-seo'), $length ),
                'why'     => __('A good meta description improves click-through rate and snippet quality.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_canonical( string $severity, array $context ): array {
        $value = (string) ( $context['value'] ?? '' );
        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Canonical URL', 'baloa-structure-auditor-seo'),
                /* translators: %s: Canonical URL. */
                'problem' => $value !== '' ? sprintf( __('Canonical is set: %s', 'baloa-structure-auditor-seo'), $value ) : __('Canonical is set.', 'baloa-structure-auditor-seo'),
                'why'     => __('Canonicals help consolidate duplicate URLs and ensure the correct version is indexed.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Canonical URL', 'baloa-structure-auditor-seo'),
            'problem' => __('No canonical URL was found (<link rel="canonical">).', 'baloa-structure-auditor-seo'),
            'why'     => __('Without a canonical, search engines may index unintended URL variants and split ranking signals.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add a canonical tag pointing to the preferred URL of the page.', 'baloa-structure-auditor-seo'),
                __('Ensure it matches the final URL after redirects.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_meta_robots( string $severity, array $context ): array {
        $value = (string) ( $context['value'] ?? '' );

        return match ( $severity ) {
            'error' => [
                'title'   => __('Robots meta', 'baloa-structure-auditor-seo'),
                'problem' => $value !== ''
                    /* translators: %s: Directives found in robots meta tag. */
                    ? sprintf( __('Robots meta contains "noindex": %s', 'baloa-structure-auditor-seo'), $value )
                    : __('Robots meta contains "noindex".', 'baloa-structure-auditor-seo'),
                'why'     => __('Pages marked noindex will not appear in search results and are often skipped by AI crawlers.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Remove "noindex" if this page should be indexed.', 'baloa-structure-auditor-seo'),
                    __('Confirm the directive is not being injected by another plugin or template.', 'baloa-structure-auditor-seo'),
                ],
            ],
            'warning' => [
                'title'   => __('Robots meta', 'baloa-structure-auditor-seo'),
                'problem' => __('Robots meta is missing (defaults to index, follow).', 'baloa-structure-auditor-seo'),
                'why'     => __('Explicit directives reduce ambiguity and prevent accidental indexing issues.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Add <meta name="robots" content="index, follow"> if appropriate.', 'baloa-structure-auditor-seo'),
                ],
            ],
            default => [
                'title'   => __('Robots meta', 'baloa-structure-auditor-seo'),
                /* translators: %s: Robots meta tag value. */
                'problem' => $value !== '' ? sprintf( __('Robots meta is set: %s', 'baloa-structure-auditor-seo'), $value ) : __('Robots meta is set.', 'baloa-structure-auditor-seo'),
                'why'     => __('Correct indexing directives help search engines crawl and index your content as intended.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ],
        };
    }

    private static function present_meta_viewport( string $severity, array $context ): array {
        $value = (string) ( $context['value'] ?? '' );

        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Viewport meta', 'baloa-structure-auditor-seo'),
                /* translators: %s: Viewport meta tag content. */
                'problem' => $value !== '' ? sprintf( __('Viewport meta is set: %s', 'baloa-structure-auditor-seo'), $value ) : __('Viewport meta is set.', 'baloa-structure-auditor-seo'),
                'why'     => __('Viewport configuration is required for mobile-friendly rendering, which affects SEO.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Viewport meta', 'baloa-structure-auditor-seo'),
            'problem' => __('Viewport meta is missing.', 'baloa-structure-auditor-seo'),
            'why'     => __('Without it, pages may not render correctly on mobile devices, hurting usability and rankings.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add <meta name="viewport" content="width=device-width, initial-scale=1">.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_og_incomplete( string $severity, array $context ): array {
        $missing = is_array( $context['missing'] ?? null ) ? $context['missing'] : [];

        return [
            'title'   => __('Open Graph tags', 'baloa-structure-auditor-seo'),
            'problem' => ! empty( $missing )
                /* translators: %s: Comma-separated list of missing Open Graph properties. */
                ? sprintf( __('Open Graph is incomplete. Missing: %s.', 'baloa-structure-auditor-seo'), implode( ', ', $missing ) )
                : __('Open Graph is incomplete.', 'baloa-structure-auditor-seo'),
            'why'     => __('Open Graph tags control how the page appears when shared and can provide extra context for some AI systems.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add og:title, og:description, og:image, og:type, and og:url.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_twitter_card( string $severity, array $context ): array {
        $value = (string) ( $context['value'] ?? '' );

        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Twitter Card', 'baloa-structure-auditor-seo'),
                /* translators: %s: Twitter Card card type value. */
                'problem' => $value !== '' ? sprintf( __('Twitter Card is set: %s', 'baloa-structure-auditor-seo'), $value ) : __('Twitter Card is set.', 'baloa-structure-auditor-seo'),
                'why'     => __('It controls how content appears when shared on X/Twitter.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Twitter Card', 'baloa-structure-auditor-seo'),
            'problem' => __('Twitter Card meta is missing (twitter:card).', 'baloa-structure-auditor-seo'),
            'why'     => __('Without it, shared previews may be inconsistent and less clickable.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add <meta name="twitter:card" content="summary_large_image">.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_twitter_incomplete( string $severity, array $context ): array {
        $missing = is_array( $context['missing'] ?? null ) ? $context['missing'] : [];

        return [
            'title'   => __('Twitter meta tags', 'baloa-structure-auditor-seo'),
            'problem' => ! empty( $missing )
                /* translators: %s: Comma-separated list of missing Twitter meta tags. */
                ? sprintf( __('Twitter meta tags are incomplete. Missing: %s.', 'baloa-structure-auditor-seo'), implode( ', ', $missing ) )
                : __('Twitter meta tags are incomplete.', 'baloa-structure-auditor-seo'),
            'why'     => __('Completing Twitter meta improves share previews and consistency across platforms.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add twitter:title, twitter:description, and twitter:image.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_schema_present( string $severity, array $context ): array {
        if ( $severity === 'pass' ) {
            return [
                'title'   => __('Schema.org presence', 'baloa-structure-auditor-seo'),
                'problem' => __('Schema.org markup was detected.', 'baloa-structure-auditor-seo'),
                'why'     => __('Schema markup improves eligibility for rich results and helps AI systems understand the content type.', 'baloa-structure-auditor-seo'),
                'how'     => [],
            ];
        }

        return [
            'title'   => __('Schema.org presence', 'baloa-structure-auditor-seo'),
            'problem' => __('No Schema.org markup was detected (no JSON-LD and no microdata).', 'baloa-structure-auditor-seo'),
            'why'     => __('Without Schema, your content is less likely to qualify for rich results and harder for AI systems to classify and cite.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Add Schema.org markup using JSON-LD.', 'baloa-structure-auditor-seo'),
                __('Start with the schema type that matches your content (Article, Product, FAQPage, LocalBusiness, etc.).', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_schema_json_ld_valid( string $severity, array $context ): array {
        return [
            'title'   => __('JSON-LD validity', 'baloa-structure-auditor-seo'),
            'problem' => __('JSON-LD was found but could not be parsed (invalid JSON).', 'baloa-structure-auditor-seo'),
            'why'     => __('Invalid JSON-LD is ignored by search engines and AI crawlers.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Validate your JSON-LD with Google Rich Results Test.', 'baloa-structure-auditor-seo'),
                __('Fix syntax errors such as missing quotes, trailing commas, or broken braces.', 'baloa-structure-auditor-seo'),
            ],
        ];
    }

    private static function present_schema_microdata( string $severity, array $context ): array {
        $types = is_array( $context['types'] ?? null ) ? $context['types'] : [];
        return [
            'title'   => __('Microdata detected', 'baloa-structure-auditor-seo'),
            'problem' => ! empty( $types )
                /* translators: %s: Comma-separated list of Schema types found in Microdata. */
                ? sprintf( __('Microdata detected: %s', 'baloa-structure-auditor-seo'), implode( ', ', $types ) )
                : __('Microdata detected.', 'baloa-structure-auditor-seo'),
            'why'     => __('Microdata can provide structured information for crawlers, but JSON-LD is typically easier to maintain.', 'baloa-structure-auditor-seo'),
            'how'     => [],
        ];
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ AI Crawler consolidated warning Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    private static function present_ai_crawlers_wildcard( string $severity, array $context ): array {
        $count      = (int) ( $context['blocked_count'] ?? 0 );
        $bots       = is_array( $context['blocked_bots'] ?? null ) ? $context['blocked_bots'] : [];
        $directives = (string) ( $context['allow_directives'] ?? '' );

        $bot_list = ! empty( $bots ) ? implode( ', ', $bots ) : __('Multiple AI crawlers', 'baloa-structure-auditor-seo');

        return [
            'title'   => __('AI crawlers blocked by wildcard rule', 'baloa-structure-auditor-seo'),
            'problem' => sprintf(
                /* translators: 1: Count of blocked AI crawlers, 2: List of blocked bots. */
                __('%1$d AI crawler(s) are blocked indirectly by a wildcard (*) Disallow rule in robots.txt: %2$s.', 'baloa-structure-auditor-seo'),
                $count,
                $bot_list
            ),
            'why'     => __('A blanket "User-agent: * / Disallow: /" rule prevents all AI crawlers from indexing your content. This means services like ChatGPT, Claude, Perplexity, and Google AI Overviews cannot access your pages, significantly reducing your AI visibility. The plugin does NOT modify robots.txt automatically for security reasons.', 'baloa-structure-auditor-seo'),
            'how'     => [
                __('Copy the Allow directives shown below and paste them into your robots.txt file, BEFORE the wildcard Disallow rule.', 'baloa-structure-auditor-seo'),
                __('Each directive explicitly allows a specific AI crawler to access your site.', 'baloa-structure-auditor-seo'),
                __('After editing, verify your robots.txt is accessible at yourdomain.com/robots.txt.', 'baloa-structure-auditor-seo'),
            ],
            'context' => [
                'allow_directives' => $directives,
            ],
        ];
    }

    private static function present_dynamic_crawler( string $id, string $severity, array $context ): ?array {
        if ( str_starts_with( $id, 'crawler_blocked_' ) || str_starts_with( $id, 'crawler_unlisted_' ) || str_starts_with( $id, 'crawler_allowed_' ) ) {
            $bot = (string) ( $context['bot'] ?? '' );

            if ( $severity === 'pass' ) {
                return [
                    'title'   => __('AI crawler policy', 'baloa-structure-auditor-seo'),
                    /* translators: %s: AI crawler name. */
                    'problem' => $bot !== '' ? sprintf( __('AI crawler allowed: %s', 'baloa-structure-auditor-seo'), $bot ) : __('AI crawler is allowed.', 'baloa-structure-auditor-seo'),
                    'why'     => __('Allowing AI crawlers to access your content improves your visibility in AI-generated answers.', 'baloa-structure-auditor-seo'),
                    'how'     => [],
                ];
            }

            $is_blocked = str_starts_with( $id, 'crawler_blocked_' );
            return [
                'title'   => __('AI crawler policy', 'baloa-structure-auditor-seo'),
                'problem' => $bot !== ''
                    ? ( $is_blocked 
                        /* translators: %s: AI crawler name. */
                        ? sprintf( __('AI crawler explicitly blocked: %s', 'baloa-structure-auditor-seo'), $bot ) 
                        /* translators: %s: AI crawler name. */
                        : sprintf( __('AI crawler has no explicit rule: %s', 'baloa-structure-auditor-seo'), $bot ) )
                    : __('AI crawler access issue detected.', 'baloa-structure-auditor-seo'),
                'why'     => __('Blocking or not explicitly allowing AI crawlers reduces the chance of your content being cited in AI-generated responses.', 'baloa-structure-auditor-seo'),
                'how'     => [
                    __('Review and adjust the relevant User-agent rules in your robots.txt file manually.', 'baloa-structure-auditor-seo'),
                ],
            ];
        }

        return null;
    }
}

