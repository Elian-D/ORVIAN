# Arquitectura del Microservicio de Reconocimiento Facial

![Python](https://img.shields.io/badge/Python-3.11-blue)
![FastAPI](https://img.shields.io/badge/FastAPI-0.110-green)
![Docker](https://img.shields.io/badge/Docker-Compatible-blue)
![Laravel](https://img.shields.io/badge/Laravel-Client-red)

ORVIAN implementa el reconocimiento facial como un **microservicio Python independiente** que se comunica con el backend Laravel vía HTTP. Esta separación es una decisión arquitectónica deliberada: el procesamiento biométrico requiere librerías C nativas (`dlib`, `face_recognition`) que no tienen equivalente PHP, y el aislamiento en un contenedor evita contaminar el entorno Laravel con dependencias pesadas.

---

## Tabla de Contenido

- [Arquitectura General](#arquitectura-general)
- [Repositorio y Estructura del Proyecto](#repositorio-y-estructura-del-proyecto)
- [Servicios Python Internos](#servicios-python-internos)
- [Endpoints de la API](#endpoints-de-la-api)
- [Cliente HTTP en Laravel](#cliente-http-en-laravel)
- [Flujo de Enrollment (Registro de Rostro)](#flujo-de-enrollment-registro-de-rostro)
- [Flujo de Verificación (Identificación en la Puerta)](#flujo-de-verificación-identificación-en-la-puerta)
- [Consideraciones de Privacidad](#consideraciones-de-privacidad)
- [Deployment con Docker](#deployment-con-docker)
- [Variables de Entorno](#variables-de-entorno)

---

## Arquitectura General

```
┌────────────────────────────────────────────────────────────────┐
│  ORVIAN Laravel App                                            │
│                                                                │
│  FaceEncodingManager                                           │
│  └─ enrollStudent()   ──────────────────────────────────────┐ │
│  └─ identifyStudent() ──────────────────────────────────────┤ │
│                                                              │ │
│  FacialApiClient                                             │ │
│  └─ enrollFace()      ──────────► POST /api/v1/enroll/  ──┐ │ │
│  └─ verifyFace()      ──────────► POST /api/v1/verify/  ──┤ │ │
│  └─ health()          ──────────► GET  /health          ──┤ │ │
│                                                           │ │ │
└───────────────────────────────────────────────────────────┼─┘ │
                                                            │   │
                              ┌─────────────────────────────▼───┘
                              │  orvian-facial-recognition (Docker)
                              │  Puerto: 8001                    │
                              │                                  │
                              │  FastAPI + face_recognition      │
                              │                                  │
                              │  FaceDetectionService            │
                              │  FaceEncodingService             │
                              │  FaceMatchingService             │
                              │                                  │
                              │  Redis (cache de encodings)      │
                              └──────────────────────────────────┘
```

El Laravel **nunca procesa imágenes directamente**. Solo envía los bytes de imagen al microservicio y recibe el resultado estructurado. El microservicio es stateless desde la perspectiva de Laravel — no guarda nada en su propia base de datos. Los encodings se almacenan en la base de datos principal de ORVIAN.

---

## Repositorio y Estructura del Proyecto

**Repositorio separado:** `orvian-facial-recognition`

```
orvian-facial-recognition/
├── app/
│   ├── main.py                    # Punto de entrada FastAPI, CORS, middleware
│   ├── config.py                  # Settings con pydantic-settings (lee .env)
│   ├── models/
│   │   └── schemas.py             # Pydantic request/response schemas
│   ├── services/
│   │   ├── face_detection.py      # Detección de rostros (HOG/CNN)
│   │   ├── face_encoding.py       # Generación de vectores 128-dim
│   │   └── face_matching.py       # Comparación y búsqueda del mejor match
│   ├── routers/
│   │   ├── health.py              # GET /health
│   │   ├── enroll.py              # POST /api/v1/enroll/
│   │   └── verify.py              # POST /api/v1/verify/
│   └── utils/
│       └── image_processing.py    # Preprocesamiento y validación de imágenes
├── tests/
│   ├── test_face_detection.py
│   └── test_face_matching.py
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
├── .env.example
└── README.md
```

### Dependencias principales (`requirements.txt`)

```
fastapi==0.110.0
uvicorn[standard]==0.27.1
python-multipart==0.0.9
face-recognition==1.3.0        # Wrapper sobre dlib
opencv-python==4.9.0.80        # Procesamiento de imágenes
numpy==1.26.4
pillow==10.2.0
python-dotenv==1.0.1
pydantic==2.6.1
pydantic-settings==2.1.0
redis==5.0.1                   # Cache de encodings (opcional)
```

> `face-recognition` requiere que `dlib` esté compilado con soporte de CUDA para usar el modelo CNN. En modo CPU el modelo HOG es suficiente para producción y no requiere GPU.

---

## Servicios Python Internos

### `FaceDetectionService`

Localiza los rostros dentro de una imagen antes de generar el encoding.

```python
detect_faces(image_bytes) → List[Tuple]
    # Usa face_recognition.face_locations(model=self.model)
    # Retorna lista de bounding boxes (top, right, bottom, left)

has_single_face(image_bytes) → bool
    # True solo si hay exactamente 1 rostro. Enrollment requiere esto.

get_largest_face(image_bytes) → Optional[Tuple]
    # Si hay múltiples rostros (ej. verificación rápida en puerta),
    # retorna el bounding box del más grande (el más cercano a la cámara).
```

**Modelos disponibles:**
- `hog` (default): CPU, rápido, suficiente para imágenes de buena calidad.
- `cnn`: GPU, más preciso en condiciones adversas (iluminación, ángulo). Configurable via `FACE_DETECTION_MODEL`.

### `FaceEncodingService`

Genera el vector de características biométricas a partir de la imagen.

```python
generate_encoding(image_bytes) → Optional[List[float]]
    # Retorna lista de 128 floats (vector de características de dlib)
    # None si no detecta ningún rostro.

generate_multiple_encodings(image_bytes, num_jitters=10) → List[List[float]]
    # Para enrollment más robusto: genera N encodings con jitter aleatorio
    # y retorna el promedio. Mayor num_jitters = mayor precisión, menor velocidad.
```

El vector de 128 floats es la "huella digital" biométrica del rostro. Tiene ~1KB en JSON. Dos fotos del mismo rostro producen vectores similares; la distancia euclidiana entre ellos determina si corresponden a la misma persona.

### `FaceMatchingService`

Compara encodings para determinar identidad.

```python
compare_faces(known, unknown) → Dict
    # Compara un encoding conocido contra uno desconocido
    # → { match: bool, distance: float, confidence: float }
    # distance: 0.0 = mismo rostro. < tolerance = match.
    # confidence: (1 - distance) * 100, representado como porcentaje.

find_best_match(unknown_encoding, known_encodings) → Optional[Dict]
    # Itera la lista [ {id, name, encoding}, ... ]
    # Retorna el mejor match dentro de la tolerancia configurada.
    # → { student_id, student_name, confidence, distance }
    # None si ningún encoding supera el umbral de tolerancia.
```

**Tolerancia:** Configurable via `TOLERANCE` (default `0.6`). Valores menores son más estrictos. `0.5` es recomendado para mayor seguridad; `0.6` balancea precisión con cobertura en condiciones variables de iluminación.

---

## Endpoints de la API

### `GET /health`

Verifica que el microservicio está corriendo y accesible desde Laravel.

**Respuesta:**
```json
{
    "status": "healthy",
    "version": "1.0.0"
}
```

**Timeout Laravel:** 5 segundos. Si falla, `FaceEncodingManager::isServiceHealthy()` retorna `false` y la interfaz muestra el escáner facial como no disponible.

---

### `POST /api/v1/enroll/`

Registra el encoding facial de un estudiante. Se llama una vez (o para actualizar la foto).

**Request:** `multipart/form-data`
```
student_id: int        (ID del estudiante en ORVIAN)
school_id:  int        (para logging y auditoría)
image:      File       (foto del estudiante — JPEG/PNG, max 5MB)
```

**Respuesta exitosa (`200`):**
```json
{
    "success": true,
    "student_id": 42,
    "encoding": [0.12, -0.34, 0.56, ...],   // 128 floats
    "faces_detected": 1,
    "message": "Encoding generado correctamente."
}
```

**Casos de error:**
```json
// Sin rostro detectado
{ "success": false, "faces_detected": 0, "message": "No se detectó ningún rostro en la imagen." }

// Múltiples rostros
{ "success": false, "faces_detected": 3, "message": "Se detectaron 3 rostros. La foto debe contener solo al estudiante." }

// Fallo de encoding
{ "success": false, "faces_detected": 1, "message": "No se pudo generar el encoding. Intenta con mejor iluminación." }
```

---

### `POST /api/v1/verify/`

Identifica quién es la persona frente a la cámara comparándola contra los encodings del plantel.

**Request:** `multipart/form-data`
```
school_id:       int           (ID de la escuela)
known_encodings: string/JSON   (array de {id, name, encoding})
image:           File          (foto capturada en tiempo real)
```

**Formato de `known_encodings`:**
```json
[
    {"id": 1, "name": "María García", "encoding": [0.12, ...]},
    {"id": 2, "name": "Carlos Pérez", "encoding": [-0.05, ...]},
    ...
]
```

**Respuesta exitosa con match (`200`):**
```json
{
    "success": true,
    "matched": true,
    "student_id": 1,
    "student_name": "María García",
    "confidence": 87.3,
    "distance": 0.127,
    "faces_detected": 1,
    "message": "Estudiante identificado: María García (87.3% de confianza)"
}
```

**Respuesta sin match (`200`):**
```json
{
    "success": true,
    "matched": false,
    "student_id": null,
    "student_name": null,
    "confidence": null,
    "distance": null,
    "faces_detected": 1,
    "message": "No se encontró coincidencia en el sistema."
}
```

---

## Cliente HTTP en Laravel

### `FacialApiClient`

Responsable de la comunicación HTTP con el microservicio. Inyectable via IoC de Laravel.

**Ubicación:** `app/Services/FacialRecognition/FacialApiClient.php`

```php
health(): array
    // GET /health — timeout: 5s
    // Lanza \Exception si el servicio no responde.

enrollFace(int $studentId, int $schoolId, UploadedFile $image): array
    // POST /api/v1/enroll/ — timeout: 30s
    // Envía imagen como multipart. Retorna el array de respuesta.
    // Lanza \Exception si $response->failed().

verifyFace(int $schoolId, array $knownEncodings, UploadedFile $image): array
    // POST /api/v1/verify/ — timeout: 30s
    // Serializa $knownEncodings a JSON para el campo known_encodings.
    // Lanza \Exception si $response->failed().
```

Los timeouts son más largos que en health (30s vs 5s) porque la inferencia CNN puede tomar varios segundos en hardware sin GPU.

### `FaceEncodingManager`

Orquesta la integración entre el cliente HTTP y la base de datos ORVIAN.

**Ubicación:** `app/Services/FacialRecognition/FaceEncodingManager.php`

```php
enrollStudent(Student $student, UploadedFile $photo): bool
    // 1. Llama FacialApiClient::enrollFace()
    // 2. Si success: Student::update(['face_encoding' => json_encode($encoding)])
    // 3. Retorna true/false

identifyStudent(int $schoolId, UploadedFile $photo): ?array
    // 1. Carga todos los encodings activos del school en memoria:
    //    Student::active()->where('school_id', $schoolId)
    //          ->whereNotNull('face_encoding')
    //          ->get(['id', 'first_name', 'last_name', 'face_encoding'])
    // 2. Construye el array known_encodings
    // 3. Llama FacialApiClient::verifyFace()
    // 4. Retorna {student_id, student_name, confidence, distance} o null

isServiceHealthy(): bool
    // Llama health() y captura excepciones → true/false
```

**Configuración en `config/services.php`:**
```php
'facial_api' => [
    'url' => env('FACIAL_API_URL', 'http://localhost:8001'),
    'key' => env('FACIAL_API_KEY'),
],
```

---

## Flujo de Enrollment (Registro de Rostro)

```
Administrador abre ficha del estudiante
        │
        ▼
Click "Capturar Rostro" → Modal de cámara
        │
        ▼
Cámara del dispositivo captura foto
        │
        ▼
Livewire envía foto como UploadedFile
        │
        ▼
FaceEncodingManager::enrollStudent($student, $photo)
        │
        ├─► FacialApiClient::enrollFace()
        │       │
        │       ▼
        │   POST /api/v1/enroll/ (multipart, 30s timeout)
        │       │
        │       ▼
        │   FaceDetectionService::has_single_face()
        │       │
        │       ├─ No face → Error response
        │       ├─ Multiple faces → Error response
        │       └─ Single face →
        │
        │   FaceEncodingService::generate_multiple_encodings(jitters=10)
        │       │
        │       └─ encoding: [128 floats]
        │
        │   Response: { success: true, encoding: [...] }
        │
        ├─► Student::update(['face_encoding' => json_encode($encoding)])
        │
        ▼
Toast: "Rostro registrado exitosamente."
Ícono de biometría activado en ficha del estudiante
```

---

## Flujo de Verificación (Identificación en la Puerta)

```
Escáner facial abierto en portería
        │
        ▼
Cámara en loop — frame capturado cada N segundos
        │
        ▼
FaceEncodingManager::identifyStudent($schoolId, $capturedFrame)
        │
        ├─► Student::active()
        │       ->where('school_id', $schoolId)
        │       ->whereNotNull('face_encoding')
        │       ->get(['id', 'first_name', 'last_name', 'face_encoding'])
        │   (Una sola query — todos los encodings del plantel en memoria)
        │
        ├─► FacialApiClient::verifyFace($schoolId, $knownEncodings, $frame)
        │       │
        │       ▼
        │   POST /api/v1/verify/ (multipart, 30s timeout)
        │       │
        │       ▼
        │   FaceDetectionService::get_largest_face()
        │   FaceEncodingService::generate_encoding()
        │   FaceMatchingService::find_best_match()
        │       │
        │       └─ Response: { matched: true/false, student_id, confidence }
        │
        ├─ matched: false → "No identificado" en UI
        │
        └─ matched: true →
                │
                ▼
        PlantelAttendanceService::recordAttendance([
            'student_id'  => $result['student_id'],
            'method'      => PlantelAttendanceRecord::METHOD_FACIAL,
            ...
        ])
                │
                ▼
        UI muestra: foto + nombre + confianza + estado registrado
```

---

## Consideraciones de Privacidad

El diseño del microservicio prioriza la privacidad de los datos biométricos de los estudiantes:

**Los encodings, no las fotos.** La base de datos de ORVIAN almacena únicamente el vector de 128 floats (`face_encoding`), no las fotografías capturadas. Un vector de encoding no puede revertirse a la imagen original — es una huella matemática, no una foto.

**Las fotos de verificación nunca se persisten.** Cuando se captura un frame para identificar a un estudiante en la puerta, esa imagen se envía al microservicio, se procesa en memoria y se descarta. No se guarda en disco ni en base de datos.

**El microservicio es stateless.** No tiene base de datos propia ni filesystem persistente. No almacena ninguna imagen ni encoding entre requests. Cada llamada a `/verify` recibe todos los encodings conocidos en el payload y los procesa en memoria durante esa petición.

**Datos biométricos en el modelo Student.** El campo `face_encoding` en la tabla `students` es un `longText` que almacena el JSON del vector. Es recomendable agregar este campo a la propiedad `$hidden` del modelo para evitar que viaje en consultas masivas innecesariamente (ej. `Student::all()` en otros contextos).

**Consentimiento.** La captura de datos biométricos debe realizarse con consentimiento informado del estudiante o tutor. ORVIAN provee la infraestructura técnica; la política de consentimiento es responsabilidad del centro educativo.

---

## Deployment con Docker

### `Dockerfile`

```dockerfile
FROM python:3.11-slim

# Dependencias del sistema para compilar dlib y face_recognition
RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    libopenblas-dev \
    liblapack-dev \
    libx11-dev \
    libgtk-3-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .
EXPOSE 8001
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
```

> La compilación de `dlib` puede tomar varios minutos en el primer build. Una vez compilada la imagen, los arranques posteriores son instantáneos.

### `docker-compose.yml`

```yaml
services:
  facial-api:
    build: .
    ports:
      - "8001:8001"
    environment:
      - API_KEY=${FACIAL_API_KEY}
      - FACE_DETECTION_MODEL=hog
      - TOLERANCE=0.6
      - REDIS_HOST=redis
    depends_on:
      - redis

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
```

### Verificar que está corriendo

```bash
# Desde el servidor Laravel
curl http://localhost:8001/health

# Respuesta esperada:
# {"status":"healthy","version":"1.0.0"}
```

### Integración con Laravel Sail

Si usas Laravel Sail, agregar el servicio en `docker-compose.yml` del proyecto Laravel:

```yaml
facial-api:
    build:
        context: ../orvian-facial-recognition
    ports:
        - '${FACIAL_API_PORT:-8001}:8001'
    environment:
        API_KEY: '${FACIAL_API_KEY}'
```

---

## Variables de Entorno

### En el microservicio Python (`.env`)

```env
# Autenticación
API_KEY=your-secret-key-here

# CORS — lista separada por comas de orígenes permitidos
ALLOWED_ORIGINS=http://localhost,https://tudominio.com

# Detección: "hog" (CPU, default) o "cnn" (GPU, más preciso)
FACE_DETECTION_MODEL=hog

# Encoding: "large" (más preciso) o "small" (más rápido)
FACE_ENCODING_MODEL=large

# Tolerancia de matching: 0.4 (estricto) - 0.6 (permisivo, default)
TOLERANCE=0.6

# Tamaño máximo de imagen en bytes
MAX_IMAGE_SIZE=5242880

# Redis (opcional — cache de encodings)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
```

### En Laravel (`.env`)

```env
FACIAL_API_URL=http://localhost:8001
FACIAL_API_KEY=your-secret-key-here
```

> La `FACIAL_API_KEY` debe coincidir entre ambos entornos. El microservicio valida el header `X-API-Key` en cada request autenticado.