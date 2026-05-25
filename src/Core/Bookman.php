<?php
/**
 * SEOSI\Core\Bookman
 *
 * Centralized Glossary and Encyclopedic Dictionary for SEO/GEO/AEO parameters.
 * Provides easy-to-understand definitions, official references, and Good vs Bad examples.
 * Exposes hooks for dynamic extensibility.
 *
 * @package SEO_Structure_Inspector
 * @since   1.0.0
 */

namespace SEOSI\Core;

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
            'html'        => [ 'name' => 'SEO Estructural', 'icon' => '🔧', 'desc' => 'Etiquetas HTML semánticas y jerarquía del documento.' ],
            'llms'        => [ 'name' => 'GEO / LLMs', 'icon' => '🌐', 'desc' => 'Optimización para motores de respuesta de IA y archivos robots.' ],
            'aeo'         => [ 'name' => 'AEO / Contenido', 'icon' => '📝', 'desc' => 'Formato optimizado para fragmentos destacados y respuestas directas.' ],
            'cwv'         => [ 'name' => 'Core Web Vitals', 'icon' => '⚡', 'desc' => 'Rendimiento, estabilidad visual e interactividad de la página.' ],
            'schema'      => [ 'name' => 'Schema Markup', 'icon' => '🔗', 'desc' => 'Datos estructurados para clasificar y enriquecer tu contenido.' ],
            'metatags'    => [ 'name' => 'Metatags', 'icon' => '🏷️', 'desc' => 'Directivas de indexación y optimización de previews sociales.' ],
            'links'       => [ 'name' => 'Enlaces e Imágenes', 'icon' => '⚓', 'desc' => 'Salud de enlaces internos/externos y accesibilidad de imágenes.' ],
            'readability' => [ 'name' => 'Legibilidad', 'icon' => '📖', 'desc' => 'Complejidad, fluidez y estructura del texto para humanos e IA.' ],
            'keyword'     => [ 'name' => 'EEAT / Keyword', 'icon' => '⭐', 'desc' => 'Ubicación estratégica y densidad de palabras clave clave.' ],
        ];
    }

    /**
     * Initialize and return all glossary terms.
     * Integrates standard WordPress filter 'seosi_bookman_terms' for full extensibility.
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
                'name' => 'Cuerpo único de HTML (<body>)',
                'category' => 'html',
                'short_definition' => 'Verifica que el documento contenga exactamente una etiqueta <body>.',
                'full_definition' => 'La etiqueta <body> contiene todo el contenido visible de un sitio web. La especificación HTML dictamina que debe haber un solo elemento <body> en todo el documento para que los navegadores y rastreadores web interpreten correctamente el DOM (Document Object Model).',
                'source_name' => 'W3C HTML Specification',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-body-element',
                'why_it_matters' => 'Tener múltiples cuerpos o etiquetas <body> rotas confunde a los motores de búsqueda (como Googlebot) y a los bots de IA (como GPTBot), lo cual puede romper el renderizado móvil, diluir el SEO técnico y causar fallos imprevistos en la velocidad de indexación.',
                'example_good' => "<!DOCTYPE html>\n<html>\n  <head><title>Título</title></head>\n  <body>\n    <main>Contenido principal</main>\n  </body>\n</html>",
                'example_bad' => "<body>\n  <header>Cabecera</header>\n</body>\n<body>\n  <main>Contenido duplicado</main>\n</body>",
                'recommendation' => 'Asegúrate de que tus plantillas PHP del tema (header.php y footer.php) solo abran y cierren un elemento <body> global.'
            ],
            'single_h1' => [
                'name' => 'Encabezado principal único (<h1>)',
                'category' => 'html',
                'short_definition' => 'Garantiza la presencia de un único H1 representativo en la página.',
                'full_definition' => 'El H1 es el título semántico supremo de una página web. Sirve para declarar de forma explícita a humanos y rastreadores de qué trata la página. Debe haber exactamente uno por página para evitar la dilución del foco temático.',
                'source_name' => 'Google Search Central',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/article#technical-guidelines',
                'why_it_matters' => 'Carecer de H1 deja al sitio sin un título claro para las búsquedas. Por el contrario, usar múltiples H1 diluye las señales semánticas principales y confunde a los resumidores automáticos de inteligencia artificial, reduciendo las posibilidades de ser citado.',
                'example_good' => "<h1>Guía Definitiva de SEO para Principiantes</h1>",
                'example_bad' => "<h1>Inicio</h1>\n<h1>¡Oferta especial!</h1>\n<h1>Nuestra Empresa</h1>",
                'recommendation' => 'Usa el H1 exclusivamente para el título del artículo o página. Convierte cualquier otro H1 secundario en etiquetas H2 o H3.'
            ],
            'heading_hierarchy' => [
                'name' => 'Jerarquía de encabezados',
                'category' => 'html',
                'short_definition' => 'Verifica la correcta presencia y profundidad lógica de los encabezados (H2 antes de H3).',
                'full_definition' => 'El marcado semántico de encabezados (H1 -> H2 -> H3 -> H4) forma un esquema o índice jerárquico. La regla principal es no romper la jerarquía colocando niveles secundarios antes de que existan niveles principales (por ejemplo, tener un H3 sin un H2 precedente).',
                'source_name' => 'W3C Web Accessibility Initiative (WAI)',
                'source_url' => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
                'why_it_matters' => 'Una jerarquía impecable facilita enormemente la lectura a usuarios que usan lectores de pantalla. Del mismo modo, ayuda a los algoritmos y LLMs a indexar secciones lógicas del texto, potenciando las respuestas generativas destacadas (GEO).',
                'example_good' => "<h1>Recetas Saludables</h1>\n  <h2>Recetas de Ensaladas</h2>\n    <h3>Ensalada César</h3>\n  <h2>Recetas de Sopas</h2>",
                'example_bad' => "<h1>Recetas Saludables</h1>\n    <h3>Ensalada César</h3>\n  <h2>Recetas de Ensaladas</h2>",
                'recommendation' => 'Organiza los encabezados como el índice de un libro. No saltes de H1 directamente a H3 sin pasar por H2.'
            ],
            'heading_order' => [
                'name' => 'Orden jerárquico consecutivo',
                'category' => 'html',
                'short_definition' => 'Asegura que no se salten niveles de encabezados (ej. H2 directamente a H4).',
                'full_definition' => 'Complementario a la jerarquía de encabezados, el orden semántico prohíbe saltar niveles. Pasar de un H2 a un H4 sin pasar por un H3 crea una laguna estructural que rompe la navegación lógica del lector.',
                'source_name' => 'W3C Accessibility Guidelines (WCAG)',
                'source_url' => 'https://www.w3.org/TR/WCAG21/#section-headings',
                'why_it_matters' => 'Los motores de búsqueda y las IAs de lectura rápida usan estos niveles para fragmentar el texto en "Knowledge Graphs". Si se saltan niveles, el algoritmo podría asociar erróneamente un subtítulo de menor nivel a un contexto equivocado.',
                'example_good' => "<h2>Sección de Finanzas</h2>\n<h3>Consejos de Ahorro</h3>\n<h4>Ahorrar en el Hogar</h4>",
                'example_bad' => "<h2>Sección de Finanzas</h2>\n<h4>Ahorrar en el Hogar</h4>",
                'recommendation' => 'Revisa la estructura de encabezados en el editor visual de WordPress y asegúrate de que todos sigan una secuencia numérica estricta.'
            ],
            'has_footer' => [
                'name' => 'Pie de página semántico (<footer>)',
                'category' => 'html',
                'short_definition' => 'Comprueba la existencia de la etiqueta <footer> para delimitar la sección baja.',
                'full_definition' => 'El elemento semántico HTML5 <footer> define el pie de página de un documento o sección. Tradicionalmente incluye información de autoría, derechos de autor, enlaces de contacto y navegación accesoria.',
                'source_name' => 'W3C HTML Sectioning Elements',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-footer-element',
                'why_it_matters' => 'El uso de etiquetas semánticas universales ayuda a que los bots sepan exactamente dónde termina el contenido de valor (main) y dónde empieza la información corporativa repetitiva (footer), evitando penalizaciones por texto duplicado.',
                'example_good' => "<footer class=\"site-footer\">\n  <p>&copy; 2026 Mi Sitio Web. Todos los derechos reservados.</p>\n</footer>",
                'example_bad' => "<div class=\"footer-container-box\">\n  <p>&copy; 2026 Mi Sitio Web.</p>\n</div>",
                'recommendation' => 'Si tu tema web usa divs genéricos en el footer, edita el archivo footer.php de tu tema para envolver este contenido con la etiqueta semántica <footer>.'
            ],
            'has_main' => [
                'name' => 'Área de contenido principal (<main>)',
                'category' => 'html',
                'short_definition' => 'Busca que exista una etiqueta <main> para delimitar el núcleo del post.',
                'full_definition' => 'La etiqueta <main> encierra el contenido de valor único de la página. Excluye barras laterales, menús globales y pies de página. Debe ser único dentro del documento.',
                'source_name' => 'W3C HTML5 Specification',
                'source_url' => 'https://www.w3.org/TR/html52/grouping-content.html#the-main-element',
                'why_it_matters' => 'Los crawlers de inteligencia artificial (LLMs) y los lectores de pantalla leen con prioridad lo que hay dentro de la etiqueta <main>. Si no existe, tienen que adivinar dónde empieza el contenido real, lo cual puede diluir el score del post.',
                'example_good' => "<main id=\"main-content\">\n  <h1>Mi Post del Blog</h1>\n  <p>Contenido valioso...</p>\n</main>",
                'example_bad' => "<div class=\"wrapper\">\n  <div class=\"content-area\">\n    <p>Texto sin envoltorio semántico principal...</p>\n  </div>\n</div>",
                'recommendation' => 'Envuelve el contenedor central de tu plantilla de artículos (single.php) o páginas (page.php) con la etiqueta semántica <main>.'
            ],
            'semantic_tags' => [
                'name' => 'Etiquetado semántico secundario',
                'category' => 'html',
                'short_definition' => 'Detecta el uso de secciones semánticas adicionales como <section> y <article>.',
                'full_definition' => 'Las etiquetas <article> (contenido independiente) y <section> (agrupación temática) de HTML5 organizan el texto en componentes autónomos de información estructurada.',
                'source_name' => 'MDN Web Docs HTML Semantics',
                'source_url' => 'https://developer.mozilla.org/es/docs/Glossary/Semantics',
                'why_it_matters' => 'El uso sistemático de <section> e <article> rompe la "sopa de divs" e indica explícitamente a los buscadores y modelos RAG cómo dividir el documento para extraer fragmentos informativos óptimos.',
                'example_good' => "<article class=\"post-entry\">\n  <section class=\"intro\">Introducción...</section>\n  <section class=\"body\">Desarrollo...</section>\n</article>",
                'example_bad' => "<div class=\"post-entry\">\n  <div class=\"intro\">Introducción...</div>\n  <div class=\"body\">Desarrollo...</div>\n</div>",
                'recommendation' => 'Estructura las partes lógicas de tus entradas utilizando bloques semánticos y de contenido, evitando maquetar tu tema exclusivamente con divs.'
            ],
            'has_paragraphs' => [
                'name' => 'Párrafos estructurados (<p>)',
                'category' => 'html',
                'short_definition' => 'Asegura que el texto plano esté correctamente etiquetado en bloques <p>.',
                'full_definition' => 'El elemento <p> representa un párrafo de texto. El contenido de texto nunca debe servirse suelto en el DOM ni separado por múltiples etiquetas <br> consecutivas sin un contenedor lógico de párrafo.',
                'source_name' => 'W3C HTML Paragraphs',
                'source_url' => 'https://www.w3.org/TR/html52/grouping-content.html#the-p-element',
                'why_it_matters' => 'Los algoritmos de accesibilidad y los parseadores de lenguaje natural (NLP/LLM) dividen el texto por elementos de párrafo (<p>). Los saltos de línea sueltos impiden la correcta segmentación de la lectura.',
                'example_good' => "<p>Este es el primer párrafo de contenido.</p>\n<p>Este es un segundo bloque de texto separado.</p>",
                'example_bad' => "Este es el primer párrafo.<br><br>Este es el segundo bloque en el mismo div.",
                'recommendation' => 'Evita pulsar Shift+Enter repetidamente para simular párrafos en tu editor; usa bloques de párrafo reales que generen etiquetas <p> limpias.'
            ],
            'article_in_section' => [
                'name' => 'Agrupación semántica anidada',
                'category' => 'html',
                'short_definition' => 'Busca que los artículos posean divisiones internas o relaciones <article>/<section>.',
                'full_definition' => 'Establece una relación semántica lógica anidada donde las subsecciones internas del post o listados anidados se estructuran de forma complementaria utilizando tags específicos.',
                'source_name' => 'HTML5 Sectioning Specification',
                'source_url' => 'https://www.w3.org/TR/html52/sections.html#the-section-element',
                'why_it_matters' => 'Aporta legibilidad de jerarquía estructural fina a las IAs cuando procesan páginas muy densas de datos.',
                'example_good' => "<section class=\"blog-list\">\n  <article class=\"card\">Post 1</article>\n  <article class=\"card\">Post 2</article>\n</section>",
                'example_bad' => "<div class=\"blog-list\">\n  <div class=\"card\">Post 1</div>\n  <div class=\"card\">Post 2</div>\n</div>",
                'recommendation' => 'En tus listados de blog, usa <section> para englobar el feed de entradas y <article> para la tarjeta individual de cada post.'
            ],

            // ==========================================
            // 2. GEO / LLMS (llms)
            // ==========================================
            'llms_txt_present' => [
                'name' => 'Presencia de archivo llms.txt',
                'category' => 'llms',
                'short_definition' => 'Detecta si existe el archivo estándar llms.txt en el directorio raíz.',
                'full_definition' => 'El archivo llms.txt es una especificación emergente diseñada para proveer información estructurada y concisa en formato Markdown a los modelos de lenguaje (LLM). Ayuda a que las IAs entiendan rápidamente el propósito general de un sitio sin rastrear gigabytes de HTML.',
                'source_name' => 'llms-txt.org',
                'source_url' => 'https://llms-txt.org/',
                'why_it_matters' => 'Los buscadores de respuestas de inteligencia artificial (GEO) priorizan los sitios que les facilitan la información limpia. Disponer de un archivo llms.txt de calidad garantiza que las IAs te citen de forma precisa en sus chats conversacionales.',
                'example_good' => "# Título del Sitio\n\n> Breve descripción útil para prompts...\n\n## Secciones clave\n- [Inicio](https://ejemplo.com/)",
                'example_bad' => "[Archivo vacío o redirigido a una página 404 de WordPress]",
                'recommendation' => 'Usa el auto-fix de este plugin para generar una plantilla estructurada y guardarla para que se sirva en tu-dominio.com/llms.txt.'
            ],
            'llms_full_txt_present' => [
                'name' => 'Presencia de archivo llms-full.txt',
                'category' => 'llms',
                'short_definition' => 'Busca que exista el archivo de contenido extendido llms-full.txt.',
                'full_definition' => 'Complementario al llms.txt, el llms-full.txt ofrece una recopilación completa en texto limpio de todo el contenido de valor del sitio web. Sirve para procesos de entrenamiento de agentes de IA o para búsquedas contextuales en profundidad.',
                'source_name' => 'llms-txt.org Standard',
                'source_url' => 'https://llms-txt.org/',
                'why_it_matters' => 'Alimentar a los agentes de IA con el texto plano completo previene alucinaciones cuando responden sobre tu marca o servicios especializados.',
                'example_good' => "# Contenido Completo del Sitio\n\nEste archivo recopila toda la documentación...",
                'example_bad' => "[Sin archivo configurado o con error de redirección]",
                'recommendation' => 'Define y redacta la información complementaria de tu sitio en el cuadro de auto-fix para generar tu archivo llms-full.txt.'
            ],
            'robots_txt_present' => [
                'name' => 'Presencia del archivo robots.txt',
                'category' => 'llms',
                'short_definition' => 'Comprueba que exista un archivo robots.txt accesible.',
                'full_definition' => 'El robots.txt es un protocolo de exclusión voluntario que le dice a los motores de búsqueda y bots qué páginas del sitio tienen permitido rastrear y cuáles no.',
                'source_name' => 'Google Search Central - Robots.txt',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/robots/intro',
                'why_it_matters' => 'Sin un robots.txt adecuado, los motores de búsqueda pueden indexar carpetas del sistema (como wp-admin) o sobrecargar el servidor web con solicitudes de rastreo inútiles.',
                'example_good' => "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nSitemap: https://ejemplo.com/sitemap_index.xml",
                'example_bad' => "User-agent: *\nDisallow: /",
                'recommendation' => 'Asegúrate de que robots.txt exista en la raíz de tu dominio y contenga la ruta correcta al Sitemap de tu sitio.'
            ],
            'crawler_allowed_gptbot' => [
                'name' => 'Acceso permitido a GPTBot',
                'category' => 'llms',
                'short_definition' => 'Comprueba si la directiva robots.txt permite o bloquea al rastreador de OpenAI (ChatGPT).',
                'full_definition' => 'GPTBot es el rastreador oficial de OpenAI para recabar información de la web para entrenar y mejorar modelos como ChatGPT y responder consultas en tiempo real.',
                'source_name' => 'OpenAI Developer Documentation',
                'source_url' => 'https://platform.openai.com/docs/gptbot',
                'why_it_matters' => 'Si bloqueas a GPTBot en robots.txt, ChatGPT no conocerá tu negocio, marca o artículos y nunca aparecerás en sus respuestas o recomendaciones recomendadas.',
                'example_good' => "User-agent: GPTBot\nAllow: /",
                'example_bad' => "User-agent: GPTBot\nDisallow: /",
                'recommendation' => 'Revisa tu robots.txt y asegúrate de no tener una directiva Disallow para GPTBot.'
            ],
            'crawler_allowed_claudebot' => [
                'name' => 'Acceso permitido a ClaudeBot',
                'category' => 'llms',
                'short_definition' => 'Comprueba el acceso permitido al rastreador de Anthropic (Claude).',
                'full_definition' => 'ClaudeBot es el rastreador de Anthropic diseñado para recopilar información de la web para alimentar a la inteligencia artificial Claude y responder consultas de usuarios.',
                'source_name' => 'Anthropic Support Guidelines',
                'source_url' => 'https://support.anthropic.com/en/articles/888888-claudebot',
                'why_it_matters' => 'Permitir a ClaudeBot rastrear tu sitio web te posiciona como fuente de autoridad en la plataforma de IA de Claude, la cual posee una enorme masa crítica corporativa.',
                'example_good' => "User-agent: ClaudeBot\nAllow: /",
                'example_bad' => "User-agent: ClaudeBot\nDisallow: /",
                'recommendation' => 'Evita la directiva Disallow en robots.txt para ClaudeBot si deseas que los usuarios de Claude descubran tu contenido.'
            ],
            'crawler_allowed_google_extended' => [
                'name' => 'Acceso permitido a Google-Extended',
                'category' => 'llms',
                'short_definition' => 'Comprueba si se permite a Google usar tu contenido para alimentar Gemini.',
                'full_definition' => 'Google-Extended es el token que utilizan los creadores web para controlar si Google puede usar el contenido de sus sitios para entrenar modelos Gemini y APIs de IA generativa relacionadas.',
                'source_name' => 'Google Search Central - Google-Extended',
                'source_url' => 'https://developers.google.com/search/docs/crawling-indexing/google-extended',
                'why_it_matters' => 'Si se bloquea Google-Extended, impides que Gemini use tus datos para sus resúmenes rápidos interactivos (AI Overviews), lo que reduce drásticamente el tráfico informacional moderno.',
                'example_good' => "User-agent: Google-Extended\nAllow: /",
                'example_bad' => "User-agent: Google-Extended\nDisallow: /",
                'recommendation' => 'Permite el acceso a Google-Extended para que tu web siga participando en los resúmenes inteligentes de Google.'
            ],

            // ==========================================
            // 3. AEO / CONTENIDO (aeo)
            // ==========================================
            'aeo_question_headings' => [
                'name' => 'Headings en formato pregunta',
                'category' => 'aeo',
                'short_definition' => 'Detecta encabezados redactados como preguntas directas para resolver dudas de búsqueda.',
                'full_definition' => 'Los encabezados que inician o terminan en signo de interrogación estructuran el texto para responder consultas exactas. Reflejan fielmente el lenguaje natural con el que buscan las personas en Google y asistentes de voz.',
                'source_name' => 'Google Search Central',
                'source_url' => 'https://developers.google.com/search/docs/appearance/featured-snippets',
                'why_it_matters' => 'Esta técnica es el método más rápido y efectivo para capturar Featured Snippets (fragmentos destacados) de Google y aparecer en los resúmenes conversacionales de las IAs, las cuales buscan emparejar preguntas del usuario con respuestas explícitas.',
                'example_good' => "<h2>¿Qué es el SEO técnico y cómo optimizarlo?</h2>",
                'example_bad' => "<h2>Definición y Optimización del SEO de un sitio web en 2026</h2>",
                'recommendation' => 'Añade preguntas claras e interesantes al inicio de tus principales secciones (H2 y H3).'
            ],
            'aeo_faq_schema' => [
                'name' => 'Schema FAQPage o QAPage',
                'category' => 'aeo',
                'short_definition' => 'Detecta si hay datos estructurados JSON-LD que definan preguntas frecuentes.',
                'full_definition' => 'El Schema `FAQPage` indica formalmente a nivel de código que una página contiene una lista de preguntas frecuentes con sus respectivas respuestas concisas.',
                'source_name' => 'Schema.org FAQPage Type',
                'source_url' => 'https://schema.org/FAQPage',
                'why_it_matters' => 'Las páginas con Schema FAQPage son elegibles para mostrar resultados enriquecidos con acordeones directamente en las SERPs de Google, lo que incrementa sustancialmente el porcentaje de clics (CTR). También ayuda a los LLMs a descargar pares de datos perfectos.',
                'example_good' => "{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"FAQPage\",\n  \"mainEntity\": [{\n    \"@type\": \"Question\",\n    \"name\": \"¿...\",\n    \"acceptedAnswer\": {\n      \"@type\": \"Answer\",\n      \"text\": \"...\"\n    }\n  }]\n}",
                'example_bad' => "[Sin Schema estructurado FAQPage en el código de la página]",
                'recommendation' => 'Si tienes secciones de preguntas en tu post, implementa un bloque de acordeón o inyecta el JSON-LD de FAQPage mediante el auto-fix.'
            ],
            'aeo_qa_pairs' => [
                'name' => 'Pares de Pregunta y Respuesta en DOM',
                'category' => 'aeo',
                'short_definition' => 'Verifica que a una pregunta (heading) le siga inmediatamente su respuesta (párrafo).',
                'full_definition' => 'Audita que el DOM tenga una estructura directa: un encabezado de pregunta (H2 o H3) seguido de manera contigua por un párrafo descriptivo corto (<p>) que funcione como respuesta concisa y resolutiva.',
                'source_name' => 'Search Engine Journal AEO Studies',
                'source_url' => 'https://www.searchenginejournal.com/answer-engine-optimization/',
                'why_it_matters' => 'Los algoritmos de extracción rápida analizan el DOM buscando este patrón exacto para recortar párrafos e insertarlos como respuestas rápidas (featured snippets) sin necesidad de leer todo el post.',
                'example_good' => "<h2>¿Cuánto dura la batería?</h2>\n<p>La batería del dispositivo dura aproximadamente 12 horas con uso moderado.</p>",
                'example_bad' => "<h2>¿Cuánto dura la batería?</h2>\n<div class=\"publicidad\">...</div>\n<p>Bueno, es una pregunta compleja...</p>",
                'recommendation' => 'Coloca la respuesta al grano en el primer párrafo directamente debajo de la pregunta jerárquica.'
            ],
            'aeo_definitions' => [
                'name' => 'Oraciones definitorias directas',
                'category' => 'aeo',
                'short_definition' => 'Busca patrones lingüísticos explícitos que definan conceptos ("es un", "se refiere a").',
                'full_definition' => 'Analiza si el texto utiliza estructuras semánticas de definición inequívocas, tales como "X es...", "X se define como..." o "X consiste en...".',
                'source_name' => 'Google Knowledge Graph Search API',
                'source_url' => 'https://developers.google.com/knowledge-graph',
                'why_it_matters' => 'Los Knowledge Panels de Google y los resumidores de IA se basan en estas frases directas para definir términos a los usuarios de manera concisa.',
                'example_good' => "<p>La <strong>energía solar</strong> es un recurso renovable obtenido a partir de la radiación electromagnética procedente del Sol.</p>",
                'example_bad' => "<p>Cuando pensamos en el sol y cómo nos da luz, a veces también pensamos en formas de generar electricidad para nuestras casas.</p>",
                'recommendation' => 'Al introducir un tema clave, utiliza una frase corta que comience definiéndolo de forma explícita en su formato de diccionario.'
            ],
            'aeo_answer_lists' => [
                'name' => 'Listas de respuesta estructuradas',
                'category' => 'aeo',
                'short_definition' => 'Busca listas ordenadas u ordenadas (ol/ul) justo después de un encabezado.',
                'full_definition' => 'Detecta si hay listas de viñetas (<ul>) o listas numeradas (<ol>) colocadas directamente debajo de un encabezado temático.',
                'source_name' => 'Google Featured Snippets - List Types',
                'source_url' => 'https://developers.google.com/search/docs/appearance/featured-snippets#snippet-types',
                'why_it_matters' => 'Para búsquedas de tipo tutorial o listas de elementos, Google extrae estas viñetas para construir fragmentos destacados de lista ordenada. Son ideales para capturar tráfico informacional altamente cualificado.',
                'example_good' => "<h2>Pasos para purgar un radiador</h2>\n<ol>\n  <li>Apaga la calefacción.</li>\n  <li>Usa la llave en la válvula.</li>\n</ol>",
                'example_bad' => "<h2>Cómo purgar un radiador</h2>\nPrimero debes asegurarte de apagar el sistema. Luego tienes que buscar la llave de purgado e insertarla...",
                'recommendation' => 'Usa listas y viñetas para enumerar pasos, ingredientes, ventajas o herramientas en lugar de escribir párrafos largos y apelmazados.'
            ],
            'aeo_snippet_length' => [
                'name' => 'Longitud ideal del fragmento (TL;DR)',
                'category' => 'aeo',
                'short_definition' => 'Busca párrafos resumen con una longitud óptima de entre 40 y 60 palabras.',
                'full_definition' => 'Mide si el documento posee al menos un párrafo conciso con una longitud perfecta de entre 40 y 60 palabras totales, que condense la respuesta principal a una pregunta.',
                'source_name' => 'SEMrush Featured Snippets Research',
                'source_url' => 'https://www.semrush.com/blog/featured-snippets/',
                'why_it_matters' => 'El rango de 40-60 palabras es estadísticamente el tamaño preferido por los algoritmos de Google para recortar y mostrar como fragmentos destacados en su buscador de escritorio e internacional.',
                'example_good' => "<p>El SEO es la práctica de optimizar un sitio web para mejorar su visibilidad y posicionamiento en los motores de búsqueda. Esto se logra mediante optimizaciones técnicas, contenido de calidad, análisis de palabras clave y la obtención de enlaces externos autoritarios.</p>",
                'example_bad' => "<p>El SEO es vital y te ayuda mucho. Debes hacerlo hoy mismo para ganar dinero.</p>",
                'recommendation' => 'Incluye un resumen ejecutivo "TL;DR" de unas 50 palabras al principio de tu artículo y enmárcalo de forma destacada.'
            ],
            'aeo_how_patterns' => [
                'name' => 'Patrones instructivos en headings',
                'category' => 'aeo',
                'short_definition' => 'Detecta palabras clave instructivas en encabezados ("Cómo", "Guía", "Pasos").',
                'full_definition' => 'Audita que el post posea encabezados orientados a la instrucción práctica, utilizando palabras clave disparadoras como "Cómo hacer X", "Guía para X" o "Pasos para solucionar Y".',
                'source_name' => 'Google Search Central - How-to Structured Data',
                'source_url' => 'https://developers.google.com/search/docs/appearance/structured-data/how-to',
                'why_it_matters' => 'Las intenciones de búsqueda informacionales son las más buscadas. Estos patrones aumentan la tasa de aparición en sistemas de respuesta de IA y búsquedas directas de tutoriales.',
                'example_good' => "<h2>Guía paso a paso para cambiar un neumático</h2>",
                'example_bad' => "<h2>Consideraciones sobre neumáticos en el coche</h2>",
                'recommendation' => 'Usa títulos atractivos e instructivos que inviten al usuario a aprender a resolver una tarea paso a paso.'
            ],

            // ==========================================
            // 4. CORE WEB VITALS (cwv)
            // ==========================================
            'cwv_lcp' => [
                'name' => 'Largest Contentful Paint (LCP)',
                'category' => 'cwv',
                'short_definition' => 'Mide la velocidad de carga percibida del elemento principal (ideal: <2.5s).',
                'full_definition' => 'El LCP mide el tiempo que tarda en renderizarse el elemento de texto o imagen más grande visible en la pantalla del usuario. Marca el punto donde el contenido principal de la página ya se ha cargado en gran parte.',
                'source_name' => 'web.dev LCP',
                'source_url' => 'https://web.dev/lcp/',
                'why_it_matters' => 'Un LCP lento frustra al usuario y aumenta la tasa de rebote instantáneo. Google lo considera un factor de ranking crítico dentro de la Experiencia de Página.',
                'example_good' => "[El banner de la cabecera carga de forma optimizada en 1.8 segundos]",
                'example_bad' => "[Imagen principal gigante sin comprimir que tarda 4.5 segundos en aparecer]",
                'recommendation' => 'Comprime tus imágenes principales, implementa WebP/Avif y precarga la imagen LCP mediante la inyección del auto-fix.'
            ],
            'cwv_cls' => [
                'name' => 'Cumulative Layout Shift (CLS)',
                'category' => 'cwv',
                'short_definition' => 'Mide la estabilidad visual de la página durante la carga (ideal: <0.1).',
                'full_definition' => 'El CLS mide la cantidad de cambios inesperados de diseño que ocurren en la página mientras se está cargando. Ocurre cuando los elementos visibles cambian de posición debido a imágenes sin dimensiones o fuentes lentas.',
                'source_name' => 'web.dev CLS',
                'source_url' => 'https://web.dev/cls/',
                'why_it_matters' => 'Un CLS alto hace que el usuario pulse botones equivocados por accidente o pierda el hilo de la lectura, degradando de forma severa la usabilidad móvil.',
                'example_good' => "[La página carga sin que el texto o los botones se desplacen ni un píxel]",
                'example_bad' => "[Una publicidad sin dimensiones explícitas carga tarde y desplaza todo el texto hacia abajo]",
                'recommendation' => 'Asigna dimensiones de alto y ancho (width y height) explícitas a tus imágenes y reserva espacios con CSS para banners publicitarios.'
            ],

            // ==========================================
            // 5. SCHEMA MARKUP (schema)
            // ==========================================
            'schema_present' => [
                'name' => 'Presencia de Schema.org',
                'category' => 'schema',
                'short_definition' => 'Verifica la existencia de algún bloque de datos estructurados en la página.',
                'full_definition' => 'El marcado de Schema.org provee un vocabulario estructurado para codificar el contenido de tus páginas web en un formato que las máquinas entiendan perfectamente. Se suele inyectar usando código JSON-LD dentro de etiquetas `<script>`.',
                'source_name' => 'Schema.org',
                'source_url' => 'https://schema.org',
                'why_it_matters' => 'Sin datos estructurados, los buscadores tienen que inferir el tipo de contenido. Schema ayuda a clasificar de forma inequívoca si la página es una Receta, un Producto en venta, una Organización, o un Artículo de prensa.',
                'example_good' => "<script type=\"application/ld+json\">\n{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Article\",\n  \"headline\": \"Título\"\n}\n</script>",
                'example_bad' => "[No existe ningún bloque de marcado JSON-LD ni microdatos en la página]",
                'recommendation' => 'Usa el auto-fix para generar e inyectar el bloque Schema estructurado correspondiente al contenido del post.'
            ],
            'schema_json_ld_valid' => [
                'name' => 'Validez sintáctica del JSON-LD',
                'category' => 'schema',
                'short_definition' => 'Garantiza que el código JSON-LD sea sintácticamente correcto y libre de errores.',
                'full_definition' => 'Valida que el contenido del script de datos estructurados sea un objeto JSON bien formado, respetando comillas dobles, comas de separación correctas y llaves perfectamente cerradas.',
                'source_name' => 'Google Rich Results Test',
                'source_url' => 'https://search.google.com/test/rich-results',
                'why_it_matters' => 'Un código JSON con un solo error de sintaxis (como una coma sobrante al final o una comilla rota) queda totalmente inutilizado, lo cual invalida todo el Schema e impide calificar para rich results.',
                'example_good' => "{\n  \"@type\": \"WebPage\",\n  \"name\": \"Inicio\"\n}",
                'example_bad' => "{\n  \"@type\": \"WebPage\",\n  \"name\": \"Inicio\",  <-- Coma ilegal al final\n}",
                'recommendation' => 'Comprueba la sintaxis de tus datos estructurados y corrige cualquier fallo tipográfico utilizando el inyector del plugin.'
            ],

            // ==========================================
            // 6. METATAGS (metatags)
            // ==========================================
            'meta_title' => [
                'name' => 'Etiqueta Title del documento',
                'category' => 'metatags',
                'short_definition' => 'Mide la longitud y presencia del título del navegador (ideal: 30-60 caracteres).',
                'full_definition' => 'La etiqueta `<title>` define el texto que se muestra en la pestaña del navegador y es el título principal sobre el cual los usuarios hacen clic en los resultados de búsqueda de Google.',
                'source_name' => 'Google Search Central - Title Link',
                'source_url' => 'https://developers.google.com/search/docs/appearance/title-link',
                'why_it_matters' => 'Es el factor SEO On-Page más importante para incitar al clic. Si es muy corto, pierdes espacio informativo; si supera los 60 caracteres, Google lo recortará con puntos suspensivos ("...").',
                'example_good' => "<title>Cómo optimizar el SEO de tu web en 5 pasos sencillos</title>",
                'example_bad' => "<title>Inicio</title>  (Muy corto)\n<title>Esta es la página de inicio donde vendemos muchísimos servicios de marketing digital para tu empresa y tus amigos...</title> (Muy largo)",
                'recommendation' => 'Escribe títulos atractivos, descriptivos y de entre 45 y 55 caracteres, situando la palabra clave al principio.'
            ],
            'meta_description' => [
                'name' => 'Meta Descripción (<meta name="description">)',
                'category' => 'metatags',
                'short_definition' => 'Asegura que el fragmento descriptivo tenga la longitud correcta (70-155 caracteres).',
                'full_definition' => 'La meta descripción es una etiqueta HTML en la cabecera que resume el contenido de la página. Google la muestra habitualmente debajo del enlace de título en la página de resultados.',
                'source_name' => 'Google Search Central - Snippets Control',
                'source_url' => 'https://developers.google.com/search/docs/appearance/snippet',
                'why_it_matters' => 'Aunque no influye de forma directa en el ranking de posicionamiento, una meta descripción persuasiva dispara el porcentaje de clics (CTR). También ayuda a que los resúmenes automáticos de IA entiendan rápido el tema.',
                'example_good' => "<meta name=\"description\" content=\"Descubre cómo auditar el SEO técnico de tu sitio web fácilmente con nuestra guía detallada de 5 pasos prácticos.\">",
                'example_bad' => "<meta name=\"description\" content=\"Hacemos webs.\">",
                'recommendation' => 'Redacta un resumen muy vendedor de unas 130 palabras, incluyendo un llamado a la acción persuasivo al final.'
            ],

            // ==========================================
            // 7. ENLACES E IMÁGENES (links)
            // ==========================================
            'links_broken_check' => [
                'name' => 'Salud de enlaces externos (sin roturas 404)',
                'category' => 'links',
                'short_definition' => 'Busca enlaces caídos o rotos que apunten a páginas inexistentes.',
                'full_definition' => 'Analiza todos los enlaces salientes para verificar que respondan con un código HTTP de estado exitoso (200 OK) y no con un error de página no encontrada (404 Not Found).',
                'source_name' => 'W3C Link Checker Guidelines',
                'source_url' => 'https://validator.w3.org/docs/checklink.html',
                'why_it_matters' => 'Los enlaces rotos arruinan la experiencia del usuario y envían una señal de dejadez técnica a los motores de búsqueda, reduciendo la autoridad de enlace transferida (Link Juice).',
                'example_good' => "<a href=\"https://google.com\">Ir a Google</a>",
                'example_bad' => "<a href=\"https://ejemplo.com/pagina-borrada-hace-años\">Leer más</a>",
                'recommendation' => 'Edita el contenido del artículo para eliminar el enlace roto o redirigirlo a una fuente de información activa y verídica.'
            ],
            'images_missing_alt' => [
                'name' => 'Atributo alternativo faltante en imágenes (alt)',
                'category' => 'links',
                'short_definition' => 'Identifica imágenes que carecen por completo de la propiedad alt descriptiva.',
                'full_definition' => 'El atributo `alt` proporciona una descripción en formato texto del contenido de una imagen. Es interpretado por los lectores de pantalla de personas con discapacidad visual y por los bots indexadores.',
                'source_name' => 'W3C Web Accessibility (alt property)',
                'source_url' => 'https://www.w3.org/WAI/tutorials/images/decision-tree/',
                'why_it_matters' => 'Una imagen sin alt es invisible para Google Images y degrada severamente la accesibilidad del sitio (fallo WCAG). Las IAs multimediales usan esta descripción para indexar tu contenido gráfico.',
                'example_good' => "<img src=\"perro.jpg\" alt=\"Un perro Golden Retriever corriendo en el parque por el césped\">",
                'example_bad' => "<img src=\"perro.jpg\">",
                'recommendation' => 'Usa el auto-fix para rellenar dinámicamente los campos alt de tus imágenes utilizando el título del artículo como fallback automático.'
            ],

            // ==========================================
            // 8. LEGIBILIDAD (readability)
            // ==========================================
            'readability_flesch' => [
                'name' => 'Índice de Legibilidad Flesch-Szigriszt / Flesch',
                'category' => 'readability',
                'short_definition' => 'Evalúa la facilidad de lectura y complejidad sintáctica del texto.',
                'full_definition' => 'El índice de Flesch mide la complejidad lingüística en base a la longitud media de las oraciones y el número de sílabas por palabra. Un puntaje alto (>60) indica un texto fluido y fácil de comprender para una gran audiencia.',
                'source_name' => 'Flesch Reading Ease Standard',
                'source_url' => 'https://www.plainlanguage.gov/guidelines/words/use-simple-words/',
                'why_it_matters' => 'Un texto excesivamente denso y académico cansa al lector moderno, aumentando el abandono inmediato de la página. Asimismo, las IAs conversacionales prefieren citar respuestas redactadas con claridad cristalina.',
                'example_good' => "<p>Optimizar el SEO de tu web es sencillo si sigues una lista de pasos ordenados. Primero, comprime tus imágenes para mejorar la carga.</p> (Fluido y directo)",
                'example_bad' => "<p>La optimización metodológica de la indexabilidad algorítmica presupone la previa sustanciación de la compresión volumétrica de los archivos de píxeles.</p> (Innecesariamente complejo)",
                'recommendation' => 'Simplifica tu lenguaje. Escribe oraciones cortas, evita los tecnicismos innecesarios y usa palabras directas del día a día.'
            ],
            'readability_long_paragraphs' => [
                'name' => 'Párrafos excesivamente largos ("Muros de texto")',
                'category' => 'readability',
                'short_definition' => 'Detecta bloques de texto enormes sin saltos de línea lógicos.',
                'full_definition' => 'Identifica párrafos que contienen demasiadas palabras u oraciones complejas seguidas sin un punto y aparte que permita un respiro visual al lector.',
                'source_name' => 'Plain Language Guidelines - Paragraphs',
                'source_url' => 'https://www.plainlanguage.gov/guidelines/design/keep-paragraphs-short/',
                'why_it_matters' => 'En pantallas móviles de teléfonos móviles, un párrafo de más de 6 líneas se convierte en un "muro de texto" impenetrable que provoca fatiga visual y rebote inmediato del usuario.',
                'example_good' => "<p>Escribir párrafos cortos atrae la atención. Ayuda al usuario a escanear tu web de un vistazo.</p>\n<p>Divide tus ideas en bloques separados de máximo 3 oraciones.</p>",
                'example_bad' => "<p>Escribir en la web requiere saber cómo se comportan las personas porque hoy en día casi nadie lee palabra por palabra sino que escanean de forma rápida y buscan lo importante por lo cual si pones párrafos eternos llenos de comas y sin puntos de descanso la persona simplemente se irá a otra página que le dé la respuesta de forma inmediata y masticada...</p>",
                'recommendation' => 'Limita tus párrafos a un máximo de 3 o 4 líneas de longitud antes de introducir un salto de párrafo lógico.'
            ],

            // ==========================================
            // 9. EEAT / KEYWORD (keyword)
            // ==========================================
            'kw_density' => [
                'name' => 'Densidad ideal de Palabra Clave',
                'category' => 'keyword',
                'short_definition' => 'Mide el porcentaje de aparición de la palabra clave en el contenido (ideal: 0.5% - 2.5%).',
                'full_definition' => 'Calcula cuántas veces aparece la frase de palabra clave objetivo en relación al total de palabras del post.',
                'source_name' => 'Google Search Central - Keyword Stuffing avoidance',
                'source_url' => 'https://developers.google.com/search/docs/essentials/spam-policies#keyword-stuffing',
                'why_it_matters' => 'Si la palabra clave aparece muy poco, Google no sabrá que el post se especializa en ella. Si aparece demasiado (>3%), se incurre en "Keyword Stuffing", una práctica penalizada por los algoritmos antispam.',
                'example_good' => "[Uso natural del término \"SEO para WordPress\" unas 8 veces en un artículo de 1000 palabras (0.8%)]",
                'example_bad' => "\"Somos el mejor SEO para WordPress porque nuestro SEO para WordPress optimiza tu SEO para WordPress y hace que tu SEO para WordPress sea el mejor de la red.\"",
                'recommendation' => 'Escribe de forma natural para humanos. Usa sinónimos y variaciones semánticas de tu palabra clave en lugar de repetirla idéntica hasta el cansancio.'
            ],
            'kw_position_title' => [
                'name' => 'Palabra clave en el Title Tag',
                'category' => 'keyword',
                'short_definition' => 'Verifica que la palabra clave objetivo aparezca dentro de la etiqueta `<title>`.',
                'full_definition' => 'Comprueba que la frase de búsqueda exacta introducida esté presente en el Title Tag, preferiblemente en los primeros caracteres.',
                'source_name' => 'Search Engine Land Title Guidelines',
                'source_url' => 'https://searchengineland.com/seo-best-practices-titles-descriptions/',
                'why_it_matters' => 'Tener la palabra clave al inicio del título es uno de los factores de relevancia más determinantes para indexar y posicionar para dicha palabra clave exacta.',
                'example_good' => "<title>SEO para WordPress: Guía de Optimización Definitiva</title>",
                'example_bad' => "<title>Guía Completa para tu Blog y Consejos Web</title> (Falta la palabra clave objetivo)",
                'recommendation' => 'Edita el Title Tag y asegúrate de incluir tu frase clave de manera natural lo más a la izquierda posible.'
            ]
        ];

        // Ensure we support the full list by auto-filling default representations for missing ones
        // or letting developers extend/filter the array using a WordPress filter.
        self::$terms = apply_filters( 'seosi_bookman_terms', $raw_terms );

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
                'why_it_matters' => 'Los buscadores generativos de IA necesitan leer el contenido de tu web para citarte. Si los bloqueas en el archivo robots.txt, pierdes toda presencia en sus chats.',
                'example_good' => "User-agent: *\nAllow: /",
                'example_bad' => sprintf( "User-agent: %s\nDisallow: /", $bot ),
                'recommendation' => 'Asegúrate de no bloquear este bot en las directivas de tu archivo robots.txt.'
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
                'why_it_matters' => 'Tener esquemas completos incrementa notablemente la elegibilidad para obtener fragmentos enriquecidos (Rich Results) en Google y previene advertencias críticas en Search Console.',
                'example_good' => sprintf( "\"%s\": \"Valor válido descriptivo\"", $field ),
                'example_bad' => sprintf( "[La propiedad \"%s\" está completamente omitida en el objeto JSON-LD]", $field ),
                'recommendation' => 'Añade esta propiedad con información verídica a tus datos estructurados JSON-LD.'
            ];
        }

        return null;
    }
}
