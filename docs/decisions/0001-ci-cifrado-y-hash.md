# 0001 — Almacenamiento de la cédula de identidad (CI): cifrado + hash

## Contexto

El cliente se identifica por **CI**, que es la clave de enlace con la base de
clientes existente. Necesitamos:

1. Buscar clientes por CI (igualdad) durante el reclamo y la importación CSV.
2. No exponer el CI en claro si la base de datos se filtra.
3. Cumplir el requisito de protección de datos personales (AGENTS §10).

Un cast `encrypted` de Laravel usa cifrado no determinista: el mismo CI produce
valores distintos, por lo que **no sirve** para búsquedas por igualdad.

## Decisión

- `users.ci` y `customer_records.ci` se guardan con cast `encrypted`.
- Se agrega una columna `ci_hash` con **HMAC-SHA256** del CI normalizado, usando
  la clave `loyalty.ci.hash_key` (env `CI_HASH_KEY`, con fallback a `APP_KEY`).
- La búsqueda se hace por `ci_hash` (determinista), no por el valor cifrado.
- La normalización vive en `App\Domain\Customers\CiHasher`: `trim`, sin
  espacios ni puntos, mayúsculas, sin acentos, conservando el complemento
  (`7654321-1A`).

Las columnas `ci` se definen como `string(255)` porque el valor cifrado en
base64 supera los 32 caracteres.

## Consecuencias

- `CI_HASH_KEY` es un secreto: si se pierde o cambia, los `ci_hash` existentes
  dejan de coincidir. Debe respaldarse junto a `APP_KEY` y rotarse con
  migración/re-hash si fuera necesario.
- La unicidad por CI se garantiza con índices sobre el hash: `users.ci_hash`
  único y `customer_records` único `(brand_id, ci_hash)`.
- El hash es determinista: permite correlacionar registros que compartan CI sin
  conocer el valor en claro (limitación aceptada; HMAC con clave secreta evita
  ataques de diccionario triviales sobre CIs).

## Alternativas descartadas

- **Solo cifrado, sin hash:** imposible buscar por igualdad.
- **CI en claro:** incumple el requisito de protección de datos.
- **Hash sin sal/clave (SHA-256 simple):** vulnerable a fuerza bruta por el
  espacio reducido de CIs bolivianos.
