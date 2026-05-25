### Estructura de Capas Definida para la IA
1. **Capa de Dominio (Entities):** Contiene los objetos de negocio, las reglas de validación empresarial y las invariantes del sistema. Es código puro de lenguaje base (PHP, JavaScript, Python), sin dependencias externas.
2. **Capa de Aplicación (Use Cases / Interactors):** Orquesta el flujo de datos desde y hacia el dominio. Representa las acciones que un usuario puede ejecutar en el sistema (ej. `RegistrarPedido`, `AplicarDescuento`).
3. **Capa de Interfaz / Adaptadores (Interface Adapters):** Convierte los datos en el formato más conveniente para los casos de uso y las entidades. Aquí residen los controladores de las APIs, los presentadores y las implementaciones concretas de interfaces de repositorios.
4. **Capa de Infraestructura (Frameworks & Drivers):** La capa más externa. Contiene la base de datos, el framework web, herramientas de telemetría y librerías de terceros.

---

## 3. Modelado del Dominio (Domain-Driven Design)

Para evitar la degradación del software a medida que crece el negocio, las soluciones propuestas por la IA deben estructurarse utilizando conceptos tácticos de **Domain-Driven Design (Eric Evans)**.

### Restricciones de Modelado
* **Contextos Acotados (Bounded Contexts):** El sistema debe dividirse en fronteras lógicas e independientes. Un cambio en el contexto de `Facturación` no puede afectar al contexto de `Inventario`. La IA debe identificar y separar explícitamente estas fronteras.
* **Lenguaje Ubicuo (Ubiquitous Language):** El código debe reflejar exactamente el lenguaje del negocio. Si los expertos del dominio llaman a un proceso *"Despacho"*, el código debe usar `Despacho` o `Dispatch`, nunca términos genéricos inventados por la IA como `Shipping` o `Delivery`.
* **Entidades vs. Value Objects:**
  * **Entidad:** Posee una identidad única que persiste en el tiempo (ej. `Pedido` con un `UUID`). Dos entidades con los mismos datos pero diferente ID son objetos distintos.
  * **Value Object (Objeto de Valor):** No tiene identidad, se define exclusivamente por sus atributos y es **inmutable** (ej. `Dinero(monto, moneda)`, `Direccion`). Si cambia una propiedad, se crea una nueva instancia. La IA debe priorizar el uso de Value Objects para encapsular validaciones complejas.

---

## 4. Checklist de Control Estricto: Auditoría SOLID y Clean Code

Cuando la IA genere código, se le debe exigir que pase la solución a través de este checklist de auditoría automatizable. Si viola una sola regla, la IA debe reescribir la solución de inmediato.

| Principio / Regla | Criterio de Control Estricto para la IA |
| :--- | :--- |
| **SRP** (Single Responsibility) | Cada clase, módulo o función debe tener **una sola razón para cambiar**. Si una función procesa lógica de negocio y además formatea un JSON para la respuesta HTTP, debe ser dividida inmediatamente. |
| **OCP** (Open/Closed) | El código debe estar **abierto para la extensión pero cerrado para la modificación**. Las nuevas características deben agregarse mediante polimorfismo, herencia o composición, nunca editando el código core existente. |
| **LSP** (Liskov Substitution) | Las clases derivadas deben poder usarse en lugar de sus clases base sin alterar la corrección del programa. Si un método hijo arroja una excepción no esperada por la interfaz base, viola Liskov. |
| **ISP** (Interface Segregation) | Ningún cliente debe ser forzado a depender de métodos que no utiliza. Es preferible tener muchas interfaces pequeñas y ultraespecíficas que una interfaz masiva y "todopoderosa". |
| **DIP** (Dependency Inversion) | Los módulos de alto nivel no deben depender de módulos de bajo nivel; ambos deben depender de **abstracciones**. Las clases dependientes deben inyectarse mediante interfaces (Inyección de Dependencias). |
| **Clean Code (Legibilidad)** | Nombres de variables autodescriptivos, funciones de menos de 20 líneas, eliminación total de números mágicos (reemplazarlos por constantes o enums) y evitación del anidamiento excesivo (utilizar cláusulas de guarda). |

---

## 5. Diseño y Gestión de Datos Intensivos

Basado en los principios de sistemas distribuidos y arquitecturas de datos modernas (**Martin Kleppmann**), todo flujo que involucre persistencia a escala debe ser evaluado minuciosamente por la IA bajo los siguientes criterios:

* **Análisis de Carga de Trabajo:** La IA debe preguntar o deducir si el flujo es de **Lectura Intensiva** (Read-Heavy) o de **Escritura Intensiva** (Write-Heavy) antes de diseñar la persistencia, sugiriendo estrategias de indexación o almacenamiento en caché (ej. Redis) consecuentes.
* **Inmutabilidad de Datos y Auditoría:** Para operaciones críticas del negocio (transacciones financieras, estados de pedidos), el estado no se sobreescribe a ciegas. Se debe diseñar pensando en logs append-only o arquitecturas basadas en eventos donde cada cambio sea rastreable.
* **Segregación de Consultas y Comandos (CQRS):** Para flujos de alta demanda, la IA debe proponer la separación de los modelos de escritura (comandos que mutan el estado) de los modelos de lectura (consultas optimizadas), evitando bloqueos en la base de datos principal.

---

## 6. Flujo de Trabajo en Antigravity (Protocolo de Prompting)

Para desmenuzar las ideas y soluciones con el control estricto deseado, utiliza la siguiente secuencia de interacción de tres pasos en tus cuadernos:

### Paso 1: Petición de Diseño (Fase de Abstracción)
```markdown
Usa el Marco de Referencia Arquitectónico. Tengo la siguiente idea/problema: [Describir la funcionalidad aquí]. 
Antes de generar código, entrégame un análisis de los componentes requeridos en las Capas de Dominio y Aplicación, e identifica los Contextos Acotados involucrados.