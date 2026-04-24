# API del Microservicio de Reconocimiento Facial

![FastAPI](https://img.shields.io/badge/FastAPI-0.110-green)
![Python](https://img.shields.io/badge/Python-3.11-blue)
![Version](https://img.shields.io/badge/API_Version-v1-orange)

Documentación completa de los endpoints del microservicio `orvian-facial-recognition`. Todos los endpoints excepto `/health` requieren autenticación mediante API key en el header.

---

## Tabla de Contenido

- [URL Base y Autenticación](#url-base-y-autenticación)
- [Convenciones de Respuesta](#convenciones-de-respuesta)
- [Endpoints](#endpoints)
  - [GET /health](#get-health)
  - [POST /api/v1/enroll/](#post-apiv1enroll)
  - [POST /api/v1/verify/](#post-apiv1verify)
- [Códigos de Error](#códigos-de-error)
- [Ejemplos con cURL](#ejemplos-con-curl)
- [Schemas Pydantic](#schemas-pydantic)
- [Límites y Restricciones](#límites-y-restricciones)

---

## URL Base y Autenticación

**URL por defecto (desarrollo):**
```
http://localhost:8001
```

**URL en producción:** Configurable vía `FACIAL_API_URL` en el `.env` de Laravel.

### Autenticación

Todos los endpoints excepto `/health` requieren el header `X-API-Key` con la clave configurada en `FACIAL_API_KEY`.

```http
X-API-Key: your-secret-api-key
Content-Type: multipart/form-data
```

Si la clave es inválida o está ausente, el servidor responde con `401 Unauthorized`.

---

## Convenciones de Respuesta

Todas las respuestas son JSON. Los campos siempre presentes son `success` (bool) y `message` (string).

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Descripción del resultado",
    ...campos específicos del endpoint
}
```

**Respuesta de error:**
```json
{
    "success": false,
    "message": "Descripción del error",
    ...campos adicionales según el tipo de error
}
```

---

## Endpoints

### GET /health

Verifica el estado del microservicio. No requiere autenticación. Útil para healthchecks de Docker y para que Laravel determine si el servicio está disponible antes de mostrar el escáner facial.

**Request:**
```http
GET /health HTTP/1.1
Host: localhost:8001
```

**Respuesta exitosa (`200`):**
```json
{
    "status": "healthy",
    "version": "1.0.0"
}
```

**Respuesta cuando el servicio está degradado (`503`):**
```json
{
    "status": "unhealthy",
    "version": "1.0.0",
    "detail": "Redis connection failed"
}
```

---

### POST /api/v1/enroll/

Genera y retorna el encoding facial (vector de 128 floats) para un estudiante. Debe llamarse una vez al registrar al estudiante, o para actualizar su biometría.

**Request:** `multipart/form-data`

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `student_id` | integer | Sí | ID del estudiante en ORVIAN |
| `school_id` | integer | Sí | ID del centro educativo |
| `image` | file | Sí | Foto del estudiante. JPEG o PNG. Máx 5MB. |

```http
POST /api/v1/enroll/ HTTP/1.1
Host: localhost:8001
X-API-Key: your-secret-api-key
Content-Type: multipart/form-data; boundary=----FormBoundary

------FormBoundary
Content-Disposition: form-data; name="student_id"

42
------FormBoundary
Content-Disposition: form-data; name="school_id"

3
------FormBoundary
Content-Disposition: form-data; name="image"; filename="student.jpg"
Content-Type: image/jpeg

[bytes de la imagen]
------FormBoundary--
```

**Respuesta exitosa (`200`):**
```json
{
    "success": true,
    "student_id": 42,
    "encoding": [
        0.12345678,
        -0.34567890,
        0.56789012,
        ...
    ],
    "faces_detected": 1,
    "message": "Encoding generado correctamente."
}
```

> `encoding` contiene exactamente 128 valores `float`. Este array es lo que Laravel debe almacenar en `Student.face_encoding` (serializado como JSON).

**Respuesta sin rostro (`200`):**
```json
{
    "success": false,
    "student_id": 42,
    "encoding": null,
    "faces_detected": 0,
    "message": "No se detectó ningún rostro en la imagen. Asegúrate de que la cara esté centrada y bien iluminada."
}
```

**Respuesta con múltiples rostros (`200`):**
```json
{
    "success": false,
    "student_id": 42,
    "encoding": null,
    "faces_detected": 3,
    "message": "Se detectaron 3 rostros en la imagen. La foto de enrollment debe contener únicamente al estudiante."
}
```

**Respuesta de fallo de encoding (`200`):**
```json
{
    "success": false,
    "student_id": 42,
    "encoding": null,
    "faces_detected": 1,
    "message": "Se detectó el rostro pero no fue posible generar el encoding. Intenta con mejor iluminación o mayor resolución."
}
```

---

### POST /api/v1/verify/

Identifica al estudiante frente a la cámara comparando el frame capturado contra los encodings conocidos del plantel.

**Request:** `multipart/form-data`

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `school_id` | integer | Sí | ID del centro educativo |
| `known_encodings` | string (JSON) | Sí | Array serializado de encodings conocidos. Ver formato abajo. |
| `image` | file | Sí | Frame capturado en tiempo real. JPEG o PNG. Máx 5MB. |

**Formato del campo `known_encodings`:**

Es un string JSON que contiene un array de objetos. Cada objeto tiene el ID del estudiante, su nombre y su encoding.

```json
[
    {
        "id": 1,
        "name": "María García",
        "encoding": [0.12, -0.34, 0.56, ...]
    },
    {
        "id": 2,
        "name": "Carlos Pérez",
        "encoding": [-0.05, 0.78, 0.23, ...]
    }
]
```

Laravel construye este array cargando todos los estudiantes activos del centro con `face_encoding != null` y los serializa con `json_encode`.

```http
POST /api/v1/verify/ HTTP/1.1
Host: localhost:8001
X-API-Key: your-secret-api-key
Content-Type: multipart/form-data; boundary=----FormBoundary

------FormBoundary
Content-Disposition: form-data; name="school_id"

3
------FormBoundary
Content-Disposition: form-data; name="known_encodings"

[{"id":1,"name":"María García","encoding":[0.12,-0.34,...]},...]
------FormBoundary
Content-Disposition: form-data; name="image"; filename="frame.jpg"
Content-Type: image/jpeg

[bytes del frame]
------FormBoundary--
```

**Respuesta con match exitoso (`200`):**
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

| Campo | Tipo | Descripción |
|---|---|---|
| `matched` | bool | `true` si se encontró coincidencia dentro del umbral de tolerancia |
| `student_id` | integer\|null | ID del estudiante identificado |
| `student_name` | string\|null | Nombre completo del estudiante |
| `confidence` | float\|null | Porcentaje de confianza (0-100). A mayor valor, mayor certeza. |
| `distance` | float\|null | Distancia euclidiana entre encodings. A menor valor, mayor similitud. |
| `faces_detected` | integer | Número de rostros detectados en el frame |

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
    "message": "No se encontró ningún estudiante registrado que corresponda al rostro detectado."
}
```

**Respuesta sin rostro en el frame (`200`):**
```json
{
    "success": true,
    "matched": false,
    "student_id": null,
    "student_name": null,
    "confidence": null,
    "distance": null,
    "faces_detected": 0,
    "message": "No se detectó ningún rostro en el frame capturado."
}
```

**Respuesta con encodings vacíos (`200`):**
```json
{
    "success": false,
    "matched": false,
    "message": "No hay encodings registrados para este centro educativo. Registra los rostros de los estudiantes primero."
}
```

---

## Códigos de Error

### Errores HTTP

| Código | Causa | Acción recomendada |
|---|---|---|
| `401 Unauthorized` | API key ausente o inválida | Verificar `FACIAL_API_KEY` en el `.env` de Laravel |
| `422 Unprocessable Entity` | Campos requeridos faltantes o tipos incorrectos | Revisar el payload del request (ver schemas) |
| `413 Request Entity Too Large` | Imagen mayor a 5MB (`MAX_IMAGE_SIZE`) | Comprimir la imagen antes de enviar |
| `500 Internal Server Error` | Error inesperado en el servidor | Revisar logs del contenedor Docker |
| `503 Service Unavailable` | Microservicio iniciando o Redis no disponible | Esperar y reintentar |

### Errores lógicos (dentro de respuesta `200`)

Estos no generan errores HTTP — son condiciones de negocio con `success: false`:

| Condición | `faces_detected` | `message` |
|---|---|---|
| Sin rostro en la imagen | `0` | "No se detectó ningún rostro..." |
| Múltiples rostros (solo en enroll) | `N > 1` | "Se detectaron N rostros..." |
| Fallo de encoding | `1` | "No fue posible generar el encoding..." |
| Sin match en verify | `1` | "No se encontró ningún estudiante..." |
| Lista de encodings vacía | N/A | "No hay encodings registrados..." |

---

## Ejemplos con cURL

### Health check

```bash
curl -X GET http://localhost:8001/health
```

### Enrollment desde terminal

```bash
curl -X POST http://localhost:8001/api/v1/enroll/ \
  -H "X-API-Key: your-secret-api-key" \
  -F "student_id=42" \
  -F "school_id=3" \
  -F "image=@/ruta/a/foto_estudiante.jpg"
```

### Verificación con encodings conocidos

```bash
# Primero, preparar los encodings como archivo JSON
echo '[{"id":1,"name":"María García","encoding":[0.12,-0.34,0.56]}]' > encodings.json

curl -X POST http://localhost:8001/api/v1/verify/ \
  -H "X-API-Key: your-secret-api-key" \
  -F "school_id=3" \
  -F "known_encodings=$(cat encodings.json)" \
  -F "image=@/ruta/al/frame_capturado.jpg"
```

### Prueba desde PHP (usando Guzzle)

```php
use Illuminate\Support\Facades\Http;

// Health check
$response = Http::timeout(5)
    ->get(config('services.facial_api.url') . '/health');

// Enrollment
$response = Http::timeout(30)
    ->withHeaders(['X-API-Key' => config('services.facial_api.key')])
    ->attach('image', file_get_contents($photo->path()), 'photo.jpg')
    ->post(config('services.facial_api.url') . '/api/v1/enroll/', [
        'student_id' => $studentId,
        'school_id'  => $schoolId,
    ]);

$data = $response->json();

if ($data['success']) {
    $encoding = $data['encoding']; // array de 128 floats
}
```

---

## Schemas Pydantic

Los schemas definen la estructura de datos del microservicio. Son útiles para entender exactamente qué valida el servidor.

### Request de Enrollment (`EnrollRequest`)

```python
class EnrollRequest(BaseModel):
    student_id: int
    school_id: int
    # image viene como UploadFile (multipart), no en el body JSON
```

### Response de Enrollment (`EnrollResponse`)

```python
class EnrollResponse(BaseModel):
    success: bool
    student_id: int
    encoding: Optional[List[float]] = None  # 128 floats o null
    faces_detected: int
    message: str
```

### Encoding conocido (`KnownEncoding`)

```python
class KnownEncoding(BaseModel):
    id: int                  # student_id en ORVIAN
    name: str                # full_name del estudiante
    encoding: List[float]    # vector de 128 floats
```

### Request de Verificación (`VerifyRequest`)

```python
class VerifyRequest(BaseModel):
    school_id: int
    known_encodings: List[KnownEncoding]
    # image viene como UploadFile (multipart)
```

### Response de Verificación (`VerifyResponse`)

```python
class VerifyResponse(BaseModel):
    success: bool
    matched: bool
    student_id: Optional[int] = None
    student_name: Optional[str] = None
    confidence: Optional[float] = None   # 0.0 - 100.0
    distance: Optional[float] = None     # 0.0 - 1.0+
    faces_detected: int
    message: str
```

---

## Límites y Restricciones

| Parámetro | Valor | Configurable |
|---|---|---|
| Tamaño máximo de imagen | 5MB | `MAX_IMAGE_SIZE` en `.env` |
| Timeout recomendado (enroll/verify) | 30s | En `FacialApiClient` de Laravel |
| Timeout recomendado (health) | 5s | En `FacialApiClient` de Laravel |
| Tolerancia de matching | 0.6 | `TOLERANCE` en `.env` (0.4–0.7 recomendado) |
| Modelo de detección | `hog` (CPU) | `FACE_DETECTION_MODEL` en `.env` |
| Jitters para enrollment | 10 | Hardcoded en `FaceEncodingService` |
| Dimensión del encoding | 128 floats | Fijo por la librería `dlib` |

### Sobre la tolerancia

La tolerancia (`TOLERANCE`) determina qué tan estricto es el sistema para considerar que dos encodings corresponden a la misma persona:

- `0.4` — Muy estricto. Pocas falsas aceptaciones, pero más rechazos de personas reales.
- `0.6` — Balance (default). Adecuado para condiciones de iluminación variable en planteles.
- `0.7` — Permisivo. Más falsos positivos. No recomendado para control de acceso.

En entornos con buena iluminación y cámaras de calidad, `0.5` es una buena opción. En entornos con iluminación inconsistente (entrada del plantel, luz natural variable), `0.6` reduce los rechazos injustificados.

### Rendimiento aproximado

| Hardware | Tiempo por request (verify, 50 encodings) |
|---|---|
| CPU moderno (sin GPU), modelo HOG | 800ms – 1.5s |
| CPU moderno, modelo CNN | 3s – 5s |
| GPU NVIDIA (CUDA), modelo CNN | 150ms – 400ms |

Para un plantel con 300 estudiantes y 250 con encoding registrado, el array `known_encodings` tiene un tamaño de aproximadamente 250KB en JSON — manejable en un single request.