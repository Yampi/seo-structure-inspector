# SEO Structure Inspector

Plugin de WordPress para análisis SEO/GE0/AEO completo. Inspecciona estructura HTML, uso de keywords, Schema.org, legibilidad, meta tags, LLMs/GEO, AEO, Core Web Vitals y enlaces.

## Características

- **Análisis HTML**: Estructura semántica, jerarquía de headings, etiquetas semánticas
- **Keyword Analyzer**: Presencia en title, meta description, H1, primer párrafo, H2, URL y densidad
- **Schema.org**: Detección y validación de JSON-LD y microdata
- **Legibilidad**: Flesch-Kincaid (español), voz pasiva, oraciones largas, párrafos largos
- **Meta Tags**: Title, description, canonical, robots, viewport, Open Graph, Twitter Card
- **LLMs/GEO**: Verificación de llms.txt, llms-full.txt y políticas de crawlers de IA en robots.txt
- **AEO**: Headings en formato pregunta, FAQ/QA Schema, definiciones, listas de respuesta
- **Core Web Vitals**: LCP, CLS, INP, FCP, TTFB vía Google PageSpeed API
- **Links**: Enlaces internos/externos, alt de imágenes, canonical
- **Batch Analysis**: Análisis masivo de múltiples URLs
- **Exportación**: Reporte HTML listo para imprimir/PDF

## Requisitos

- WordPress 6.0+
- PHP 8.1+

## Instalación

1. Sube la carpeta `seo-structure-inspector` a `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Accede a "SEO Inspector" en el menú de administración

## Uso

### Meta Box en Editor

El plugin añade un meta box en el editor de posts y páginas donde puedes:
- Ingresar una palabra clave objetivo
- Analizar la página actual directamente desde el editor

### Página de Administración

Accede a **SEO Inspector** → **Analizar URL** para:
- Analizar cualquier URL (no requiere que sea de tu sitio)
- Descubrir URLs desde un sitemap XML
- Ejecutar análisis batch de múltiples URLs

### Configuración

Accede a **SEO Inspector** → **Configuración** para:
- Configurar API Key de Google PageSpeed (opcional)
- Habilitar/deshabilitar análisis de Core Web Vitals
- Ajustar timeout de solicitudes HTTP

## Arquitectura

El plugin sigue una arquitectura modular con namespaces PSR-4:

```
src/
├── Admin/          # Componentes de administración
│   ├── MetaBox.php
│   ├── AdminPage.php
│   └── Settings.php
├── Ajax/           # Handlers AJAX
│   └── Handlers.php
├── Analyzers/      # Módulos de análisis
│   ├── HTMLInspector.php
│   ├── KeywordAnalyzer.php
│   ├── SchemaChecker.php
│   ├── ReadabilityAnalyzer.php
│   ├── MetaTagsAnalyzer.php
│   ├── LLMsChecker.php
│   ├── AEOAnalyzer.php
│   ├── CoreWebVitals.php
│   └── LinksAnalyzer.php
├── Services/       # Servicios de negocio
│   ├── AnalysisService.php
│   ├── FetcherService.php
│   ├── BatchAnalyzer.php
│   ├── PDFReport.php
│   └── SitemapReader.php
└── Core/           # Componentes centrales
    ├── Plugin.php
    ├── ScoringEngine.php
    ├── Hooks.php
    ├── Logger.php
    └── ViewRenderer.php
```

## Hooks y Filtros

El plugin proporciona hooks para extensibilidad:

### Análisis
- `seosi_filter_html` - Modificar HTML antes del análisis
- `seosi_filter_results` - Modificar resultados de análisis
- `seosi_after_analysis` - Acción después del análisis
- `seosi_filter_global_score` - Modificar cálculo de score global

### Módulos
- `seosi_filter_analyzers` - Agregar módulos personalizados
- `seosi_filter_module_result` - Modificar resultados de módulo específico

### Fetching
- `seosi_filter_fetch_args` - Modificar args de solicitud HTTP
- `seosi_filter_fetched_html` - Modificar HTML obtenido

### Batch
- `seosi_batch_created` - Acción al crear job de batch
- `seosi_batch_completed` - Acción al completar job de batch
- `seosi_batch_url_analyzed` - Acción al analizar cada URL del batch

### Reportes
- `seosi_filter_report_html` - Modificar HTML de reporte
- `seosi_filter_report_filename` - Modificar nombre de archivo de reporte

## Ejemplo de Uso de Hooks

```php
// Agregar un módulo personalizado
add_filter( 'seosi_filter_analyzers', function( $modules ) {
    $modules[] = 'MyCustomAnalyzer';
    return $modules;
} );

// Modificar score global
add_filter( 'seosi_filter_global_score', function( $score, $results ) {
    // Aplicar lógica personalizada
    return $score;
}, 10, 2 );
```

## Logging

El plugin incluye un sistema de logging que escribe a `debug.log` cuando `WP_DEBUG` está activado:

```php
use SEOSI\Core\Logger;

Logger::info( 'Mensaje informativo', [ 'context' => 'data' ] );
Logger::warning( 'Advertencia' );
Logger::error( 'Error' );
Logger::debug( 'Debug' );
```

## Seguridad

- Verificación de nonces en todas las solicitudes AJAX
- Verificación de capacidades de usuario
- Rate limiting por usuario
- Sanitización de todas las entradas
- Escapado de todas las salidas

## Créditos

Desarrollado por Brian Yamanué Baloa Gota
- [tecnicoelho.com](https://tecnicoelho.com)
- [LinkedIn](https://www.linkedin.com/in/brianbaloa/)

## Build de Producción

El directorio `vendor/` en este repositorio incluye dependencias de desarrollo (PHPUnit) para testing. Para generar el ZIP de distribución sin dependencias de dev:

**En Unix/Linux/Mac:**
```bash
bash build.sh
```

**En Windows (PowerShell):**
```powershell
.\build.ps1
```

Esto creará `seo-structure-inspector.zip` con:
- Dependencias de producción únicamente (sin PHPUnit)
- Autoloader optimizado
- Excluye: tests, archivos .md, phpunit.xml, composer.lock, build.sh, build.ps1

**IMPORTANTE**: Nunca distribuya el ZIP generado manualmente. Siempre use el script de build (`build.sh` o `build.ps1`) para asegurar que las dependencias de dev estén excluidas.

## Licencia

MIT License - Ver archivo LICENSE para detalles

## Changelog

### 0.5.3
- **Optimización de HTML**: Reducción de anidación innecesaria en estructura del dashboard
- **Mejora semántica**: Uso de elementos HTML apropiados (h2, h3, p) en lugar de divs genéricos
- **Layout corregido**: Sidebar y main ahora se alinean correctamente con wrapper flexbox
- **CSS optimizado**: Eliminación de clases innecesarias y simplificación de estilos
- **Grid mejorado**: Nueva clase `.grid-4` para layouts de 4 columnas

### 0.5.2
- **Corrección de seguridad**: XSS protection en JavaScript (escHtml() en updateProblemsTable)
- Análisis de seguridad completo realizado
- Documentación actualizada con findings de seguridad
- Versión lista para producción

### 0.5.1
- **Nueva interfaz de dashboard** con diseño dark theme moderno
- Sidebar con navegación y badges de score por módulo
- Cards de overview con donut score y radar chart
- Stats cards para problemas críticos, advertencias, aprobados y verificados
- Tabla de problemas dinámica
- Card de recomendación principal
- **Mejoras de seguridad**:
  - SSRF protection mejorado en FetcherService
  - Sanitización recursiva en export_report
- **Eliminación de código legacy**:
  - Radar interactivo antiguo eliminado
  - Sitemap discovery legacy eliminado
  - Batch analysis legacy eliminado
  - Theme toggle legacy eliminado
- CSS extraído a archivo separado (admin-dashboard.css)
- JavaScript simplificado para nueva interfaz

### 0.5.0
- Corrección de SSRF protection en FetcherService
- Validación de IPs privadas mejorada
- Actualización de versión a 0.5.0

### 0.4.0
- Refactorización completa con namespaces PSR-4
- Service layer separado
- Fetching separado del análisis
- Bootstrap class para inicialización
- Sistema de configuración con WordPress Settings API
- Sistema de hooks/filtros para extensibilidad
- Sistema de logging
- Separación de lógica de vista (templates)

### 0.3.0
- Migración a ScoringEngine centralizado
- Mejoras en validación de Schema.org

### 0.2.0
- Lanzamiento inicial
