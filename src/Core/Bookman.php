<?php
/**
 * BaloaStructureAuditorSEO\Core\Bookman
 *
 * Centralized Glossary and Encyclopedic Dictionary for SEO/GEO/AEO parameters.
 * Provides easy-to-understand definitions, official references, and Good vs Bad examples.
 * Exposes hooks for dynamic extensibility.
 *
 * @package SEO_Structure_Inspector
 * @since   1.0.0
 */

namespace BaloaStructureAuditorSEO\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Bookman {

    /**
     * Master array of all terms tested by the plugin.
     */
    private static ?array $terms = null;

    /**
     * Get the list of all categories with their icons and display names.
     */
    public static function get_categories(): array {
        return [
            'html'        => [ 'name' => __( 'SEO Estructural', 'baloa-structure-auditor-seo' ), 'icon' => '🔧', 'desc' => 'Etiquetas HTML semánticas y jerarquía del documento.' ],
            'llms'        => [ 'name' => __( 'GEO / LLMs', 'baloa-structure-auditor-seo' ), 'icon' => '🌐', 'desc' => 'Optimización para motores de respuesta de IA y archivos robots.' ],
            'aeo'         => [ 'name' => __( 'AEO / Contenido', 'baloa-structure-auditor-seo' ), 'icon' => '📝', 'desc' => 'Formato optimizado para fragmentos destacados y respuestas directas.' ],
            'cwv'         => [ 'name' => __( 'Core Web Vitals', 'baloa-structure-auditor-seo' ), 'icon' => '⚡', 'desc' => 'Rendimiento, estabilidad visual e interactividad de la página.' ],
            'schema'      => [ 'name' => __( 'Schema Markup', 'baloa-structure-auditor-seo' ), 'icon' => '🔗', 'desc' => 'Datos estructurados para clasificar y enriquecer tu contenido.' ],
            'metatags'    => [ 'name' => __( 'Metatags', 'baloa-structure-auditor-seo' ), 'icon' => '🏷️', 'desc' => 'Directivas de indexación y optimización de previews sociales.' ],
            'links'       => [ 'name' => __( 'Enlaces', 'baloa-structure-auditor-seo' ), 'icon' => '⚓', 'desc' => 'Salud de enlaces internos, externos y enlaces rotos.' ],
            'images'      => [ 'name' => __( 'Imágenes', 'baloa-structure-auditor-seo' ), 'icon' => '🖼️', 'desc' => 'Optimización de formatos, nombres de archivo y accesibilidad de imágenes.' ],
            'readability' => [ 'name' => __( 'Legibilidad', 'baloa-structure-auditor-seo' ), 'icon' => '📖', 'desc' => 'Complejidad, fluidez y estructura del texto para humanos e IA.' ],
            'keyword'     => [ 'name' => __( 'EEAT / Keyword', 'baloa-structure-auditor-seo' ), 'icon' => '⭐', 'desc' => 'Ubicación estratégica y densidad de palabras clave clave.' ],
        ];
    }

    /**
     * Initialize and return all glossary terms.
     * Integrates standard WordPress filter 'baloa_structure_auditor_seo_bookman_terms' for full extensibility.
     */
    public static function get_terms(): array {
        if ( self::$terms !== null ) {
            return self::$terms;
        }

        $raw_terms = [
            // ==========================================
            // 1. SEO ESTRUCTURAL (html)
            // ==========================================
            'single_body' => [
                'name' => __( 'Cuerpo único de HTML (<body>)', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Verifica que el documento contenga exactamente una etiqueta <body>.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La etiqueta <body> contiene todo el contenido visible de un sitio web. La especificación HTML dictamina que debe haber un solo elemento <body> en todo el documento para que los navegadores y rastreadores web interpreten correctamente el DOM (Document Object Model).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C HTML Specification',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-body-element',
                'why_it_matters' => __( 'Tener múltiples cuerpos o etiquetas <body> rotas confunde a los motores de búsqueda (como Googlebot) y a los bots de IA (como GPTBot), lo cual puede romper el renderizado móvil, diluir el SEO técnico y causar fallos imprevistos en la velocidad de indexación.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<!DOCTYPE html>\n<html>\n  <head><title>Título</title></head>\n  <body>\n    <main>Contenido principal</main>\n  </body>\n</html>",
                'example_bad' => "<body>\n  <header>Cabecera</header>\n</body>\n<body>\n  <main>Contenido duplicado</main>\n</body>",
                'recommendation' => __( 'Asegúrate de que tus plantillas PHP del tema (header.php y footer.php) solo abran y cierren un elemento <body> global.', 'baloa-structure-auditor-seo' )
            ],
            'single_h1' => [
                'name' => __( 'Encabezado principal único (<h1>)', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Garantiza la presencia de un único H1 representativo en la página.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El H1 es el título semántico supremo de una página web. Sirve para declarar de forma explícita a humanos y rastreadores de qué trata la página. Debe haber exactamente uno por página para evitar la dilución del foco temático.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/article#technical-guidelines',
                'why_it_matters' => __( 'Carecer de H1 deja al sitio sin un título claro para las búsquedas. Por el contrario, usar múltiples H1 diluye las señales semánticas principales y confunde a los resumidores automáticos de inteligencia artificial, reduciendo las posibilidades de ser citado.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h1>Guía Definitiva de SEO para Principiantes</h1>",
                'example_bad' => "<h1>Inicio</h1>\n<h1>¡Oferta especial!</h1>\n<h1>Nuestra Empresa</h1>",
                'recommendation' => __( 'Usa el H1 exclusivamente para el título del artículo o página. Convierte cualquier otro H1 secundario en etiquetas H2 o H3.', 'baloa-structure-auditor-seo' )
            ],
            'heading_hierarchy' => [
                'name' => __( 'Jerarquía de encabezados', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Verifica la correcta presencia y profundidad lógica de los encabezados (H2 antes de H3).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El marcado semántico de encabezados (H1 -> H2 -> H3 -> H4) forma un esquema o índice jerárquico. La regla principal es no romper la jerarquía colocando niveles secundarios antes de que existan niveles principales (por ejemplo, tener un H3 sin un H2 precedente).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Web Accessibility Initiative (WAI)',
                'source_url' => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
                'why_it_matters' => __( 'Una jerarquía impecable facilita enormemente la lectura a usuarios que usan lectores de pantalla. Del mismo modo, ayuda a los algoritmos y LLMs a indexar secciones lógicas del texto, potenciando las respuestas generativas destacadas (GEO).', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h1>Recetas Saludables</h1>\n  <h2>Recetas de Ensaladas</h2>\n    <h3>Ensalada César</h3>\n  <h2>Recetas de Sopas</h2>",
                'example_bad' => "<h1>Recetas Saludables</h1>\n    <h3>Ensalada César</h3>\n  <h2>Recetas de Ensaladas</h2>",
                'recommendation' => __( 'Organiza los encabezados como el índice de un libro. No saltes de H1 directamente a H3 sin pasar por H2.', 'baloa-structure-auditor-seo' )
            ],
            'heading_order' => [
                'name' => __( 'Orden jerárquico consecutivo', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Asegura que no se salten niveles de encabezados (ej. H2 directamente a H4).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Complementario a la jerarquía de encabezados, el orden semántico prohíbe saltar niveles. Pasar de un H2 a un H4 sin pasar por un H3 crea una laguna estructural que rompe la navegación lógica del lector.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Accessibility Guidelines (WCAG)',
                'source_url' => 'https://www.w3.org/TR/WCAG21/#section-headings',
                'why_it_matters' => __( 'Los motores de búsqueda y las IAs de lectura rápida usan estos niveles para fragmentar el texto en "Knowledge Graphs". Si se saltan niveles, el algoritmo podría asociar erróneamente un subtítulo de menor nivel a un contexto equivocado.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h2>Sección de Finanzas</h2>\n<h3>Consejos de Ahorro</h3>\n<h4>Ahorrar en el Hogar</h4>",
                'example_bad' => "<h2>Sección de Finanzas</h2>\n<h4>Ahorrar en el Hogar</h4>",
                'recommendation' => __( 'Revisa la estructura de encabezados en el editor visual de WordPress y asegúrate de que todos sigan una secuencia numérica estricta.', 'baloa-structure-auditor-seo' )
            ],
            'has_footer' => [
                'name' => __( 'Pie de página semántico (<footer>)', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Comprueba la existencia de la etiqueta <footer> para delimitar la sección baja.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El elemento semántico HTML5 <footer> define el pie de página de un documento o sección. Tradicionalmente incluye información de autoría, derechos de autor, enlaces de contacto y navegación accesoria.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C HTML Sectioning Elements',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-footer-element',
                'why_it_matters' => __( 'El uso de etiquetas semánticas universales ayuda a que los bots sepan exactamente dónde termina el contenido de valor (main) y dónde empieza la información corporativa repetitiva (footer), evitando penalizaciones por texto duplicado.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<footer class=\"site-footer\">\n  <p>&copy; 2026 Mi Sitio Web. Todos los derechos reservados.</p>\n</footer>",
                'example_bad' => "<div class=\"footer-container-box\">\n  <p>&copy; 2026 Mi Sitio Web.</p>\n</div>",
                'recommendation' => __( 'Si tu tema web usa divs genéricos en el footer, edita el archivo footer.php de tu tema para envolver este contenido con la etiqueta semántica <footer>.', 'baloa-structure-auditor-seo' )
            ],
            'has_main' => [
                'name' => __( 'Área de contenido principal (<main>)', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Busca que exista una etiqueta <main> para delimitar el núcleo del post.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La etiqueta <main> encierra el contenido de valor único de la página. Excluye barras laterales, menús globales y pies de página. Debe ser único dentro del documento.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C HTML5 Specification',
                'source_url' => 'https://www.w3.org/TR/html52/grouping-content.html#the-main-element',
                'why_it_matters' => __( 'Los crawlers de inteligencia artificial (LLMs) y los lectores de pantalla leen con prioridad lo que hay dentro de la etiqueta <main>. Si no existe, tienen que adivinar dónde empieza el contenido real, lo cual puede diluir el score del post.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<main id=\"main-content\">\n  <h1>Mi Post del Blog</h1>\n  <p>Contenido valioso...</p>\n</main>",
                'example_bad' => "<div class=\"wrapper\">\n  <div class=\"content-area\">\n    <p>Texto sin envoltorio semántico principal...</p>\n  </div>\n</div>",
                'recommendation' => __( 'Envuelve el contenedor central de tu plantilla de artículos (single.php) o páginas (page.php) con la etiqueta semántica <main>.', 'baloa-structure-auditor-seo' )
            ],
            'semantic_tags' => [
                'name' => __( 'Etiquetado semántico secundario', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Detecta el uso de secciones semánticas adicionales como <section> y <article>.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Las etiquetas <article> (contenido independiente) y <section> (agrupación temática) de HTML5 organizan el texto en componentes autónomos de información estructurada.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'MDN Web Docs HTML Semantics',
                'source_url' => 'https://developer.mozilla.org/es/docs/Glossary/Semantics',
                'why_it_matters' => __( 'El uso sistemático de <section> e <article> rompe la "sopa de divs" e indica explícitamente a los buscadores y modelos RAG cómo dividir el documento para extraer fragmentos informativos óptimos.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<article class=\"post-entry\">\n  <section class=\"intro\">Introducción...</section>\n  <section class=\"body\">Desarrollo...</section>\n</article>",
                'example_bad' => "<div class=\"post-entry\">\n  <div class=\"intro\">Introducción...</div>\n  <div class=\"body\">Desarrollo...</div>\n</div>",
                'recommendation' => __( 'Estructura las partes lógicas de tus entradas utilizando bloques semánticos y de contenido, evitando maquetar tu tema exclusivamente con divs.', 'baloa-structure-auditor-seo' )
            ],
            'has_paragraphs' => [
                'name' => __( 'Párrafos estructurados (<p>)', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Asegura que el texto plano esté correctamente etiquetado en bloques <p>.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El elemento <p> representa un párrafo de texto. El contenido de texto nunca debe servirse suelto en el DOM ni separado por múltiples etiquetas <br> consecutivas sin un contenedor lógico de párrafo.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C HTML Paragraphs',
                'source_url' => 'https://www.w3.org/TR/html52/grouping-content.html#the-p-element',
                'why_it_matters' => __( 'Los algoritmos de accesibilidad y los parseadores de lenguaje natural (NLP/LLM) dividen el texto por elementos de párrafo (<p>). Los saltos de línea sueltos impiden la correcta segmentación de la lectura.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<p>Este es el primer párrafo de contenido.</p>\n<p>Este es un segundo bloque de texto separado.</p>",
                'example_bad' => "Este es el primer párrafo.<br><br>Este es el segundo bloque en el mismo div.",
                'recommendation' => __( 'Evita pulsar Shift+Enter repetidamente para simular párrafos en tu editor; usa bloques de párrafo reales que generen etiquetas <p> limpias.', 'baloa-structure-auditor-seo' )
            ],
            'article_in_section' => [
                'name' => __( 'Agrupación semántica anidada', 'baloa-structure-auditor-seo' ),
                'category' => 'html',
                'short_definition' => __( 'Busca que los artículos posean divisiones internas o relaciones <article>/<section>.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Establece una relación semántica lógica anidada donde las subsecciones internas del post o listados anidados se estructuran de forma complementaria utilizando tags específicos.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'HTML5 Sectioning Specification',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-section-element',
                'why_it_matters' => __( 'Aporta legibilidad de jerarquía estructural fina a las IAs cuando procesan páginas muy densas de datos.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<section class=\"blog-list\">\n  <article class=\"card\">Post 1</article>\n  <article class=\"card\">Post 2</article>\n</section>",
                'example_bad' => "<div class=\"blog-list\">\n  <div class=\"card\">Post 1</div>\n  <div class=\"card\">Post 2</div>\n</div>",
                'recommendation' => __( 'En tus listados de blog, usa <section> para englobar el feed de entradas y <article> para la tarjeta individual de cada post.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 2. GEO / LLMS (llms)
            // ==========================================
            'llms_txt_present' => [
                'name' => __( 'Presencia de archivo llms.txt', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Detecta si existe el archivo estándar llms.txt en el directorio raíz.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El archivo llms.txt es una especificación emergente diseñada para proveer información estructurada y concisa en formato Markdown a los modelos de lenguaje (LLM). Ayuda a que las IAs entiendan rápidamente el propósito general de un sitio sin rastrear gigabytes de HTML.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'llms-txt.org',
                'source_url' => 'https://llms-txt.org/',
                'why_it_matters' => __( 'Los buscadores de respuestas de inteligencia artificial (GEO) priorizan los sitios que les facilitan la información limpia. Disponer de un archivo llms.txt de calidad garantiza que las IAs te citen de forma precisa en sus chats conversacionales.', 'baloa-structure-auditor-seo' ),
                'example_good' => "# Título del Sitio\n\n> Breve descripción útil para prompts...\n\n## Secciones clave\n- [Inicio](https://example.com/)",
                'example_bad' => "[Archivo vacío o redirigido a una página 404 de WordPress]",
                'recommendation' => __( 'Usa el auto-fix de este plugin para generar una plantilla estructurada y guardarla para que se sirva en tu-dominio.com/llms.txt.', 'baloa-structure-auditor-seo' )
            ],
            'llms_full_txt_present' => [
                'name' => __( 'Presencia de archivo llms-full.txt', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Busca que exista el archivo de contenido extendido llms-full.txt.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Complementario al llms.txt, el llms-full.txt ofrece una recopilación completa en texto limpio de todo el contenido de valor del sitio web. Sirve para procesos de entrenamiento de agentes de IA o para búsquedas contextuales en profundidad.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'llms-txt.org Standard',
                'source_url' => 'https://llms-txt.org/',
                'why_it_matters' => __( 'Alimentar a los agentes de IA con el texto plano completo previene alucinaciones cuando responden sobre tu marca o servicios especializados.', 'baloa-structure-auditor-seo' ),
                'example_good' => "# Contenido Completo del Sitio\n\nEste archivo recopila toda la documentación...",
                'example_bad' => "[Sin archivo configurado o con error de redirección]",
                'recommendation' => __( 'Define y redacta la información complementaria de tu sitio en el cuadro de auto-fix para generar tu archivo llms-full.txt.', 'baloa-structure-auditor-seo' )
            ],
            'robots_txt_present' => [
                'name' => __( 'Presencia del archivo robots.txt', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Comprueba que exista un archivo robots.txt accesible.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El robots.txt es un protocolo de exclusión voluntario que le dice a los motores de búsqueda y bots qué páginas del sitio tienen permitido rastrear y cuáles no.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Robots.txt',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/robots/intro',
                'why_it_matters' => __( 'Sin un robots.txt adecuado, los motores de búsqueda pueden indexar carpetas del sistema (como wp-admin) o sobrecargar el servidor web con solicitudes de rastreo inútiles.', 'baloa-structure-auditor-seo' ),
                'example_good' => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nSitemap: https://example.com/sitemap_index.xml",
                'example_bad' => "User-agent: *\nDisallow: /",
                'recommendation' => __( 'Asegúrate de que robots.txt exista en la raíz de tu dominio y contenga la ruta correcta al Sitemap de tu sitio.', 'baloa-structure-auditor-seo' )
            ],
            'crawler_allowed_gptbot' => [
                'name' => __( 'Acceso permitido a GPTBot', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Comprueba si la directiva robots.txt permite o bloquea al rastreador de OpenAI (ChatGPT).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'GPTBot es el rastreador oficial de OpenAI para recabar información de la web para entrenar y mejorar modelos como ChatGPT y responder consultas en tiempo real.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'OpenAI Developer Documentation',
                'source_url' => 'https://platform.openai.com/docs/gptbot',
                'why_it_matters' => __( 'Si bloqueas a GPTBot en robots.txt, ChatGPT no conocerá tu negocio, marca o artículos y nunca aparecerás en sus respuestas o recomendaciones recomendadas.', 'baloa-structure-auditor-seo' ),
                'example_good' => "User-agent: GPTBot\nAllow: /",
                'example_bad' => "User-agent: GPTBot\nDisallow: /",
                'recommendation' => __( 'Revisa tu robots.txt y asegúrate de no tener una directiva Disallow para GPTBot.', 'baloa-structure-auditor-seo' )
            ],
            'crawler_allowed_claudebot' => [
                'name' => __( 'Acceso permitido a ClaudeBot', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Comprueba el acceso permitido al rastreador de Anthropic (Claude).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'ClaudeBot es el rastreador de Anthropic diseñado para recopilar información de la web para alimentar a la inteligencia artificial Claude y responder consultas de usuarios.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Anthropic Support Guidelines',
                'source_url' => 'https://support.anthropic.com/en/articles/888888-claudebot',
                'why_it_matters' => __( 'Permitir a ClaudeBot rastrear tu sitio web te posiciona como fuente de autoridad en la plataforma de IA de Claude, la cual posee una enorme masa crítica corporativa.', 'baloa-structure-auditor-seo' ),
                'example_good' => "User-agent: ClaudeBot\nAllow: /",
                'example_bad' => "User-agent: ClaudeBot\nDisallow: /",
                'recommendation' => __( 'Evita la directiva Disallow en robots.txt para ClaudeBot si deseas que los usuarios de Claude descubran tu contenido.', 'baloa-structure-auditor-seo' )
            ],
            'crawler_allowed_google_extended' => [
                'name' => __( 'Acceso permitido a Google-Extended', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Comprueba si se permite a Google usar tu contenido para alimentar Gemini.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Google-Extended es el token que utilizan los creadores web para controlar si Google puede usar el contenido de sus sitios para entrenar modelos Gemini y APIs de IA generativa relacionadas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Google-Extended',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/google-extended',
                'why_it_matters' => __( 'Si se bloquea Google-Extended, impides que Gemini use tus datos para sus resúmenes rápidos interactivos (AI Overviews), lo que reduce drásticamente el tráfico informacional moderno.', 'baloa-structure-auditor-seo' ),
                'example_good' => "User-agent: Google-Extended\nAllow: /",
                'example_bad' => "User-agent: Google-Extended\nDisallow: /",
                'recommendation' => __( 'Permite el acceso a Google-Extended para que tu web siga participando en los resúmenes inteligentes de Google.', 'baloa-structure-auditor-seo' )
            ],
            'geo_meta_position' => [
                'name' => __( 'Posición geográfica (geo.position)', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Verifica la existencia y validez de la etiqueta meta geo.position.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La etiqueta meta geo.position define las coordenadas geográficas precisas (latitud y longitud) del sitio web o del negocio físico que representa. Esto ayuda a los indexadores y mapas a geolocalizar la entidad.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Geotagging Guidelines',
                'source_url' => 'https://www.w3.org/2003/01/geo/',
                'why_it_matters' => __( 'Permite a los motores de búsqueda locales y de respuestas con IA situar con total exactitud las coordenadas físicas del negocio, potenciando su visibilidad en búsquedas locales (búsquedas del tipo "cerca de mí") y mapas.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<meta name="geo.position" content="40.416775;-3.70379">',
                'example_bad' => '<meta name="geo.position" content="latitud, longitud">',
                'recommendation' => __( 'Agrega una etiqueta meta geo.position válida en la cabecera de tu página con formato de punto decimal separado por punto y coma (latitud;longitud).', 'baloa-structure-auditor-seo' )
            ],
            'geo_meta_region' => [
                'name' => __( 'Región geográfica (geo.region)', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Verifica la presencia de la etiqueta meta geo.region en el documento.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La etiqueta meta geo.region especifica la subdivisión política o territorial (código de país y región o provincia) en la que se encuentra el negocio o sitio web (por ejemplo, "ES-M" para Madrid, España).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'ISO 3166-2 standard',
                'source_url' => 'https://www.iso.org/obp/ui/#search',
                'why_it_matters' => __( 'Informa explícitamente el alcance regional del negocio, permitiendo a los algoritmos de respuestas contextuales de IA y buscadores limitar o potenciar los resultados en áreas específicas.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<meta name="geo.region" content="ES-M">',
                'example_bad' => __( '[Sin la etiqueta meta geo.region en el HTML]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Añade la etiqueta <meta name="geo.region" content="código-país-región"> en el <head> de tu web para consolidar la geolocalización regional.', 'baloa-structure-auditor-seo' )
            ],
            'geo_local_schema' => [
                'name' => __( 'Schema de Negocio Local (LocalBusiness)', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Comprueba la existencia de datos estructurados LocalBusiness o similares en el sitio.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El marcado estructurado Schema.org de tipo LocalBusiness (u organizaciones específicas) proporciona metadatos clave sobre un negocio físico, como su dirección, teléfono, horario y coordenadas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Schema.org LocalBusiness Type',
                'source_url' => 'https://schema.org/LocalBusiness',
                'why_it_matters' => __( 'Es el factor definitivo de SEO local y GEO. Las IAs conversacionales (como ChatGPT, Gemini o Claude) extraen los datos estructurados LocalBusiness para dar respuestas de contacto instantáneas e inequívocas.', 'baloa-structure-auditor-seo' ),
                'example_good' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"LocalBusiness\",\n  \"name\": \"Mi Negocio\",\n  \"telephone\": \"+34 912 345 678\",\n  \"address\": {\n    \"@type\": \"PostalAddress\",\n    \"streetAddress\": \"Gran Via 12\"\n  }\n}",
                'example_bad' => __( '[No se detecta ningún bloque JSON-LD con marcado de tipo LocalBusiness]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Genera e inserta datos estructurados LocalBusiness incluyendo de manera precisa Nombre, Dirección y Teléfono.', 'baloa-structure-auditor-seo' )
            ],
            'geo_nap_consistency' => [
                'name' => __( 'Consistencia de datos NAP (Name, Address, Phone)', 'baloa-structure-auditor-seo' ),
                'category' => 'llms',
                'short_definition' => __( 'Verifica la consistencia entre los datos del Schema y la información visible en el sitio.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El principio NAP (Nombre, Dirección, Teléfono) requiere que estos tres identificadores comerciales fundamentales coincidan exactamente entre el Schema (JSON-LD) y el contenido web visible impreso en el cuerpo HTML de la página.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Local SEO Guide',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/local-business',
                'why_it_matters' => __( 'Las discrepancias entre los datos JSON y el texto visible (por ejemplo, tener un teléfono diferente o desactualizado) confunden a los rastreadores y dañan la confianza algorítmica, pudiendo penalizar la clasificación local.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[El teléfono y el nombre coinciden en el Schema y en el HTML visible]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( '[El Schema declara un teléfono pero en la página visible no aparece o tiene un formato o número diferente]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Asegúrate de que la información NAP del Schema de Negocio Local aparezca idéntica y visible en el cuerpo HTML del sitio.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 3. AEO / CONTENIDO (aeo)
            // ==========================================
            'aeo_question_headings' => [
                'name' => __( 'Headings en formato pregunta', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Detecta encabezados redactados como preguntas directas para resolver dudas de búsqueda.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Los encabezados que inician o terminan en signo de interrogación estructuran el texto para responder consultas exactas. Reflejan fielmente el lenguaje natural con el que buscan las personas en Google y asistentes de voz.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central',
                'source_url' => 'https://developers.google.com/search/docs/appearance/featured-snippets',
                'why_it_matters' => __( 'Esta técnica es el método más rápido y efectivo para capturar Featured Snippets (fragmentos destacados) de Google y aparecer en los resúmenes conversacionales de las IAs, las cuales buscan emparejar preguntas del usuario con respuestas explícitas.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h2>¿Qué es el SEO técnico y cómo optimizarlo?</h2>",
                'example_bad' => "<h2>Definición y Optimización del SEO de un sitio web en 2026</h2>",
                'recommendation' => __( 'Añade preguntas claras e interesantes al inicio de tus principales secciones (H2 y H3).', 'baloa-structure-auditor-seo' )
            ],
            'aeo_faq_schema' => [
                'name' => __( 'Schema FAQPage o QAPage', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Detecta si hay datos estructurados JSON-LD que definan preguntas frecuentes.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El Schema `FAQPage` indica formalmente a nivel de código que una página contiene una lista de preguntas frecuentes con sus respectivas respuestas concisas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Schema.org FAQPage Type',
                'source_url' => 'https://schema.org/FAQPage',
                'why_it_matters' => __( 'Las páginas con Schema FAQPage son elegibles para mostrar resultados enriquecidos con acordeones directamente en las SERPs de Google, lo que incrementa sustancialmente el porcentaje de clics (CTR). También ayuda a los LLMs a descargar pares de datos perfectos.', 'baloa-structure-auditor-seo' ),
                'example_good' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"FAQPage\",\n  \"mainEntity\": [{\n    \"@type\": \"Question\",\n    \"name\": \"¿...\",\n    \"acceptedAnswer\": {\n      \"@type\": \"Answer\",\n      \"text\": \"...\"\n    }\n  }]\n}",
                'example_bad' => "[Sin Schema estructurado FAQPage en el código de la página]",
                'recommendation' => __( 'Si tienes secciones de preguntas en tu post, implementa un bloque de acordeón o inyecta el JSON-LD de FAQPage mediante el auto-fix.', 'baloa-structure-auditor-seo' )
            ],
            'aeo_qa_pairs' => [
                'name' => __( 'Pares de Pregunta y Respuesta en DOM', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Verifica que a una pregunta (heading) le siga inmediatamente su respuesta (párrafo).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Audita que el DOM tenga una estructura directa: un encabezado de pregunta (H2 o H3) seguido de manera contigua por un párrafo descriptivo corto (<p>) que funcione como respuesta concisa y resolutiva.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Search Engine Journal AEO Studies',
                'source_url' => 'https://www.searchenginejournal.com/answer-engine-optimization/',
                'why_it_matters' => __( 'Los algoritmos de extracción rápida analizan el DOM buscando este patrón exacto para recortar párrafos e insertarlos como respuestas rápidas (featured snippets) sin necesidad de leer todo el post.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h2>¿Cuánto dura la batería?</h2>\n<p>La batería del dispositivo dura aproximadamente 12 horas con uso moderado.</p>",
                'example_bad' => "<h2>¿Cuánto dura la batería?</h2>\n<div class=\"publicidad\">...</div>\n<p>Bueno, es una pregunta compleja...</p>",
                'recommendation' => __( 'Coloca la respuesta al grano en el primer párrafo directamente debajo de la pregunta jerárquica.', 'baloa-structure-auditor-seo' )
            ],
            'aeo_definitions' => [
                'name' => __( 'Oraciones definitorias directas', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Busca patrones lingüísticos explícitos que definan conceptos ("es un", "se refiere a").', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Analiza si el texto utiliza estructuras semánticas de definición inequívocas, tales como "X es...", "X se define como..." o "X consiste en...".', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Knowledge Graph Search API',
                'source_url' => 'https://developers.google.com/knowledge-graph',
                'why_it_matters' => __( 'Los Knowledge Panels de Google y los resumidores de IA se basan en estas frases directas para definir términos a los usuarios de manera concisa.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<p>La <strong>energía solar</strong> es un recurso renovable obtenido a partir de la radiación electromagnética procedente del Sol.</p>",
                'example_bad' => "<p>Cuando pensamos en el sol y cómo nos da luz, a veces también pensamos en formas de generar electricidad para nuestras casas.</p>",
                'recommendation' => __( 'Al introducir un tema clave, utiliza una frase corta que comience definiéndolo de forma explícita en su formato de diccionario.', 'baloa-structure-auditor-seo' )
            ],
            'aeo_answer_lists' => [
                'name' => __( 'Listas de respuesta estructuradas', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Busca listas ordenadas u ordenadas (ol/ul) justo después de un encabezado.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Detecta si hay listas de viñetas (<ul>) o listas numeradas (<ol>) colocadas directamente debajo de un encabezado temático.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Featured Snippets - List Types',
                'source_url' => 'https://developers.google.com/search/docs/appearance/featured-snippets#snippet-types',
                'why_it_matters' => __( 'Para búsquedas de tipo tutorial o listas de elementos, Google extrae estas viñetas para construir fragmentos destacados de lista ordenada. Son ideales para capturar tráfico informacional altamente cualificado.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h2>Pasos para purgar un radiador</h2>\n<ol>\n  <li>Apaga la calefacción.</li>\n  <li>Usa la llave en la válvula.</li>\n</ol>",
                'example_bad' => "<h2>Cómo purgar un radiador</h2>\nPrimero debes asegurarte de apagar el sistema. Luego tienes que buscar la llave de purgado e insertarla...",
                'recommendation' => __( 'Usa listas y viñetas para enumerar pasos, ingredientes, ventajas o herramientas en lugar de escribir párrafos largos y apelmazados.', 'baloa-structure-auditor-seo' )
            ],
            'aeo_snippet_length' => [
                'name' => __( 'Longitud ideal del fragmento (TL;DR)', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Busca párrafos resumen con una longitud óptima de entre 40 y 60 palabras.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Mide si el documento posee al menos un párrafo conciso con una longitud perfecta de entre 40 y 60 palabras totales, que condense la respuesta principal a una pregunta.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'SEMrush Featured Snippets Research',
                'source_url' => 'https://www.semrush.com/blog/featured-snippets/',
                'why_it_matters' => __( 'El rango de 40-60 palabras es estadísticamente el tamaño preferido por los algoritmos de Google para recortar y mostrar como fragmentos destacados en su buscador de escritorio e internacional.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<p>El SEO es la práctica de optimizar un sitio web para mejorar su visibilidad y posicionamiento en los motores de búsqueda. Esto se logra mediante optimizaciones técnicas, contenido de calidad, análisis de palabras clave y la obtención de enlaces externos autoritarios.</p>",
                'example_bad' => "<p>El SEO es vital y te ayuda mucho. Debes hacerlo hoy mismo para ganar dinero.</p>",
                'recommendation' => __( 'Incluye un resumen ejecutivo "TL;DR" de unas 50 palabras al principio de tu artículo y enmárcalo de forma destacada.', 'baloa-structure-auditor-seo' )
            ],
            'aeo_how_patterns' => [
                'name' => __( 'Patrones instructivos en headings', 'baloa-structure-auditor-seo' ),
                'category' => 'aeo',
                'short_definition' => __( 'Detecta palabras clave instructivas en encabezados ("Cómo", "Guía", "Pasos").', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Audita que el post posea encabezados orientados a la instrucción práctica, utilizando palabras clave disparadoras como "Cómo hacer X", "Guía para X" o "Pasos para solucionar Y".', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - How-to Structured Data',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/how-to',
                'why_it_matters' => __( 'Las intenciones de búsqueda informacionales son las más buscadas. Estos patrones aumentan la tasa de aparición en sistemas de respuesta de IA y búsquedas directas de tutoriales.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<h2>Guía paso a paso para cambiar un neumático</h2>",
                'example_bad' => "<h2>Consideraciones sobre neumáticos en el coche</h2>",
                'recommendation' => __( 'Usa títulos atractivos e instructivos que inviten al usuario a aprender a resolver una tarea paso a paso.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 4. CORE WEB VITALS (cwv)
            // ==========================================
            'cwv_lcp' => [
                'name' => __( 'Largest Contentful Paint (LCP)', 'baloa-structure-auditor-seo' ),
                'category' => 'cwv',
                'short_definition' => __( 'Mide la velocidad de carga percibida del elemento principal (ideal: <2.5s).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El LCP mide el tiempo que tarda en renderizarse el elemento de texto o imagen más grande visible en la pantalla del usuario. Marca el punto donde el contenido principal de la página ya se ha cargado en gran parte.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'web.dev LCP',
                'source_url' => 'https://web.dev/lcp/',
                'why_it_matters' => __( 'Un LCP lento frustra al usuario y aumenta la tasa de rebote instantáneo. Google lo considera un factor de ranking crítico dentro de la Experiencia de Página.', 'baloa-structure-auditor-seo' ),
                'example_good' => "[El banner de la cabecera carga de forma optimizada en 1.8 segundos]",
                'example_bad' => "[Imagen principal gigante sin comprimir que tarda 4.5 segundos en aparecer]",
                'recommendation' => __( 'Comprime tus imágenes principales, implementa WebP/Avif y precarga la imagen LCP mediante la inyección del auto-fix.', 'baloa-structure-auditor-seo' )
            ],
            'cwv_cls' => [
                'name' => __( 'Cumulative Layout Shift (CLS)', 'baloa-structure-auditor-seo' ),
                'category' => 'cwv',
                'short_definition' => __( 'Mide la estabilidad visual de la página durante la carga (ideal: <0.1).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El CLS mide la cantidad de cambios inesperados de diseño que ocurren en la página mientras se está cargando. Ocurre cuando los elementos visibles cambian de posición debido a imágenes sin dimensiones o fuentes lentas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'web.dev CLS',
                'source_url' => 'https://web.dev/cls/',
                'why_it_matters' => __( 'Un CLS alto hace que el usuario pulse botones equivocados por accidente o pierda el hilo de la lectura, degradando de forma severa la usabilidad móvil.', 'baloa-structure-auditor-seo' ),
                'example_good' => "[La página carga sin que el texto o los botones se desplacen ni un píxel]",
                'example_bad' => "[Una publicidad sin dimensiones explícitas carga tarde y desplaza todo el texto hacia abajo]",
                'recommendation' => __( 'Asigna dimensiones de alto y ancho (width y height) explícitas a tus imágenes y reserva espacios con CSS para banners publicitarios.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 5. SCHEMA MARKUP (schema)
            // ==========================================
            'schema_present' => [
                'name' => __( 'Presencia de Schema.org', 'baloa-structure-auditor-seo' ),
                'category' => 'schema',
                'short_definition' => __( 'Verifica la existencia de algún bloque de datos estructurados en la página.', 'baloa-structure-auditor-seo' ),
                'full_definition' => 'El marcado de Schema.org provee un vocabulario estructurado para codificar el contenido de tus páginas web en un formato que las máquinas entiendan perfectamente. Se suele inyectar usando código JSON-LD dentro de etiquetas `' . '`&lt;script&gt;`' . '.',
                'source_name' => 'Schema.org',
                'source_url' => 'https://schema.org',
                'why_it_matters' => __( 'Sin datos estructurados, los buscadores tienen que inferir el tipo de contenido. Schema ayuda a clasificar de forma inequívoca si la página es una Receta, un Producto en venta, una Organización, o un Artículo de prensa.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<' . 'script type="application/ld+json">' . "\n" . '{' . "\n" . '  "@context": "https://schema.org",' . "\n" . '  "@type": "Article",' . "\n" . '  "headline": "Título"' . "\n" . '}' . "\n" . '<' . '/script>',
                'example_bad' => "[No existe ningún bloque de marcado JSON-LD ni microdatos en la página]",
                'recommendation' => __( 'Usa el auto-fix para generar e inyectar el bloque Schema estructurado correspondiente al contenido del post.', 'baloa-structure-auditor-seo' )
            ],
            'schema_json_ld_valid' => [
                'name' => __( 'Validez sintáctica del JSON-LD', 'baloa-structure-auditor-seo' ),
                'category' => 'schema',
                'short_definition' => __( 'Garantiza que el código JSON-LD sea sintácticamente correcto y libre de errores.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Valida que el contenido del script de datos estructurados sea un objeto JSON bien formado, respetando comillas dobles, comas de separación correctas y llaves perfectamente cerradas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Rich Results Test',
                'source_url' => 'https://search.google.com/test/rich-results',
                'why_it_matters' => __( 'Un código JSON con un solo error de sintaxis (como una coma sobrante al final o una comilla rota) queda totalmente inutilizado, lo cual invalida todo el Schema e impide calificar para rich results.', 'baloa-structure-auditor-seo' ),
                'example_good' => "{\n  \"@type\": \"WebPage\",\n  \"name\": \"Inicio\"\n}",
                'example_bad' => "{\n  \"@type\": \"WebPage\",\n  \"name\": \"Inicio\",  <-- Coma ilegal al final\n}",
                'recommendation' => __( 'Comprueba la sintaxis de tus datos estructurados y corrige cualquier fallo tipográfico utilizando el inyector del plugin.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 6. METATAGS (metatags)
            // ==========================================
            'meta_title' => [
                'name' => __( 'Etiqueta Title del documento', 'baloa-structure-auditor-seo' ),
                'category' => 'metatags',
                'short_definition' => __( 'Mide la longitud y presencia del título del navegador (ideal: 30-60 caracteres).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La etiqueta `<title>` define el texto que se muestra en la pestaña del navegador y es el título principal sobre el cual los usuarios hacen clic en los resultados de búsqueda de Google.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Title Link',
                'source_url' => 'https://developers.google.com/search/docs/appearance/title-link',
                'why_it_matters' => __( 'Es el factor SEO On-Page más importante para incitar al clic. Si es muy corto, pierdes espacio informativo; si supera los 60 caracteres, Google lo recortará con puntos suspensivos ("...").', 'baloa-structure-auditor-seo' ),
                'example_good' => "<title>Cómo optimizar el SEO de tu web en 5 pasos sencillos</title>",
                'example_bad' => "<title>Inicio</title>  (Muy corto)\n<title>Esta es la página de inicio donde vendemos muchísimos servicios de marketing digital para tu empresa y tus amigos...</title> (Muy largo)",
                'recommendation' => __( 'Escribe títulos atractivos, descriptivos y de entre 45 y 55 caracteres, situando la palabra clave al principio.', 'baloa-structure-auditor-seo' )
            ],
            'meta_description' => [
                'name' => __( 'Meta Descripción (<meta name="description">)', 'baloa-structure-auditor-seo' ),
                'category' => 'metatags',
                'short_definition' => __( 'Asegura que el fragmento descriptivo tenga la longitud correcta (70-155 caracteres).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La meta descripción es una etiqueta HTML en la cabecera que resume el contenido de la página. Google la muestra habitualmente debajo del enlace de título en la página de resultados.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Snippets Control',
                'source_url' => 'https://developers.google.com/search/docs/appearance/snippet',
                'why_it_matters' => __( 'Aunque no influye de forma directa en el ranking de posicionamiento, una meta descripción persuasiva dispara el porcentaje de clics (CTR). También ayuda a que los resúmenes automáticos de IA entiendan rápido el tema.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<meta name=\"description\" content=\"Descubre cómo auditar el SEO técnico de tu sitio web fácilmente con nuestra guía detallada de 5 pasos prácticos.\">",
                'example_bad' => "<meta name=\"description\" content=\"Hacemos webs.\">",
                'recommendation' => __( 'Redacta un resumen muy vendedor de unas 130 palabras, incluyendo un llamado a la acción persuasivo al final.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 7. ENLACES E IMÁGENES (links)
            // ==========================================
            'links_broken_check' => [
                'name' => __( 'Salud de enlaces externos (sin roturas 404)', 'baloa-structure-auditor-seo' ),
                'category' => 'links',
                'short_definition' => __( 'Busca enlaces caídos o rotos que apunten a páginas inexistentes.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Analiza todos los enlaces salientes para verificar que respondan con un código HTTP de estado exitoso (200 OK) y no con un error de página no encontrada (404 Not Found).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Link Checker Guidelines',
                'source_url' => 'https://validator.w3.org/docs/checklink.html',
                'why_it_matters' => __( 'Los enlaces rotos arruinan la experiencia del usuario y envían una señal de dejadez técnica a los motores de búsqueda, reduciendo la autoridad de enlace transferida (Link Juice).', 'baloa-structure-auditor-seo' ),
                'example_good' => "<a href=\"https://google.com\">Ir a Google</a>",
                'example_bad' => "<a href=\"https://example.com/pagina-borrada-hace-años\">Leer más</a>",
                'recommendation' => __( 'Edita el contenido del artículo para eliminar el enlace roto o redirigirlo a una fuente de información activa y verídica.', 'baloa-structure-auditor-seo' )
            ],
            'images_missing_alt' => [
                'name' => __( 'Atributo alternativo faltante en imágenes (alt)', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Identifica imágenes que carecen por completo de la propiedad alt descriptiva.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El atributo `alt` proporciona una descripción en formato texto del contenido de una imagen. Es interpretado por los lectores de pantalla de personas con discapacidad visual y por los bots indexadores.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Web Accessibility (alt property)',
                'source_url' => 'https://www.w3.org/WAI/tutorials/images/decision-tree/',
                'why_it_matters' => __( 'Una imagen sin alt es invisible para Google Images y degrada severamente la accesibilidad del sitio (fallo WCAG). Las IAs multimediales usan esta descripción para indexar tu contenido gráfico.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<img src=\"perro.jpg\" alt=\"Un perro Golden Retriever corriendo en el parque por el césped\">",
                'example_bad' => "<img src=\"perro.jpg\">",
                'recommendation' => __( 'Usa el auto-fix para rellenar dinámicamente los campos alt de tus imágenes utilizando el título del artículo como fallback automático.', 'baloa-structure-auditor-seo' )
            ],
            'images_empty_alt' => [
                'name' => __( 'Atributo alt vacío en imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Detecta imágenes con el atributo alt vacío (alt="").', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El atributo alt="" vacío es correcto únicamente para imágenes puramente decorativas (fondos, iconos decorativos). Si la imagen aporta información, debe incluir una descripción.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Web Accessibility Tutorials',
                'source_url' => 'https://www.w3.org/WAI/tutorials/images/decorative/',
                'why_it_matters' => __( 'Si una imagen que aporta contenido tiene un alt vacío, se ignora en la accesibilidad y en las búsquedas visuales de Google, perdiendo visibilidad e indexabilidad.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<img src=\"esquema.png\" alt=\"Esquema de la arquitectura limpia del plugin\">",
                'example_bad' => "<img src=\"esquema.png\" alt=\"\">",
                'recommendation' => __( 'Añade una descripción precisa y descriptiva si la imagen aporta valor al artículo, o mantén el alt vacío si es meramente decorativo.', 'baloa-structure-auditor-seo' )
            ],
            'images_generic_filename' => [
                'name' => __( 'Nombres de archivo de imágenes genéricos', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Detecta nombres de archivo poco descriptivos (como image001.jpg).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Los nombres de archivo de imagen deben ser semánticos, descriptivos y usar palabras clave separadas por guiones en lugar de nombres generados automáticamente por cámaras, editores de captura o subidas por defecto.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Images SEO Best Practices',
                'source_url' => 'https://developers.google.com/search/docs/appearance/google-images',
                'why_it_matters' => __( 'Google y los rastreadores semánticos utilizan el nombre de archivo como la primera pista del tema de la imagen. Un nombre genérico como `dsc_123.jpg` pierde toda la relevancia en las búsquedas visuales.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<img src=\"seo-tecnico-wordpress.jpg\" alt=\"Guía de SEO técnico para WordPress\">",
                'example_bad' => "<img src=\"dsc_028392.jpg\" alt=\"Guía de SEO técnico para WordPress\">",
                'recommendation' => __( 'Renombra tus archivos localmente a un formato descriptivo antes de subirlos a tu biblioteca de medios de WordPress.', 'baloa-structure-auditor-seo' )
            ],
            'images_missing_lazy_loading' => [
                'name' => __( 'Carga diferida faltante (lazy loading)', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Busca imágenes sin la directiva loading="lazy" implementada.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La carga diferida (`loading="lazy"`) retrasa la descarga de las imágenes que no aparecen en el primer pliegue de la pantalla hasta que el usuario hace scroll hacia ellas.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'MDN Web Docs - Lazy Loading',
                'source_url' => 'https://developer.mozilla.org/es/docs/Web/Performance/Lazy_loading',
                'why_it_matters' => __( 'Faltar de carga diferida obliga a descargar todas las imágenes al cargar la página, ralentizando el Largest Contentful Paint (LCP) y gastando datos en dispositivos móviles.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<img src=\"banner.webp\" loading=\"lazy\" alt=\"...\">",
                'example_bad' => "<img src=\"banner.webp\" alt=\"...\">",
                'recommendation' => __( 'Añade el atributo loading="lazy" a las imágenes del cuerpo del post. WordPress 5.5+ lo hace por defecto, pero asegúrate de que tu constructor o tema no lo deshabilite.', 'baloa-structure-auditor-seo' )
            ],
            'images_webp_format' => [
                'name' => __( 'Uso de formatos de imagen modernos (WebP/AVIF)', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Comprueba si las imágenes están en formatos tradicionales optimizados (JPG/PNG).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Los formatos modernos como WebP y AVIF proporcionan una compresión muy superior a PNG y JPG, reduciendo drásticamente el peso del archivo sin merma perceptible de calidad.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'web.dev - Use modern image formats',
                'source_url' => 'https://web.dev/uses-webp-images/',
                'why_it_matters' => __( 'Las imágenes tradicionales pesadas son la causa principal de una velocidad de carga lenta en móviles. Google penaliza los tiempos de respuesta lentos en su puntuación de Core Web Vitals.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<img src=\"captura.webp\" alt=\"...\">",
                'example_bad' => "<img src=\"captura.png\" alt=\"...\">",
                'recommendation' => __( 'Convierte tus imágenes a WebP o AVIF antes de subirlas, o usa un plugin de optimización automática en WordPress para convertirlas al vuelo.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 8. LEGIBILIDAD (readability)
            // ==========================================
            'readability_flesch' => [
                'name' => __( 'Índice de Legibilidad Flesch-Szigriszt / Flesch', 'baloa-structure-auditor-seo' ),
                'category' => 'readability',
                'short_definition' => __( 'Evalúa la facilidad de lectura y complejidad sintáctica del texto.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El índice de Flesch mide la complejidad lingüística en base a la longitud media de las oraciones y el número de sílabas por palabra. Un puntaje alto (>60) indica un texto fluido y fácil de comprender para una gran audiencia.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Flesch Reading Ease Standard',
                'source_url' => 'https://www.plainlanguage.gov/guidelines/words/use-simple-words/',
                'why_it_matters' => __( 'Un texto excesivamente denso y académico cansa al lector moderno, aumentando el abandono inmediato de la página. Asimismo, las IAs conversacionales prefieren citar respuestas redactadas con claridad cristalina.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<p>Optimizar el SEO de tu web es sencillo si sigues una lista de pasos ordenados. Primero, comprime tus imágenes para mejorar la carga.</p> (Fluido y directo)",
                'example_bad' => "<p>La optimización metodológica de la indexabilidad algorítmica presupone la previa sustanciación de la compresión volumétrica de los archivos de píxeles.</p> (Innecesariamente complejo)",
                'recommendation' => __( 'Simplifica tu lenguaje. Escribe oraciones cortas, evita los tecnicismos innecesarios y usa palabras directas del día a día.', 'baloa-structure-auditor-seo' )
            ],
            'readability_long_paragraphs' => [
                'name' => __( 'Párrafos excesivamente largos ("Muros de texto")', 'baloa-structure-auditor-seo' ),
                'category' => 'readability',
                'short_definition' => __( 'Detecta bloques de texto enormes sin saltos de línea lógicos.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Identifica párrafos que contienen demasiadas palabras u oraciones complejas seguidas sin un punto y aparte que permita un respiro visual al lector.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Plain Language Guidelines - Paragraphs',
                'source_url' => 'https://www.plainlanguage.gov/guidelines/design/keep-paragraphs-short/',
                'why_it_matters' => __( 'En pantallas móviles de teléfonos móviles, un párrafo de más de 6 líneas se convierte en un "muro de texto" impenetrable que provoca fatiga visual y rebote inmediato del usuario.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<p>Escribir párrafos cortos atrae la atención. Ayuda al usuario a escanear tu web de un vistazo.</p>\n<p>Divide tus ideas en bloques separados de máximo 3 oraciones.</p>",
                'example_bad' => "<p>Escribir en la web requiere saber cómo se comportan las personas porque hoy en día casi nadie lee palabra por palabra sino que escanean de forma rápida y buscan lo importante por lo cual si pones párrafos eternos llenos de comas y sin puntos de descanso la persona simplemente se irá a otra página que le dé la respuesta de forma inmediata y masticada...</p>",
                'recommendation' => __( 'Limita tus párrafos a un máximo de 3 o 4 líneas de longitud antes de introducir un salto de párrafo lógico.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 9. EEAT / KEYWORD (keyword)
            // ==========================================
            'kw_density' => [
                'name' => __( 'Densidad ideal de Palabra Clave', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Mide el porcentaje de aparición de la palabra clave en el contenido (ideal: 0.5% - 2.5%).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Calcula cuántas veces aparece la frase de palabra clave objetivo en relación al total de palabras del post.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Keyword Stuffing avoidance',
                'source_url' => 'https://developers.google.com/search/docs/essentials/spam-policies#keyword-stuffing',
                'why_it_matters' => __( 'Si la palabra clave aparece muy poco, Google no sabrá que el post se especializa en ella. Si aparece demasiado (>3%), se incurre en "Keyword Stuffing", una práctica penalizada por los algoritmos antispam.', 'baloa-structure-auditor-seo' ),
                'example_good' => "[Uso natural del término \"SEO para WordPress\" unas 8 veces en un artículo de 1000 palabras (0.8%)]",
                'example_bad' => "\"Somos el mejor SEO para WordPress porque nuestro SEO para WordPress optimiza tu SEO para WordPress y hace que tu SEO para WordPress sea el mejor de la red.\"",
                'recommendation' => __( 'Escribe de forma natural para humanos. Usa sinónimos y variaciones semánticas de tu palabra clave en lugar de repetirla idéntica hasta el cansancio.', 'baloa-structure-auditor-seo' )
            ],
            'kw_position_title' => [
                'name' => __( 'Palabra clave en el Title Tag', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Verifica que la palabra clave objetivo aparezca dentro de la etiqueta `<title>`.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Comprueba que la frase de búsqueda exacta introducida esté presente en el Title Tag, preferiblemente en los primeros caracteres.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Search Engine Land Title Guidelines',
                'source_url' => 'https://searchengineland.com/seo-best-practices-titles-descriptions/',
                'why_it_matters' => __( 'Tener la palabra clave al inicio del título es uno de los factores de relevancia más determinantes para indexar y posicionar para dicha palabra clave exacta.', 'baloa-structure-auditor-seo' ),
                'example_good' => "<title>SEO para WordPress: Guía de Optimización Definitiva</title>",
                'example_bad' => "<title>Guía Completa para tu Blog y Consejos Web</title> (Falta la palabra clave objetivo)",
                'recommendation' => __( 'Edita el Title Tag y asegúrate de incluir tu frase clave de manera natural lo más a la izquierda posible.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 10. IMÁGENES (images)
            // ==========================================
            'images_present' => [
                'name' => __( 'Presencia de imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Comprueba si la página contiene imágenes para enriquecer el contenido.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Las imágenes complementan el texto, mejorando el engagement de los usuarios y permitiendo que los rastreadores web indexen el contenido en búsquedas visuales.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Image SEO',
                'source_url' => 'https://developers.google.com/search/docs/appearance/google-images',
                'why_it_matters' => __( 'Un artículo sin imágenes puede ser menos atractivo visualmente y tiene menos posibilidades de aparecer en Google Images o Discover.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="mi-articulo-ejemplo.webp" alt="Ejemplo de optimización de imágenes">',
                'example_bad' => __( '[No se detectó ninguna etiqueta img en el cuerpo de la página]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Añade imágenes relevantes y descriptivas a tu contenido para mejorar la experiencia de usuario y capturar tráfico orgánico.', 'baloa-structure-auditor-seo' )
            ],
            'images_missing_alt' => [
                'name' => __( 'Atributo alt ausente en imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Detecta imágenes que no tienen configurado el atributo alt.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El atributo alt (texto alternativo) es fundamental para describir el contenido de una imagen a los motores de búsqueda y a las herramientas de accesibilidad (lectores de pantalla).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Web Accessibility Initiative (WAI)',
                'source_url' => 'https://www.w3.org/WAI/tutorials/images/',
                'why_it_matters' => __( 'La ausencia del atributo alt impide que las personas con discapacidad visual entiendan la imagen y diluye la relevancia semántica de la página para los buscadores.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="computadora-portatil.jpg" alt="Laptop plateada sobre un escritorio de madera">',
                'example_bad' => '<img src="computadora-portatil.jpg">',
                'recommendation' => __( 'Agrega un atributo alt descriptivo a todas las imágenes importantes de la página.', 'baloa-structure-auditor-seo' )
            ],
            'images_empty_alt' => [
                'name' => __( 'Atributo alt vacío en imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Comprueba si hay imágenes con el atributo alt vacío (alt="").', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Un atributo alt vacío es válido de acuerdo con el estándar W3C únicamente si la imagen es puramente decorativa y no aporta valor informativo al contenido.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'W3C Web Accessibility Tutorials',
                'source_url' => 'https://www.w3.org/WAI/tutorials/images/decorative/',
                'why_it_matters' => __( 'Si una imagen aporta contexto o información relevante y tiene el alt vacío, los motores de búsqueda y los lectores de pantalla la ignorarán, perdiendo relevancia temática.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="icono-adorno.png" alt=""> (solo si es decorativa)',
                'example_bad' => '<img src="grafico-ventas-2026.png" alt=""> (incorrecto si muestra datos)',
                'recommendation' => __( 'Asegúrate de que las imágenes informativas tengan un texto descriptivo en su atributo alt. Deja el alt vacío únicamente para adornos o divisores visuales.', 'baloa-structure-auditor-seo' )
            ],
            'images_generic_filename' => [
                'name' => __( 'Nombres de archivo genéricos en imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Detecta nombres de archivo sin valor semántico (ej. image01.jpg, dsc_9012.png).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'El nombre del archivo de la imagen es otra señal de relevancia para Google. Utilizar nombres descriptivos ayuda a posicionar la imagen y el artículo para términos de búsqueda relevantes.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Images SEO Best Practices',
                'source_url' => 'https://developers.google.com/search/docs/appearance/google-images#use-descriptive-filenames',
                'why_it_matters' => __( 'Nombres como dsc_0283.jpg o image.png no proporcionan información contextual sobre el elemento, perdiendo una oportunidad clave para el posicionamiento orgánico.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="seo-auditoria-paso-a-paso.jpg" alt="Auditoría de SEO paso a paso">',
                'example_bad' => '<img src="IMG_48123.jpg" alt="Auditoría de SEO paso a paso">',
                'recommendation' => __( 'Renombra los archivos de tus imágenes para que incluyan palabras clave separadas por guiones antes de subirlas a WordPress.', 'baloa-structure-auditor-seo' )
            ],
            'images_missing_lazy_loading' => [
                'name' => __( 'Carga diferida ausente (Lazy Loading)', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Comprueba si se utiliza loading="lazy" en las imágenes del sitio.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La carga diferida pospone la descarga de imágenes que no están visibles en el pliegue inicial (viewport) hasta que el usuario se desplaza cerca de ellas, mejorando el rendimiento de carga.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'MDN Web Docs - Browser-level lazy loading',
                'source_url' => 'https://developer.mozilla.org/en-US/docs/Web/Performance/Lazy_loading',
                'why_it_matters' => __( 'Cargar todas las imágenes al mismo tiempo ralentiza la velocidad percibida de la página, afectando negativamente métricas Core Web Vitals como LCP (Largest Contentful Paint).', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="imagen-larga.jpg" loading="lazy" alt="Ejemplo de lazy loading">',
                'example_bad' => '<img src="imagen-larga.jpg" alt="Sin loading lazy">',
                'recommendation' => __( 'Añade el atributo loading="lazy" a todas las imágenes secundarias o que se encuentren por debajo del pliegue de inicio.', 'baloa-structure-auditor-seo' )
            ],
            'images_webp_format' => [
                'name' => __( 'Formatos modernos de imágenes', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Verifica si se utilizan formatos de imagen optimizados (WebP o AVIF).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Los formatos modernos como WebP y AVIF ofrecen una compresión superior a la de JPEG y PNG, logrando archivos notablemente más pequeños sin sacrificar calidad de imagen.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'web.dev - Use WebP images',
                'source_url' => 'https://web.dev/serve-images-webp/',
                'why_it_matters' => __( 'Servir imágenes pesadas en formatos antiguos (PNG/JPG) aumenta el tiempo de descarga, consume más ancho de banda y perjudica el score global de rendimiento del sitio.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<img src="grafico-ventas.webp" alt="Gráfico de ventas en WebP">',
                'example_bad' => '<img src="grafico-ventas.png" alt="Gráfico de ventas en PNG pesado">',
                'recommendation' => __( 'Convierte y sube tus imágenes en formato WebP o AVIF para optimizar significativamente los tiempos de carga.', 'baloa-structure-auditor-seo' )
            ],
            'images_with_alt' => [
                'name' => __( 'Imágenes con alt correcto', 'baloa-structure-auditor-seo' ),
                'category' => 'images',
                'short_definition' => __( 'Confirma el porcentaje de imágenes que tienen su texto alternativo configurado.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Muestra la relación de imágenes que cumplen satisfactoriamente con la regla de poseer un texto alternativo alt descriptivo.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Images SEO Guide',
                'source_url' => 'https://developers.google.com/search/docs/appearance/google-images',
                'why_it_matters' => __( 'Mantener una tasa del 100% de imágenes con alt descriptivo asegura el máximo potencial de accesibilidad y SEO en el sitio.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( 'Todas las imágenes de la página tienen configurado su alt descriptivo.', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( 'Parte de las imágenes de la página no contienen el atributo alt.', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Continúa manteniendo todas tus imágenes con textos alt descriptivos y enfocados.', 'baloa-structure-auditor-seo' )
            ],

            // ==========================================
            // 11. E-E-A-T (llms / keyword)
            // ==========================================
            'eeat_date_visible' => [
                'name' => __( 'Fecha de publicación o actualización visible', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Detecta la visibilidad de fechas para confirmar la frescura del contenido.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Google y los usuarios valoran que el contenido esté actualizado. Mostrar la fecha de publicación o de última actualización de forma visible en el documento demuestra transparencia y frescura temática.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Quality Rater Guidelines - E-E-A-T',
                'source_url' => 'https://developers.google.com/search/docs/appearance/page-experience',
                'why_it_matters' => __( 'El contenido obsoleto o sin fecha visible pierde credibilidad ante los usuarios y puede ser ignorado por algoritmos que prefieren información fresca y actualizada.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<span>Actualizado el 5 de Junio de 2026</span>',
                'example_bad' => __( '[El artículo no muestra ninguna fecha de publicación ni de modificación visible]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Muestra claramente la fecha de publicación o de última actualización al principio o al final de tus artículos.', 'baloa-structure-auditor-seo' )
            ],
            'eeat_author_visible' => [
                'name' => __( 'Autor identificado', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Busca la presencia del nombre o enlace del autor en la página.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Identificar de forma inequívoca quién ha escrito el contenido es un pilar fundamental de la confianza (Trust) en E-E-A-T. Aporta transparencia sobre la procedencia y responsabilidad de la información.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google E-E-A-T Guidelines - Author Authority',
                'source_url' => 'https://static.googleusercontent.com/media/guidelines.raterhub.com/en//searchqualityevaluatorguidelines.pdf',
                'why_it_matters' => __( 'Las publicaciones anónimas generan desconfianza tanto en los usuarios como en los algoritmos evaluadores de calidad de los buscadores.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<a href="/autor/pedro-perez" rel="author">Escrito por Pedro Pérez</a>',
                'example_bad' => __( '[Falta mención al autor del contenido en la página]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Agrega el nombre del autor en un lugar destacado del artículo, preferiblemente con un enlace a su biografía o perfil profesional.', 'baloa-structure-auditor-seo' )
            ],
            'eeat_about_linked' => [
                'name' => __( 'Enlace a página de Quiénes Somos', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Detecta la existencia de un enlace a la sección Sobre nosotros o Quiénes somos.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'La página "Sobre nosotros" o "Quiénes somos" proporciona la historia, misión y legitimación detrás del sitio web o marca. Enlazar a ella demuestra transparencia y respalda la autoría general del sitio.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Quality Rater Guidelines - About Us Presence',
                'source_url' => 'https://developers.google.com/search/docs/appearance/page-experience',
                'why_it_matters' => __( 'Un sitio web sin información corporativa clara o sin una página sobre los creadores es considerado de menor confianza por los evaluadores de calidad.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<a href="https://ejemplo.com/sobre-nosotros">Sobre nosotros</a>',
                'example_bad' => __( '[No hay ningún enlace en el menú o cuerpo que dirija a una página corporativa o biográfica]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Asegúrate de tener un enlace visible (por ejemplo, en el menú de navegación o en el footer) que dirija a tu página de "Quiénes somos" o "Sobre mí".', 'baloa-structure-auditor-seo' )
            ],
            'eeat_credentials_mentioned' => [
                'name' => __( 'Menciones de credenciales o certificaciones', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Comprueba si se mencionan títulos o certificaciones que validen la experiencia (Expertise).', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Mencionar explícitamente los títulos, grados académicos, certificaciones o trayectoria del autor o de la empresa valida la experiencia (Expertise) y la autoridad (Authoritativeness) necesarias para tratar el tema, especialmente en temáticas YMYL (Your Money or Your Life).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Quality Rater Guidelines - YMYL & E-E-A-T',
                'source_url' => 'https://developers.google.com/search/docs/appearance/page-experience',
                'why_it_matters' => __( 'Para temas sensibles como salud, finanzas o legal, la ausencia de credenciales explícitas del autor puede restar valor y fiabilidad al contenido frente a los algoritmos de Google.', 'baloa-structure-auditor-seo' ),
                'example_good' => '<p>Pedro Pérez es Ingeniero de Software certificado en Ciberseguridad...</p>',
                'example_bad' => '<p>Pedro Pérez escribe sobre cómo proteger tu red doméstica de ataques...</p> (Sin mencionar credenciales o experiencia)',
                'recommendation' => __( 'Menciona brevemente tus credenciales profesionales, certificaciones o años de experiencia en la bio del autor o dentro del cuerpo del artículo.', 'baloa-structure-auditor-seo' )
            ],
            'eeat_content_decayed' => [
                'name' => __( 'Obsolescencia de contenido (Content Decay)', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Identifica si el artículo tiene más de 180 días sin recibir actualizaciones.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Google prioriza los contenidos actualizados y frescos, especialmente en temas informativos o que cambian rápido. Mantener un artículo sin cambios durante mucho tiempo reduce su frescura (Query Deserves Freshness).', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Central - Freshness Algorithm Update',
                'source_url' => 'https://developers.google.com/search/docs/appearance/page-experience',
                'why_it_matters' => __( 'La pérdida gradual de posiciones en Google (Content Decay) ocurre a menudo cuando un post envejece y los competidores publican información más actualizada.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[El post fue actualizado hace 15 días con datos frescos del sector]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( '[El post tiene más de 6 meses desde su última modificación]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Añade nuevos datos, revisa los enlaces y vuelve a guardar el post para reactivar su frescura en los buscadores.', 'baloa-structure-auditor-seo' )
            ],
            'entities_coverage' => [
                'name' => __( 'Cobertura de entidades semánticas', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Mide el porcentaje de entidades temáticas clave cubiertas en tu artículo.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Compara las entidades y conceptos presentes en el texto con un diccionario temático de tu nicho para evaluar si el contenido cubre el tema de forma exhaustiva.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Relations - Entities & Knowledge Graph',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data',
                'why_it_matters' => __( 'Google y los LLMs indexan la información basándose en entidades y relaciones semánticas. Una alta cobertura temática incrementa de forma crítica la relevancia y autoridad del contenido.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[El post sobre SEO cubre términos clave como backlinks, posicionamiento, palabras clave, e indexación]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( '[El post habla sobre SEO pero carece de menciones de conceptos cruciales como indexación o backlinks]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Incorpora los términos secundarios y conceptos recomendados de tu nicho para fortalecer la autoridad temática de tu página.', 'baloa-structure-auditor-seo' )
            ],
            'entity_missing_critical' => [
                'name' => __( 'Entidades críticas ausentes', 'baloa-structure-auditor-seo' ),
                'category' => 'keyword',
                'short_definition' => __( 'Identifica las entidades temáticas de alta relevancia ausentes en el texto.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Enumera los conceptos semánticos del nicho que están completamente omitidos en el contenido actual, pero que los motores de búsqueda esperan encontrar contiguamente.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'SEMrush Topical Authority Guide',
                'source_url' => 'https://www.semrush.com/blog/topical-authority/',
                'why_it_matters' => __( 'Omitir términos esenciales debilita la señal de autoridad temática de la página, reduciendo las posibilidades de figurar como fuente principal.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[El texto contiene menciones de todas las entidades secundarias recomendadas]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( '[Faltan menciones a conceptos de apoyo necesarios en el nicho del artículo]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Añade de forma natural explicaciones o párrafos que incluyan los conceptos críticos que faltan en tu artículo.', 'baloa-structure-auditor-seo' )
            ],
            'naturalness_score' => [
                'name' => __( 'Índice de escritura natural', 'baloa-structure-auditor-seo' ),
                'category' => 'readability',
                'short_definition' => __( 'Evalúa si el contenido se lee de manera fluida y humana o si suena automatizado por IA.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Analiza la presencia de redundancias, frases de transición exageradamente formales y patrones de muletillas típicos de la generación masiva por LLMs.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Google Search Quality Evaluator Guidelines - Original Content',
                'source_url' => 'https://developers.google.com/search/docs/fundamentals/creating-helpful-content',
                'why_it_matters' => __( 'El contenido que abusa de muletillas de IA es calificado de baja calidad o "spam" por los algoritmos automatizados y cansa a los lectores humanos.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[El post está redactado de manera directa, usando ejemplos y tono natural]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( '[El post repite muletillas como "en el panorama actual" o "es importante destacar"]', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Reescribe las secciones monótonas y remueve las muletillas detectadas para dotar al texto de un estilo de autor único y natural.', 'baloa-structure-auditor-seo' )
            ],
            'naturalness_cliches_detected' => [
                'name' => __( 'Clichés de IA identificados', 'baloa-structure-auditor-seo' ),
                'category' => 'readability',
                'short_definition' => __( 'Lista de muletillas y frases robóticas encontradas en el texto.', 'baloa-structure-auditor-seo' ),
                'full_definition' => __( 'Identifica de forma exacta los términos y n-gramas detectados que corresponden a plantillas de redacción de IA para su rápida remoción.', 'baloa-structure-auditor-seo' ),
                'source_name' => 'Wired - How to spot AI writing',
                'source_url' => 'https://www.wired.com/',
                'why_it_matters' => __( 'Eliminar muletillas robóticas mejora sustancialmente el enganche del usuario, reduciendo el rebote de lectura y optimizando la legibilidad de la marca.', 'baloa-structure-auditor-seo' ),
                'example_good' => __( '[No se detectaron clichés típicos de IA]', 'baloa-structure-auditor-seo' ),
                'example_bad' => __( 'Múltiples apariciones de frases vacías como "cabe destacar" o "en resumen".', 'baloa-structure-auditor-seo' ),
                'recommendation' => __( 'Reemplaza estas frases cliché por conectores textuales más variados y directos.', 'baloa-structure-auditor-seo' )
            ]
        ];

        // Ensure we support the full list by auto-filling default representations for missing ones
        // or letting developers extend/filter the array using a WordPress filter.
        self::$terms = apply_filters( 'baloa_structure_auditor_seo_bookman_terms', $raw_terms );

        return self::$terms;
    }

    /**
     * Get details of a single term by ID.
     */
    public static function get_term( string $id ): ?array {
        $terms = self::get_terms();
        if ( isset( $terms[ $id ] ) ) {
            $term = $terms[ $id ];
            $term['id'] = $id;
            return $term;
        }

        // Return fallback for dynamic keys (like crawler_allowed_*, schema_field_*, schema_complete_*)
        if ( str_starts_with( $id, 'crawler_allowed_' ) ) {
            $bot = ucwords( str_replace( 'crawler_allowed_', '', $id ) );
            return [
                'id' => $id,
                'name' => sprintf( 'Crawler de IA permitido: %s', $bot ),
                'category' => 'llms',
                'short_definition' => sprintf( 'Comprueba si se permite al rastreador de IA "%s" indexar tu web.', $bot ),
                'full_definition' => sprintf( 'Este bot corresponde al crawler automatizado que utiliza la empresa de inteligencia artificial para recabar datos con los que responder a sus usuarios. Permitir este bot ayuda a aparecer en sus fuentes.', $bot ),
                'source_name' => 'Robots.txt Specifications',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/robots/intro',
                'why_it_matters' => __( 'Los buscadores generativos de IA necesitan leer el contenido de tu web para citarte. Si los bloqueas en el archivo robots.txt, pierdes toda presencia en sus chats.', 'baloa-structure-auditor-seo' ),
                'example_good' => "User-agent: *\nAllow: /",
                'example_bad' => sprintf( "User-agent: %s\nDisallow: /", $bot ),
                'recommendation' => __( 'Asegúrate de no bloquear este bot en las directivas de tu archivo robots.txt.', 'baloa-structure-auditor-seo' )
            ];
        }

        if ( str_starts_with( $id, 'schema_field_' ) ) {
            $parts = explode( '_', $id );
            $field = end( $parts );
            return [
                'id' => $id,
                'name' => sprintf( 'Propiedad Schema recomendada: %s', $field ),
                'category' => 'schema',
                'short_definition' => sprintf( 'Comprueba si tu bloque de marcado Schema incluye el campo recomendado "%s".', $field ),
                'full_definition' => sprintf( 'Schema.org define campos obligatorios y recomendados para cada tipo de entidad. La propiedad "%s" es recomendada para enriquecer el contexto del objeto y clasificarlo mejor.', $field ),
                'source_name' => 'Schema.org Specifications',
                'source_url' => 'https://schema.org',
                'why_it_matters' => __( 'Tener esquemas completos incrementa notablemente la elegibilidad para obtener fragmentos enriquecidos (Rich Results) en Google y previene advertencias críticas en Search Console.', 'baloa-structure-auditor-seo' ),
                'example_good' => sprintf( "\"%s\": \"Valor válido descriptivo\"", $field ),
                'example_bad' => sprintf( "[La propiedad \"%s\" está completamente omitida en el objeto JSON-LD]", $field ),
                'recommendation' => __( 'Añade esta propiedad con información verídica a tus datos estructurados JSON-LD.', 'baloa-structure-auditor-seo' )
            ];
        }

        return null;
    }

    /**
     * Get Bookman data enriched for recommendation generation.
     * Returns only terms that match the given check IDs.
     *
     * @param string[] $check_ids Array of failed check IDs.
     * @return array Matching terms with full recommendation data.
     */
    public static function get_recommendations_for_checks( array $check_ids ): array {
        $result = [];
        foreach ( $check_ids as $id ) {
            $term = self::get_term( $id );
            if ( $term ) {
                $result[ $id ] = $term;
            }
        }
        return $result;
    }
}
