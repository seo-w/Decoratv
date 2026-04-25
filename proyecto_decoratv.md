# Proyecto DecoraTV - Definición y Arquitectura

Este documento describe la arquitectura y el funcionamiento del sistema DecoraTV, un proyecto multi-tenant enfocado en la visualización y personalización de enmarcados.

## Resumen del Proyecto

El sistema se compone principalmente de dos áreas:
1. **Simulador Público:** Pantalla abierta a todos los usuarios para la libre interacción y configuración de productos.
2. **Panel de Administración:** Área segura que requiere autenticación (login), dedicada a la gestión de multi-tenants y a la edición de activos visuales (**Frames**, **Liners** y **Arts**). Cada activo gestionable consta de los siguientes campos principales:
   - Imagen
   - Nombre
   - Grupo
   - ID

---

## 1. Arquitectura de la Interfaz Gráfica

La interfaz ha sido diseñada bajo un concepto de **Estudio de Diseño Digital**, priorizando fuertemente la visualización del producto sobre los elementos convencionales de control.

### Área de Composición (Canvas)

- **Geometría:** Proporción fija de `794:455` para garantizar una alta precisión técnica.
- **Estética del Producto:** El visualizador prescinde intencionalmente de bordes redondeados (`rounded-none`) para emular la rigidez física y natural de los materiales de enmarcado reales.
- **Capas (Z-Stacking):** Se utiliza un sistema estricto de posicionamiento absoluto donde:
  - El **Frame** (Marco) al 100% actúa como una máscara superior con canal alfa.
  - El **Arte** flota en el centro de la composición.
  - El **Liner** (Marialuisa) actúa como la base inferior de montaje.

### Visualización de Identificadores (IDs)

- **Transparencia Técnica:** Debajo del área de composición, el sistema despliega en tiempo real los IDs internos de los elementos seleccionados (Frame, Liner y Arte). 
- **Propósito:** Facilita la comunicación entre el cliente y el asesor, permitiendo una referencia rápida y exacta de los materiales visualizados sin esperar al paso de cotización.

### Sidebar de Navegación

- **Micro-detalles en Miniaturas:** Las miniaturas de marcos y liners emplean un zoom del 500% anclado a la esquina superior izquierda. Esta técnica permite al cliente validar al detalle características físicas como la veta de la madera o la textura del metal antes de hacer su elección.
- **Identidad de Marca:** Integración del logotipo oficial de DecoraTV acompañado del uso de una paleta cromática sofisticada, basada en tonos neutros con elegantes acentos en color ámbar.

---

## 2. Funcionamiento Lógico (Agnóstico)

Para garantizar la viabilidad de la implementación de esta lógica en cualquier lenguaje de programación o framework, se deben observar estrictamente los siguientes principios:

- **Matriz de Proporciones:** Las dimensiones y coordenadas de los elementos internos (Liner y Arte) deben calcularse **siempre** como porcentajes relativos a las dimensiones del contenedor padre (Frame) para mantener una perfecta integridad visual en cualquier formato de pantalla o resolución.
- **Lógica Adaptativa:** Si el estado del Liner es `null` o `none` (es decir, el cliente opta por no usar marialuisa), el objeto "Arte" debe recibir automáticamente un factor de escala de recuperación mayor para llenar el vacío visual, garantizando conservar un centrado absoluto en el canvas.
- **Manejo de Activos con Alfa:** Es fundamental que el motor de renderizado elegido soporte la composición de imágenes tipo PNG con transparencia (canal alfa). Esto es estrictamente necesario para permitir el correcto solapamiento visual de las molduras sobre las obras de arte.

---

## 3. Flujo de Cotización

El botón de cotización es crítico, ya que actúa como el puente principal (conversión) entre la fase de diseño lúdico y la etapa de venta directa:

1. **Captura de Configuración:** El sistema extrae los nombres y los IDs de la combinación exacta (Frame + Liner + Arte). Estos IDs son los mismos que se muestran en tiempo real en la interfaz del simulador para garantizar consistencia.
2. **Formulario de Prospección:** Posteriormente, la interfaz despliega un modal superpuesto solicitando los datos de contacto del usuario potencial, acompañado de un resumen visual claro de la selección previamente realizada.
3. **Finalización y Envío:** Al confirmar y enviar el formulario, el sistema procesa toda la información capturada (Datos de Cliente + Selección Estética) empacando los datos para ser remitidos de manera automática, vía correo electrónico, directamente al equipo comercial o área de ventas.

---

## 4. Sistema de Notificaciones y SMTP

Para garantizar que ninguna cotización se pierda, el sistema integra un motor de envío profesional:
- **Motor de Envío:** Integración de **PHPMailer** para envíos autenticados mediante SMTP.
- **Seguridad:** Soporte para TLS/SSL y contraseñas de aplicación (necesario para proveedores como Gmail).
- **Log de Entregas:** Cada cotización registra en la base de datos si el correo fue enviado con éxito o si falló, permitiendo un seguimiento auditable desde el panel.
- **Herramienta de Diagnóstico:** El panel administrativo incluye un botón de "Test Connection" para validar la configuración del servidor de correo en tiempo real.

## 5. Panel de Administración y Gestión

- **Dashboard de Inquiries:** La sección de consultas muestra indicadores visuales (badges verdes/rojos) para el estado del correo, permitiendo al administrador saber si debe contactar proactivamente al cliente en caso de error.
- **Configuración Centralizada:** Un panel de "Settings" permite actualizar las credenciales SMTP y los correos receptores de forma segura y sin tocar código.

## 6. Experiencia Visual Refinada

- **Lightbox Personalizado:** Se ha implementado un visualizador de imágenes en alta resolución (Lightbox) centrado mediante Flexbox para una visualización perfecta.
- **Estética de Marca:** El fondo del visualizador utiliza el color corporativo `#cfc1b4` con tipografías de alto contraste en tonos oscuros, reforzando la percepción de producto premium y sofisticado.
