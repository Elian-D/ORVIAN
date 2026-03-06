# ORVIAN  
## Sistema Integral de Gestión Educativa

## 📌 Descripción general

**ORVIAN** es un sistema web modular diseñado para modernizar y automatizar los procesos académicos y administrativos de centros educativos, con especial enfoque en **instituciones públicas dominicanas**.

El sistema integra **asistencia automatizada**, **gestión académica**, **registro y entrega de calificaciones**, y un **classroom local**, todo construido sobre una arquitectura **modular, escalable y mantenible**, basada en Laravel.

Este proyecto se desarrolla como **proyecto final académico**, priorizando la correcta **planificación**, **modelado del dominio**, **diseño modular** y el uso de **buenas prácticas de ingeniería de software**.

---

## 🎯 Objetivo del proyecto

Diseñar e implementar una solución que:

- Reduzca el tiempo y los errores en el registro de asistencia.
- Centralice la información académica y administrativa.
- Facilite el registro, validación y consulta de calificaciones.
- Proporcione un entorno académico digital funcional sin depender del uso de celulares.
- Permita una implementación progresiva por módulos.
- Sea adaptable a centros que acepten o no el uso de biometría.

---

## 🧩 Enfoque del sistema

ORVIAN se diseña bajo los siguientes principios:

- Arquitectura **modular (monolito modular)**.
- Arquitectura **multi-tenant**, donde cada centro educativo opera de forma independiente.
- **Biometría opcional**, nunca obligatoria.
- **Alternativa física no biométrica** (QR en brazalete).
- Implementación **por fases**.
- Separación clara entre **dominio, lógica de negocio y presentación**.

---

## 🐳 Entorno de desarrollo con Docker

El proyecto utiliza **Docker** para abstraer y estandarizar el entorno de desarrollo, permitiendo que todos los servicios necesarios para el sistema se ejecuten de forma aislada y reproducible.

Esto evita problemas comunes como:

- Diferencias de versiones entre entornos
- Problemas de configuración de dependencias
- Instalaciones complejas en el sistema operativo

Los servicios del sistema se ejecutan mediante **contenedores Docker**, incluyendo:

- Aplicación Laravel
- Servidor web
- Base de datos
- Servicios auxiliares

Esto permite que cualquier desarrollador pueda levantar el entorno completo del sistema con una configuración mínima.

---

## 🏗️ Arquitectura general

- **Backend:** Laravel (PHP)
- **Frontend:** Blade + Tailwind CSS + JavaScript
- **Base de datos:** MySQL / MariaDB
- **Contenerización:** Docker
- **Procesamiento externo (opcional):**
  - Servicio independiente en Python para reconocimiento facial
- **Estilo arquitectónico:** Monolito modular
- **Comunicación interna:** HTTP / API
- **Procesos asíncronos:** Laravel Jobs & Queues

---

## 🧱 Módulos del sistema

### 1. Core (Núcleo del sistema)

- Gestión de centros educativos
- Gestión de usuarios, roles y permisos
- Autenticación y autorización
- Manejo del año escolar activo
- Configuración general del sistema

---

### 2. Módulo de Asistencia

- Registro de asistencia mediante:
  - Reconocimiento facial (biométrico)
  - Código QR físico (no biométrico)
  - Registro manual autorizado
- Uso de una misma cámara para biometría y QR
- Control de horarios de entrada y salida
- Registro de eventos
- Alertas por tardanza y ausencia
- Reportes detallados de asistencia

---

### 3. Módulo Académico

- Gestión de niveles, cursos y secciones
- Asignaturas por nivel educativo
- Asignación de docentes
- Inscripción de estudiantes por año escolar

---

### 4. Módulo de Notas

- Registro de calificaciones por bloques o competencias
- Importación de notas desde archivos Excel
- Revisión y validación por el equipo de gestión
- Publicación controlada de calificaciones
- Consulta por estudiantes y padres
- Generación de boletines académicos en PDF

---

### 5. Classroom Local

- Publicación de tareas por asignatura
- Distribución de materiales educativos
- Visualización de tareas por estudiante
- Marcado y seguimiento de revisiones por el docente
- Integración opcional con el módulo de notas

---

### 6. Módulo de Horarios

- Horarios por curso y sección
- Horarios por docente
- Integración directa con el módulo de asistencia

---

### 7. Módulo de Reportes

- Reportes académicos
- Reportes de asistencia
- Estadísticas generales del centro
- Exportación a PDF y Excel

---

### 8. Módulos opcionales

- Gestión de incidencias y comportamiento
- Mensajería interna
- Constructor básico de páginas internas con plantillas predefinidas

---

## 🔄 Flujo de implementación

El desarrollo de ORVIAN sigue un flujo estructurado para minimizar retrabajos:

1. Definición del dominio y reglas del negocio
2. Modelado de flujos del sistema
3. Diseño de la arquitectura modular
4. Implementación del núcleo (Core)
5. Desarrollo del módulo de asistencia
6. Desarrollo del módulo académico
7. Desarrollo del módulo de notas
8. Implementación del classroom local
9. Integraciones externas
10. Pruebas, ajustes y validaciones finales

---

## 🔐 Seguridad y consideraciones legales

- Separación estricta de datos por centro educativo
- Control de accesos basado en roles y permisos
- Auditoría de eventos críticos
- Uso de biometría únicamente con consentimiento
- Alternativa no biométrica siempre disponible
- No almacenamiento de imágenes biométricas en el sistema principal

---

## 🛠️ Tecnologías utilizadas

- PHP 8+
- Laravel
- Blade
- Tailwind CSS
- JavaScript
- MySQL / MariaDB
- Docker
- Python (opcional para biometría)
- Git / GitHub

---

## 📦 Instalación (entorno de desarrollo)

### 1. Clonar repositorio

```bash
git clone https://github.com/Elian-D/orvian.git
cd orvian