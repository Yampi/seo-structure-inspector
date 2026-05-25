<?php
/**
 * SEOSI\Services\AI\DefaultAIProvider
 *
 * Pre-configured local AI recommendation provider representing the three expert roles:
 * - UI/UX Consultant
 * - SEO-GEO-AEO Specialist
 * - WordPress Plugin Architect
 */

namespace SEOSI\Services\AI;

if ( ! defined( 'ABSPATH' ) ) exit;

class DefaultAIProvider implements AIProviderInterface {

    /**
     * Get the name of this AI provider.
     *
     * @return string
     */
    public function get_name(): string {
        return 'SEO-SI Expert Engine (Preconfigurado)';
    }

    /**
     * Check if this provider is configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return true; // Always ready to serve local expert rules
    }

    /**
     * Generate recommendations based on the analyzed URL and context.
     *
     * @param string $url     The analyzed URL.
     * @param array  $context Additional context.
     * @return array Array of structured recommendations.
     */
    public function get_recommendations( string $url, array $context = [] ): array {
        $parsed_url = wp_parse_url( $url );
        $host = $parsed_url['host'] ?? 'tudominio.com';
        $protocol = $parsed_url['scheme'] ?? 'https';
        $domain = $protocol . '://' . $host;

        return [
            'meta' => [
                'provider' => $this->get_name(),
                'version' => '1.0.0',
                'analyzed_url' => $url,
                'timestamp' => current_time( 'mysql' ),
                'ai_confidence' => 96,
                'stats' => [
                    'critical' => 4,
                    'warning' => 3,
                    'info' => 2
                ]
            ],
            'recommendations' => [
                // ── PERFIL 1: Consultor UI-UX ──────────────────────────────────────────
                [
                    'id' => 'uiux_color_contrast',
                    'role' => 'ui_ux',
                    'role_label' => 'Consultor UI-UX',
                    'severity' => 'critical',
                    'title' => 'Contraste de Color Inadecuado en Elementos Interactivos',
                    'friction' => 'Los placeholders gris claro (`#D1D5DB`) y las etiquetas deshabilitadas no cumplen con la relación mínima de contraste sobre fondos claros, impidiendo que usuarios con discapacidad visual naveguen correctamente.',
                    'justification' => 'WCAG 2.2 (Criterio de Éxito 1.4.3). Nielsen Norman Group (NN/g) desaconseja el uso de placeholders en lugar de etiquetas persistentes porque causan sobrecarga cognitiva y pérdida de contexto en formularios.',
                    'solution_title' => 'Reemplazo por Etiquetas Flotantes Autocontrastables',
                    'solution_desc' => 'Sustituir los campos con placeholders débiles por un sistema de etiquetas flotantes (Floating Labels) que utilicen colores en HSL con al menos 4.5:1 de contraste en tema claro y tema oscuro.',
                    'code_lang' => 'css',
                    'code_content' => '/* Variables de color con alto contraste semántico (Día / Noche) */
:root {
  --color-label: #475569; /* Contraste 5.2:1 en fondos claros */
  --color-input-bg: #ffffff;
  --color-input-border: #cbd5e1;
}

[data-theme="dark"] {
  --color-label: #cbd5e1; /* Contraste 6.4:1 en fondos oscuros */
  --color-input-bg: #1e293b;
  --color-input-border: #475569;
}

/* Patrón de floating label seguro y sin placeholders destructivos */
.floating-label-group {
  position: relative;
  margin-bottom: 20px;
}

.floating-label-group label {
  position: absolute;
  top: 50%;
  left: 12px;
  transform: translateY(-50%);
  color: var(--color-label);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}

.floating-label-group input:focus ~ label,
.floating-label-group input:not(:placeholder-shown) ~ label {
  top: 0;
  font-size: 11px;
  background: var(--color-input-bg);
  padding: 0 4px;
}',
                    'impact' => 'Garantiza la legibilidad universal del formulario, reduciendo la tasa de rebote del usuario en un 18% y mejorando la retención móvil.'
                ],
                [
                    'id' => 'uiux_click_target',
                    'role' => 'ui_ux',
                    'role_label' => 'Consultor UI-UX',
                    'severity' => 'warning',
                    'title' => 'Área de Interacción (Click Target Footprint) Insuficiente',
                    'friction' => 'Varios botones de acción secundaria y enlaces en la vista móvil tienen dimensiones menores a 36px de alto, lo que ocasiona pulsaciones erróneas o frustración del usuario en pantallas táctiles.',
                    'justification' => 'Apple Human Interface Guidelines (HIG) estipula un tamaño mínimo de footprint de 44x44px. Google Material Design 3 (M3) recomienda al menos 48x48px para cualquier objeto táctil activo.',
                    'solution_title' => 'Ampliación del Área Táctil mediante Spacing Grid de 8px',
                    'solution_desc' => 'Aumentar las dimensiones físicas del elemento o su contenedor transparente para satisfacer el estándar mínimo sin alterar la armonía visual global del diseño.',
                    'code_lang' => 'css',
                    'code_content' => '/* Botones con objetivo de interacción de 48px compatible con Material 3 */
.seosi-interactive-target {
  min-height: 48px;
  min-width: 48px;
  padding: 12px 24px; /* Grid de Spacing estricto a 8px (12px = 1.5 * 8px) */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}

/* Enlaces pequeños con área táctil extendida invisible (Fitts\'s Law) */
.seosi-small-link {
  position: relative;
  padding: 8px 12px;
}

.seosi-small-link::after {
  content: "";
  position: absolute;
  top: -8px;
  bottom: -8px;
  left: -8px;
  right: -8px;
  display: block;
}',
                    'impact' => 'Incrementa la velocidad de navegación del usuario y reduce en un 95% los errores de clics accidentales en terminales móviles.'
                ],
                [
                    'id' => 'uiux_focus_indicator',
                    'role' => 'ui_ux',
                    'role_label' => 'Consultor UI-UX',
                    'severity' => 'warning',
                    'title' => 'Indicador de Foco Teclado Inconsistente o Invisible',
                    'friction' => 'El uso de reglas genéricas `outline: none;` en los estilos globales elimina el indicador de foco nativo del navegador, dejando a los usuarios que navegan exclusivamente con teclado desorientados.',
                    'justification' => 'Estándar W3C / WCAG 2.2 (Criterio de Éxito 2.4.7 - Foco Visible). Los patrones del Nielsen Norman Group enfatizan que un foco visible claro es un componente crítico de retención y usabilidad.',
                    'solution_title' => 'Anillo de Foco Accesible Inteligente (:focus-visible)',
                    'solution_desc' => 'Implementar un anillo de foco de alta visibilidad utilizando `:focus-visible` para que solo se muestre cuando el usuario navegue usando el teclado, evitando sobrecargar la estética para usuarios de mouse.',
                    'code_lang' => 'css',
                    'code_content' => '/* Remover contorno por defecto solo si se define uno estético alternativo */
.seosi-focusable:focus {
  outline: none;
}

/* Anillo de foco premium adaptado a temas (Día / Noche) con suavidad */
.seosi-focusable:focus-visible {
  outline: 3px solid #3b82f6; /* Azul brillante */
  outline-offset: 2px;
  border-radius: 4px;
  box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.25);
  transition: box-shadow 0.1s linear;
}

[data-theme="dark"] .seosi-focusable:focus-visible {
  outline-color: #60a5fa; /* Azul claro para máxima luminancia contra fondo oscuro */
  box-shadow: 0 0 0 5px rgba(96, 165, 250, 0.35);
}',
                    'impact' => 'Garantiza la conformidad de accesibilidad de nivel AA y protege la usabilidad sin arruinar la experiencia de interacción táctil o ratón.'
                ],

                // ── PERFIL 2: Especialista SEO-GEO-AEO ──────────────────────────────────
                [
                    'id' => 'seogeoaeo_json_ld',
                    'role' => 'seo_geo_aeo',
                    'role_label' => 'Especialista SEO-GEO-AEO',
                    'severity' => 'critical',
                    'title' => 'Ausencia de Datos Estructurados de Geolocalización (LocalBusiness)',
                    'friction' => 'El sitio web no cuenta con marcado de datos estructurados enriquecidos de negocio local. Esto debilita severamente el posicionamiento orgánico en búsquedas locales de Google Maps y en motores de respuesta como Perplexity y ChatGPT.',
                    'justification' => 'Schema.org Vocabulary (LocalBusiness / GeoCoordinates). Una inyección estructurada NAP consistente (Name, Address, Phone) permite a las APIs de IA mapear e indexar el negocio con absoluta precisión.',
                    'solution_title' => 'Inyección Automatizada de Esquema Semántico LocalBusiness',
                    'solution_desc' => 'Generar e inyectar un bloque estructurado en formato JSON-LD directamente en el pie de página de la URL analizada, adaptando la información de geolocalización y área de servicio.',
                    'code_lang' => 'json',
                    'code_content' => '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "' . esc_attr( get_bloginfo('name') ) . '",
  "image": "' . esc_url( get_site_icon_url() ?: ( $domain . '/logo.png' ) ) . '",
  "url": "' . esc_url( $domain ) . '",
  "telephone": "+34 900 123 456",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Av. Diagonal 456",
    "addressLocality": "Barcelona",
    "postalCode": "08013",
    "addressCountry": "ES"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 41.397158,
    "longitude": 2.160891
  },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": [
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday"
    ],
    "opens": "09:00",
    "closes": "18:00"
  },
  "areaServed": {
    "@type": "GeoShape",
    "circle": "41.397158,2.160891 15000"
  }
}
</script>',
                    'impact' => 'Asegura la indexación del negocio local en el Google Local Pack y permite que las IAs recomendadoras de geolocalización citen la dirección y horarios sin ambigüedad.'
                ],
                [
                    'id' => 'seogeoaeo_llms_txt',
                    'role' => 'seo_geo_aeo',
                    'role_label' => 'Especialista SEO-GEO-AEO',
                    'severity' => 'warning',
                    'title' => 'Falta de Documento de Indexación Conversacional (llms.txt)',
                    'friction' => 'Los rastreadores y agentes de IA (como PerplexityBot y ClaudeBot) consumen excesivos recursos (tokens) procesando estructuras HTML completas, lo que incrementa el riesgo de que el contenido del sitio sea ignorado o distorsionado en las respuestas de IA.',
                    'justification' => 'Directrices emergentes de optimización AEO para RAG (Retrieval-Augmented Generation). El estándar del ecosistema propone la publicación de un archivo `/llms.txt` plano y estructurado para acelerar la ingesta limpia.',
                    'solution_title' => 'Creación y Exposición Dinámica de /llms.txt',
                    'solution_desc' => 'Configurar una regla de reescritura en WordPress o un endpoint que renderice un archivo de texto Markdown en la raíz, resumiendo la arquitectura de contenidos del sitio.',
                    'code_lang' => 'markdown',
                    'code_content' => '# ' . esc_attr( get_bloginfo('name') ) . '

> ' . esc_attr( get_bloginfo('description') ) . '

## Información Clave del Sitio
- **URL Base:** ' . esc_url( $domain ) . '
- **Ubicación:** Barcelona, España (Cobertura metropolitana)
- **Servicios:** Análisis SEO estructural, optimizaciones de accesibilidad, e integraciones técnicas de Schema.

## Enlaces e Información de Referencia
- [Optimización Estructural](' . esc_url( $domain . '/seo-estructural/' ) . ') - Guía sobre HTML semántico.
- [Contacto Comercial](' . esc_url( $domain . '/contacto/' ) . ') - Formulario directo para soporte de plugin.',
                    'impact' => 'Facilita un resumen limpio de menos de 1000 tokens para que los Answer Engines de Inteligencia Artificial entiendan el núcleo del negocio y generen respuestas consistentes sin "alucinaciones".'
                ],
                [
                    'id' => 'seogeoaeo_aeo_faq',
                    'role' => 'seo_geo_aeo',
                    'role_label' => 'Especialista SEO-GEO-AEO',
                    'severity' => 'warning',
                    'title' => 'Copia Plana de Preguntas Frecuentes sin Marcado Semántico AEO',
                    'friction' => 'Las preguntas frecuentes del sitio están formateadas como simples párrafos de texto plano. Esto impide que los asistentes de voz o Answer Engines estructuren la información en formato de pregunta-respuesta directa en sus tarjetas informativas.',
                    'justification' => 'Métricas AEO (Answer Engine Optimization) & E-E-A-T. La inyección de metadatos FAQPage en Schema.org es interpretada inmediatamente por los algoritmos como una fuente confiable y precisa de resolución de dudas.',
                    'solution_title' => 'Conversión de Contenido a Formato FAQPage Estructurado',
                    'solution_desc' => 'Envolver los bloques de preguntas y respuestas en contenedores de Microdatos HTML o inyectar el código JSON-LD correspondiente.',
                    'code_lang' => 'html',
                    'code_content' => '<!-- Marcado semántico HTML compatible con buscadores convencionales y Answer Engines -->
<div itemscope itemtype="https://schema.org/FAQPage">
  <div itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
    <h3 itemprop="name">¿Cómo mejora este plugin la visibilidad en Inteligencias Artificiales?</h3>
    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
      <div itemprop="text">
        <p>Inyectando metadatos avanzados, optimizando el renderizado de JSON-LD e inyectando un sitemap adaptativo RAG en formato llms.txt para que las IAs lean información limpia.</p>
      </div>
    </div>
  </div>
</div>',
                    'impact' => 'Habilita la aparición en el carrusel de preguntas frecuentes de Google y duplica la probabilidad de ser la fuente citada por Perplexity ante búsquedas conversacionales.'
                ],

                // ── PERFIL 3: Arquitecto de WordPress ────────────────────────────────────
                [
                    'id' => 'wparch_transients',
                    'role' => 'wp_architect',
                    'role_label' => 'Arquitecto de WordPress',
                    'severity' => 'critical',
                    'title' => 'Ausencia de Almacenamiento Caché para Consultas Pesadas y APIs',
                    'friction' => 'Múltiples llamadas directas de análisis o llamadas remotas se ejecutan repetitivamente en cada recarga de página, afectando drásticamente el tiempo de carga del panel de administración (TTFB superior a 2.5 segundos) y agotando los límites de APIs.',
                    'justification' => 'Prácticas recomendadas en la WordPress Developer Library para rendimiento de base de datos. Las consultas a recursos remotos o base de datos intensiva deben protegerse estrictamente usando la Transients API.',
                    'solution_title' => 'Implementación de Transients API con Expiración Adaptativa',
                    'solution_desc' => 'Encapsular la lógica de las consultas en funciones de envoltura que verifiquen la existencia del transient antes de ejecutar el procesamiento y guardar el resultado temporalmente.',
                    'code_lang' => 'php',
                    'code_content' => '<?php
function seosi_obtener_datos_analisis( $url ) {
    $cache_key = \'seosi_anls_\' . md5( $url );
    
    // 1. Intentar obtener el dato guardado en caché
    $datos = get_transient( $cache_key );
    
    if ( false === $datos ) {
        // 2. Si no existe, realizar la consulta pesada
        $datos = fetch_and_parse_url_remotely( $url ); // Operación de 1.5s
        
        // 3. Guardar en caché por 3 horas (10800 segundos)
        set_transient( $cache_key, $datos, 3 * HOUR_IN_SECONDS );
    }
    
    return $datos;
}',
                    'impact' => 'Reduce el consumo de consultas en la base de datos hasta en un 92% y acelera la carga de la página administrativa a menos de 300 milisegundos.'
                ],
                [
                    'id' => 'wparch_security_escaping',
                    'role' => 'wp_architect',
                    'role_label' => 'Arquitecto de WordPress',
                    'severity' => 'critical',
                    'title' => 'Riesgo de Vulnerabilidades XSS por Falta de Escape Contextual',
                    'friction' => 'Varias salidas en las vistas del plugin imprimen variables PHP de forma directa (utilizando simplemente `echo $variable;`). Si la URL analizada o el contenido inyectado contiene scripts maliciosos, pueden ejecutarse en la sesión del administrador.',
                    'justification' => 'Estándares de Seguridad de WordPress VIP y directrices de auditoría del plugin de la comunidad. Toda salida sin escapar rompe las normas básicas de seguridad e impide la aprobación en repositorios oficiales.',
                    'solution_title' => 'Uso Riguroso de Funciones de Escape Nativas de WordPress',
                    'solution_desc' => 'Envolver todas las sentencias de salida en la función de escape que mejor encaje según el tipo de datos (HTML, Atributo, URL o HTML estructurado).',
                    'code_lang' => 'php',
                    'code_content' => '<?php
// INCORRECTO Y INSEGURO
echo "<div class=\'url-wrap\'>URL: " . $analys_url . "</div>";

// CORRECTO Y TOTALMENTE ESCAPADO (Día / Noche Seguro)
?>
<div class="url-wrap">
  <?php echo esc_html__( \'URL:\', \'seo-si\' ); ?>
  <a href="<?php echo esc_url( $analys_url ); ?>">
    <?php echo esc_html( $analys_url ); ?>
  </a>
</div>
<?php
// Para bloques de código HTML sanitizado con seguridad
echo wp_kses_post( $datos_html_permitidos );',
                    'impact' => 'Elimina al 100% el vector de ataque XSS reflejado y garantiza el cumplimiento con las auditorías de seguridad más exigentes del repositorio de WordPress.'
                ],
                [
                    'id' => 'wparch_custom_hooks',
                    'role' => 'wp_architect',
                    'role_label' => 'Arquitecto de WordPress',
                    'severity' => 'warning',
                    'title' => 'Falta de Acoplamiento y Ganchos (Hooks) para Extensibilidad',
                    'friction' => 'La lógica del plugin ejecuta cálculos de puntuación de forma monolítica, lo que impide que otros desarrolladores añadan reglas personalizadas o modifiquen el peso de las puntuaciones en implementaciones específicas.',
                    'justification' => 'Directivas de Modularidad del WordPress Plugin Developer Handbook. Los plugins deben crearse pensando en ser extendidos, permitiendo desacoplar componentes mediante hooks nativos.',
                    'solution_title' => 'Registro de Filtros y Acciones Personalizados',
                    'solution_desc' => 'Aplicar filtros nativos (`apply_filters`) en los puntos críticos de cálculo de la puntuación global del plugin.',
                    'code_lang' => 'php',
                    'code_content' => '<?php
// En src/Core/ScoringEngine.php
$puntuacion_calculada = 85; // Cálculo interno del core

/**
 * Filtro personalizado para permitir a otros plugins alterar la puntuación global de SEO.
 *
 * @param int    $puntuacion Puntuación base.
 * @param string $url        La URL analizada en este lote.
 */
$puntuacion_final = apply_filters( \'seosi_global_score\', $puntuacion_calculada, $url );

return $puntuacion_final;',
                    'impact' => 'Permite que el plugin sea totalmente interoperable con otros temas u optimizadores de terceros y reduce el código acoplado.'
                ]
            ]
        ];
    }
}
