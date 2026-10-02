# AGENTS.md — Plataforma de Fidelización Zontes / Kiden / NIU

Este archivo es la fuente de verdad para el agente. Léelo completo antes de escribir código.
Si algo aquí contradice una suposición tuya, **gana este archivo**. Si algo falta, usa la sección "Suposiciones y dudas" y no inventes reglas de negocio en silencio.

---

## 1. Proyecto

Aplicación web responsiva (cliente-servidor) que centraliza la fidelización de tres marcas de motos: **Zontes, Kiden y NIU**. Mercado: **Bolivia** (moneda BOB, zona horaria `America/La_Paz`, idioma **español**). Debe poder crecer a nuevas marcas y a otros países sin reescribir código.

Entregables finales: documento de arquitectura, prototipo funcional (usuarios + puntos operativos), repositorio documentado, manual de despliegue y de uso administrativo.

## 2. Decisiones de negocio ya cerradas (NO cambiar)

1. **Los puntos son independientes por marca.** Cada membresía de marca tiene su propia billetera. Nunca se suman, transfieren ni canjean entre marcas.
2. **No hay API de facturación en tiempo real. La información llega por archivos CSV** (clientes, compras, mantenimientos). Cada CSV se importa desde el panel admin y/o por comando artisan.
3. **El cliente se identifica por CI** (cédula de identidad). `ci` es la clave de enlace con la base de clientes existente.
4. **Vencimiento por lote.** Cada acumulación crea un lote con su propia fecha de vencimiento. El canje consume primero el lote que vence antes (FIFO por vencimiento).
5. **Referidos: dos momentos de premio**, ambos configurables como reglas:
   - al **registrarse** el referido (evento `referral_registered`),
   - al **primera compra** del referido en esa marca (evento `referral_purchase`).
6. **Los cupones se validan en sucursal y digitalmente.** El personal de sucursal valida y marca el cupón como usado desde un panel; además cualquier cupón puede verificarse digitalmente por código/QR.

## 3. Stack obligatorio

| Capa | Tecnología |
|---|---|
| Lenguaje / framework | PHP 8.3+, Laravel (última estable) |
| Panel administrativo | **Filament** (último mayor compatible) |
| Portal cliente | Blade + **Livewire** + Tailwind CSS |
| Auth | Laravel Breeze (Livewire) o Fortify; 2FA obligatorio para administradores |
| Roles y permisos | `spatie/laravel-permission` |
| Auditoría | `spatie/laravel-activitylog` |
| Respaldos | `spatie/laravel-backup` |
| Importación/Exportación | `maatwebsite/excel` (CSV/XLSX) |
| QR y PDF | `simplesoftwareio/simple-qrcode` y `barryvdh/laravel-dompdf` |
| BD | PostgreSQL (preferido) o MySQL 8 |
| Cola/caché | Redis + Laravel Horizon (en desarrollo puede usarse `database`) |
| Pruebas | **Pest** |
| Calidad | Laravel Pint, Larastan (nivel 6+), GitHub Actions |
| Entorno | Docker (Laravel Sail o compose propio) |

No agregues paquetes fuera de esta lista sin justificarlo en el PR/commit y en `docs/decisions/`.

## 4. Arquitectura

**Monolito modular, una sola base de datos, multimarca por columna `brand_id`.** No usar multi-tenancy por base de datos.

### 4.1 Resolución de marca
- Middleware `ResolveBrand`: determina la marca activa por **subdominio** (`zontes.dominio.bo`), con fallback por prefijo de ruta (`/zontes`) para desarrollo local.
- La marca activa se expone con un servicio `CurrentBrand` (singleton por request).
- Trait `BelongsToBrand` + global scope `BrandScope` en todos los modelos con `brand_id`. El panel admin puede desactivar el scope explícitamente con `withoutGlobalScope`, nunca por defecto.
- **Tema por marca:** la tabla `brands` guarda colores, logo y tipografía; el layout inyecta CSS variables (`--brand-primary`, etc.). Una sola base de vistas, tres identidades. Agregar una marca = un registro en `brands` + seeder, sin tocar código.

### 4.2 Estructura de carpetas

```
app/
  Domain/
    Brands/         # Brand, CurrentBrand, BrandScope
    Customers/      # User, BrandMember, Vehicle, Branch, claim por CI
    Loyalty/        # PointRule, PointLedger, PointWallet, Actions, Events, Listeners
    Referrals/
    Catalog/        # Reward
    Redemptions/    # Redemption (cupón), validación
    Imports/        # CsvImport, parsers, importers
  Http/
    Controllers/, Livewire/ (portal cliente), Middleware/ResolveBrand.php
    Requests/       # Form Requests siempre
  Filament/         # Resources, Pages y Widgets por dominio
  Console/Commands/ # points:expire, imports:process, etc.
docs/
  architecture.md, erd.md, csv-formats.md, deployment.md, admin-manual.md, decisions/
```

Reglas de código:
- La lógica de negocio vive en **Actions** (`AwardPoints`, `RedeemReward`, `ExpirePoints`, `ValidateCoupon`, `ProcessCsvImport`), clases de una sola responsabilidad con método `handle()`/`__invoke`. Controladores, componentes Livewire y recursos Filament solo orquestan.
- `declare(strict_types=1);` en todo archivo PHP nuevo. Tipos de retorno y de parámetros siempre.
- Enums PHP para estados y tipos (`LedgerType`, `RedemptionStatus`, `RuleEventType`, `CalcType`, `ImportType`).
- Dinero: montos en enteros (centavos) o `decimal(12,2)`; nunca `float`.
- Nombres de tablas/columnas en inglés `snake_case`; textos de interfaz en español vía archivos de idioma (`lang/es`), no hardcodeados.

## 5. Modelo de datos

```
brands            (id, slug UNIQUE, name, theme json, currency, active)
users             (id, name, email UNIQUE, password, ci UNIQUE, phone, birth_date null,
                   email_verified_at, ...)      -- identidad global, ci cifrado NO (se busca por él; ver §10)
brand_members     (id, user_id, brand_id, status, referral_code UNIQUE, joined_at,
                   UNIQUE(user_id, brand_id))   -- perfil por marca; dueño de la billetera
vehicles          (id, brand_member_id, vin null, model, purchase_date)
branches          (id, brand_id, name, city, active)
branch_staff      (user_id, branch_id)          -- usuarios que validan cupones

customer_records  (id, brand_id, ci, first_name, last_name, email null, phone null,
                   source_import_id, claimed_by_user_id null, UNIQUE(brand_id, ci))
                  -- clientes precargados por CSV, aún sin cuenta

point_rules       (id, brand_id, event_type, calc_type[fixed|per_amount|percent],
                   value, min_amount null, conditions json null,
                   expires_after_days, starts_at null, ends_at null, active)
point_wallets     (id, brand_member_id UNIQUE, balance)          -- caché del saldo
point_ledger      (id, brand_member_id, type[earn|redeem|expire|adjust|refund],
                   points (+/-), lot_remaining null, expires_at null,
                   rule_id null, source_type null, source_id null,
                   idempotency_key UNIQUE, note null, created_by null, created_at)

referrals         (id, brand_id, referrer_member_id, referred_member_id,
                   status[registered|purchased|rejected], UNIQUE(brand_id, referred_member_id))

rewards           (id, brand_id, name, description, points_cost, stock null, active,
                   valid_until null, image_path null)
redemptions       (id, brand_member_id, reward_id, points_spent,
                   status[issued|used|expired|cancelled], code UNIQUE,
                   issued_at, expires_at, used_at null, used_branch_id null,
                   validated_by null, cancelled_at null)

csv_imports       (id, brand_id, type[customers|purchases|maintenances], filename,
                   file_path, status[pending|processing|done|failed],
                   rows_total, rows_ok, rows_failed, user_id, started_at, finished_at)
csv_import_errors (id, csv_import_id, row_number, ci null, message, raw json)

events / event_attendances (asistencia a eventos, ver §6.4)
```

Índices obligatorios: `point_ledger(brand_member_id, expires_at)`, `point_ledger(idempotency_key)`, `redemptions(code)`, `customer_records(brand_id, ci)`.

## 6. Reglas de negocio detalladas

### 6.1 Ledger y billetera
- `point_ledger` es **inmutable**: jamás `UPDATE` de puntos ni `DELETE`. Las correcciones son nuevos movimientos (`adjust`, `refund`). La única columna que se actualiza es `lot_remaining` al consumir un lote.
- `point_wallets.balance` se actualiza **en la misma transacción** que el movimiento. Debe poder reconstruirse con `php artisan points:reconcile` (suma de lotes vigentes) y las pruebas deben verificar que coincide.
- **Idempotencia:** toda acumulación proveniente de una fuente externa lleva `idempotency_key` determinista, por ejemplo `purchase:{brand_id}:{numero_factura}`. Reprocesar el mismo CSV **no duplica puntos**.

### 6.2 Acumulación
Evento de dominio → listener en cola → busca reglas activas de **esa marca y tipo de evento y vigentes en la fecha** → calcula → crea lote `earn` con `expires_at = fecha_evento + expires_after_days`.
Tipos de evento: `purchase`, `maintenance`, `referral_registered`, `referral_purchase`, `event_attendance`, `manual`.
Cálculo: `fixed` (X puntos), `per_amount` (X puntos por cada N Bs, redondeo hacia abajo), `percent` (% del monto). Respetar `min_amount`.

### 6.3 Vencimiento por lote
- Cada `earn` define `lot_remaining` (inicia = puntos) y `expires_at`.
- Comando programado diario `points:expire`: por cada lote vencido con `lot_remaining > 0` crea un movimiento `expire` negativo, deja `lot_remaining = 0` y descuenta de la billetera. Debe ser idempotente (clave `expire:{ledger_id}`).
- **Canje FIFO:** al gastar puntos se consumen los lotes no vencidos ordenados por `expires_at ASC` (nulos al final), decrementando `lot_remaining`. Un canje puede abarcar varios lotes; registrar el detalle del consumo (columna `meta json` con lotes usados o tabla `ledger_lot_consumptions`).
- Aviso al cliente: notificación (correo + banner en portal) cuando tenga puntos que vencen en ≤ 30 días (configurable). Comando `points:notify-expiring`.

### 6.4 Referidos
1. Cada `brand_member` tiene `referral_code` único. El registro acepta `?ref=CODIGO`.
2. Al registrarse el referido: crear `referrals(status=registered)` y disparar `referral_registered` (puntos al **referidor**, y opcionalmente al referido según regla).
3. Al procesarse la **primera compra** del referido en esa marca (desde CSV): pasar a `purchased` y disparar `referral_purchase`. Solo la primera compra; guard con el estado.
4. Antifraude mínimo: no auto-referirse, un solo referidor por referido y marca, validar CI único, no premiar si el referido ya existía como cliente antes del registro (`customer_records` previo con compras → rechazar con status `rejected`).
5. Los puntos de referido se calculan por reglas, no están hardcodeados.

Asistencia a eventos: el admin crea un evento por marca y registra asistentes (selección manual o CSV de CIs); cada asistente dispara `event_attendance` con clave `event:{event_id}:{member_id}`.

### 6.5 Catálogo y canje
- Catálogo independiente por marca; un cliente solo ve y canjea recompensas de **la marca activa** con el saldo de **esa** marca.
- Canje en transacción: `lockForUpdate()` sobre la billetera y sobre la recompensa (stock). Validar saldo, stock, vigencia y `active`. Consumir lotes FIFO, crear `redemption` con **código único legible** (p. ej. `ZON-8F3K-92QD`, sin caracteres ambiguos) y emitir cupón.
- Cancelación/devolución: genera movimiento `refund` que **restituye puntos** (nuevo lote con el vencimiento original del lote consumido, o el mínimo de 30 días si ya pasó; documentar la decisión) y repone stock.
- El cupón tiene vigencia (`expires_at`). Los cupones vencidos pasan a `expired` por comando programado.

### 6.6 Validación de cupones (sucursal + digital)
- **Digital:** cada cupón tiene página pública `/c/{code}` (y QR que apunta a ella) que muestra solo: marca, recompensa, estado (válido / usado / vencido / cancelado) y fecha. **Sin datos personales.** Con rate limiting.
- **Sucursal:** rol `branch_staff`. Panel (Filament page o Livewire) donde el personal escanea el QR o escribe el código, ve el detalle (recompensa, cliente por nombre e inicial de CI) y pulsa **"Validar y marcar como usado"**. Registrar `used_at`, `used_branch_id`, `validated_by`.
- La validación es **atómica e idempotente**: `lockForUpdate()` y `status` debe ser `issued`; un segundo intento devuelve un mensaje claro "ya fue usado el … en sucursal …", nunca un doble uso.
- Un `branch_staff` solo puede validar cupones de las marcas a las que está asignado.
- Todo uso queda en auditoría.

## 7. Importación por CSV (reemplaza la API de facturación)

Formatos en `docs/csv-formats.md` (créalo y mantenlo sincronizado). Codificación UTF-8, separador `,` o `;` autodetectado, primera fila = encabezados.

| Tipo | Columnas mínimas |
|---|---|
| `customers` | `ci, nombres, apellidos, email, telefono, fecha_registro` |
| `purchases` | `ci, numero_factura, fecha, monto, vin(opcional), modelo(opcional)` |
| `maintenances` | `ci, numero_orden, fecha, tipo, vin(opcional)` |

> Estos encabezados son una **propuesta inicial**. Hazlos configurables en un mapeo de columnas por tipo, porque el formato real aún debe confirmarse con el cliente.

Reglas:
- La marca se elige al subir el archivo (o viene en una columna `marca` validada contra `brands.slug`).
- Flujo: subir → validar estructura → **vista previa** (primeras filas + conteo de errores) → confirmar → procesar en **cola** por lotes (`chunk`) → reporte final con filas OK/error y descarga del CSV de errores.
- Cada fila es independiente: un error no aborta el archivo. Errores se guardan en `csv_import_errors`.
- `customers`: *upsert* por `(brand_id, ci)` en `customer_records`. Normalizar CI (trim, mayúsculas, quitar puntos y espacios; conservar complemento si existe).
- `purchases` / `maintenances`: buscar `brand_member` por CI y marca. Si el cliente **aún no tiene cuenta**, guardar el evento como pendiente (`pending_events`) y **acreditar los puntos al reclamar la cuenta** (con la fecha original para el vencimiento). Esto evita perder compras anteriores al registro.
- Idempotencia por `numero_factura` / `numero_orden` + marca. Reimportar el mismo archivo no duplica nada.
- Rechazar archivos > límite configurable (ej. 10 MB) y filas con fecha futura o monto ≤ 0.
- Disponer también de `php artisan imports:process {file} --type= --brand=` para cargas por consola.

### Enlace cliente ↔ cuenta (por CI)
Al registrarse, el usuario ingresa su CI. Si existe en `customer_records` de la marca, se enlaza (`claimed_by_user_id`), se crea el `brand_member` y se acreditan los eventos pendientes. **Seguridad:** no enlazar solo con el CI (es dato semipúblico): exigir además verificación de correo y que coincida un segundo dato (teléfono o email del registro precargado); si no coincide, queda en estado `pending_review` para revisión del admin.

## 8. Roles y permisos

| Rol | Alcance |
|---|---|
| `customer` | Portal de su(s) marca(s): saldo, historial, catálogo, canjes, cupones, referidos |
| `branch_staff` | Solo validación de cupones de sus sucursales/marcas |
| `brand_admin` | Admin de una o varias marcas asignadas |
| `super_admin` | Todo, incluye marcas, usuarios admin y ajustes globales |

Políticas (`Policy`) para cada modelo; el scope de marca se aplica también a los administradores de marca. 2FA obligatorio para `brand_admin` y `super_admin`.

## 9. Panel administrativo (Filament)

Recursos: Marcas (tema), Usuarios y Membresías, Sucursales y personal, Reglas de puntos, Recompensas, Canjes/Cupones, Eventos y asistencia, Importaciones CSV, Referidos, Movimientos (solo lectura + ajuste manual con motivo obligatorio y auditoría).
Reportes (widgets y páginas): puntos emitidos/canjeados/vencidos por período, usuarios activos, canjes por recompensa, tendencia, top clientes, resultados de importaciones. Filtros por marca y fecha.
Exportación CSV/XLSX de usuarios, movimientos y canjes.
Gestión de contenido por marca: banners, textos legales y términos, configurables desde el panel.

## 10. Seguridad (requisitos no funcionales)

- Passwords con hash de Laravel; política de contraseñas; verificación de correo; *rate limiting* en login, registro, verificación pública de cupones y subida de CSV.
- Form Requests para toda validación; nunca confiar en datos del cliente (precio, puntos, marca).
- Autorización con Policies y verificación de pertenencia a marca en cada acción (evitar IDOR: un cliente jamás accede a datos de otro `brand_member`).
- Cifrar con casts `encrypted` los datos sensibles que no se usen como criterio de búsqueda (teléfono, fecha de nacimiento). El `ci` se busca por igualdad, por lo que se guarda además un `ci_hash` (HMAC con clave de app) para búsqueda y se almacena el valor cifrado; documenta la decisión en `docs/decisions/`.
- Los archivos CSV subidos se guardan en disco privado (nunca `public`), se eliminan o anonimizan tras un período configurable.
- Auditoría (`activitylog`) en reglas, ajustes manuales, canjes, validaciones y cambios de rol.
- HTTPS forzado en producción, cabeceras de seguridad, `APP_DEBUG=false`, secretos solo en `.env`.
- Cumplimiento de protección de datos personales: política de privacidad, consentimiento en registro, exportación y eliminación/anonimización de datos del usuario bajo solicitud.

## 11. Interfaz

- Mobile-first, responsiva. Accesibilidad: HTML semántico, contraste AA, foco visible, etiquetas en formularios, navegación por teclado, `alt` en imágenes.
- Selector/conmutador de marca si el usuario tiene membresía en varias; cada una muestra su propio saldo y tema.
- Pantallas del cliente: inicio con saldo y puntos por vencer, historial, catálogo, detalle de canje, mis cupones (con QR), referidos (código y enlace para compartir), perfil.
- Estados vacíos, mensajes de error y confirmaciones claras, todo en español.

## 12. Operación

- Scheduler (`routes/console.php`): `points:expire` diario, `points:notify-expiring` diario, `redemptions:expire` diario, `backup:run` diario y `backup:clean` semanal.
- Colas con Horizon/Supervisor. Logs estructurados; Sentry opcional.
- Respaldos a almacenamiento externo; documentar el procedimiento de **restauración** y probarlo.
- Docker para desarrollo; `docs/deployment.md` con pasos de producción (Nginx, PHP-FPM, Redis, Supervisor, SSL, variables de entorno, migraciones, cola, cron).

## 13. Pruebas (obligatorias)

Pest. Mínimo exigido antes de dar una fase por terminada:
- **Ledger:** acumulación, idempotencia (misma clave dos veces = un solo movimiento), saldo = suma de lotes.
- **Vencimiento por lote:** lote vencido genera `expire`; no afecta lotes vigentes; el comando es idempotente.
- **Canje FIFO:** consume lotes por vencimiento ascendente; canje sobre varios lotes; saldo insuficiente; stock agotado; **concurrencia** (dos canjes simultáneos con saldo para uno solo).
- **Independencia por marca:** puntos de Zontes jamás visibles ni gastables en NIU; scope de marca; IDOR.
- **Referidos:** premio al registro, premio solo en la primera compra, auto-referido rechazado, sin duplicados.
- **CSV:** archivo válido, filas inválidas, reimportación idempotente, cliente sin cuenta (evento pendiente) y posterior reclamo por CI.
- **Cupones:** validación en sucursal, doble uso rechazado, cupón vencido, staff de otra marca rechazado, página pública sin datos personales.
- **Autorización:** matriz de roles.

Cobertura objetivo ≥ 80% en `app/Domain`. `./vendor/bin/pest`, `./vendor/bin/pint --test` y `./vendor/bin/phpstan` deben pasar en CI.

## 14. Plan de trabajo por fases

Trabaja **una fase a la vez**, en orden. Al terminar cada fase: pruebas en verde, documentación actualizada, commit y resumen de lo hecho antes de continuar. No avances a la siguiente con pruebas fallando.

### Fase 0 — Cimientos
- [ ] Crear proyecto Laravel, Docker, `.env.example`, Pint, Larastan, Pest, GitHub Actions.
- [ ] Estructura `app/Domain/*`, `docs/`, README inicial.
### Fase 1 — Multimarca y autenticación
- [ ] Migraciones `brands`, roles; seeder con Zontes, Kiden y NIU (colores y logos placeholder).
- [ ] `ResolveBrand`, `CurrentBrand`, `BrandScope`, layout con tema por CSS variables.
- [ ] Registro/login/verificación, 2FA admin, roles y policies.
### Fase 2 — Usuarios y motor de puntos (**PROTOTIPO ENTREGABLE**)
- [ ] `users` (con CI), `brand_members`, `customer_records`, `vehicles`, `branches`.
- [ ] Importador CSV de `customers` con vista previa, cola y reporte de errores.
- [ ] Registro con reclamo por CI y verificación.
- [ ] `point_rules`, `point_ledger`, `point_wallets`, Actions `AwardPoints` / `ExpirePoints`, `points:expire`, `points:reconcile`.
- [ ] Importadores `purchases` y `maintenances` con idempotencia y eventos pendientes.
- [ ] Portal: saldo, historial, puntos por vencer.
- [ ] Panel Filament: usuarios, reglas, importaciones, movimientos.
- [ ] Pruebas completas del §13 para ledger, vencimiento, marca y CSV.
### Fase 3 — Referidos, catálogo y canjes
- [ ] Referidos en dos etapas (§6.4).
- [ ] Recompensas, canje transaccional FIFO, cupón con código + QR + PDF.
- [ ] Validación digital pública y panel de sucursal (§6.6).
- [ ] Cancelación/refund, vencimiento de cupones.
### Fase 4 — Administración y reportes
- [ ] Widgets y reportes, exportaciones, eventos/asistencia, contenido por marca, auditoría visible.
### Fase 5 — Endurecimiento y entrega
- [ ] Revisión de seguridad (§10), accesibilidad, rendimiento (N+1, índices, caché de reportes).
- [ ] Notificaciones de vencimiento, respaldos y prueba de restauración.
- [ ] `docs/architecture.md` (con diagrama ER y flujos de acumulación y canje), `docs/deployment.md`, `docs/admin-manual.md`, `docs/csv-formats.md`.
### Fase 6 (opcional) — API
- [ ] API REST `/api/v1` con Sanctum para futura integración directa con facturación/CRM, reutilizando las mismas Actions que los importadores CSV.

## 15. Flujo de trabajo del agente

1. Antes de codificar una fase: lista las tareas, los archivos que tocarás y las preguntas abiertas.
2. Escribe primero la prueba del comportamiento crítico (ledger, canje, importación) y luego la implementación.
3. Commits pequeños con **Conventional Commits** (`feat(loyalty): ...`, `fix(imports): ...`, `test:`, `docs:`).
4. Rama por funcionalidad (`feat/loyalty-ledger`); nunca commitear a `main` directamente.
5. Migraciones nuevas, nunca editar una migración ya aplicada/commiteada.
6. Seeders con datos de demostración: 3 marcas, reglas, recompensas, clientes de ejemplo, un admin y un staff de sucursal.
7. Documenta decisiones no triviales en `docs/decisions/NNNN-titulo.md`.
8. Ejecuta `pint`, `phpstan` y `pest` antes de cada commit.

## 16. Prohibiciones

- No modificar ni borrar movimientos del ledger. No calcular el saldo solo en memoria.
- No mezclar puntos entre marcas ni consultas sin scope de marca en el portal cliente.
- No guardar secretos ni datos reales de clientes en el repositorio (los CSV de prueba deben ser ficticios).
- No usar `float` para dinero ni puntos.
- No exponer datos personales en la verificación pública de cupones.
- No dejar lógica de negocio en controladores, vistas o componentes Livewire.
- No saltarte pruebas ni desactivar reglas de lint para "hacer pasar" el código.

## 17. Suposiciones y dudas (confirmar con el cliente; no bloquear el avance)

Mientras no haya respuesta, implementa lo indicado y deja el comportamiento **configurable**:

1. Formato real de los CSV (encabezados, separador, codificación, marca por columna o por archivo) y frecuencia de entrega.
2. Si el CI incluye complemento/extensión (ej. `1234567-1A`) y cómo normalizarlo.
3. Valores concretos de reglas: puntos por Bs, puntos por mantenimiento, premio por referido (registro y primera compra), días de vencimiento por lote.
4. Vigencia de los cupones y si el cupón puede canjearse en cualquier sucursal.
5. Si el aviso de vencimiento será por correo, SMS o WhatsApp (se implementa correo primero).
6. Qué segundo dato se usa para verificar la identidad al reclamar un cliente precargado (teléfono, correo o fecha de nacimiento).
7. Requisitos legales de protección de datos personales aplicables en Bolivia.

## 18. Definición de "terminado" (por fase)

- Todas las tareas de la fase marcadas, pruebas del §13 correspondientes en verde, `pint` y `phpstan` sin errores.
- Seeders y `README` permiten levantar el proyecto con `docker compose up` y `php artisan migrate --seed`.
- Documentación de la fase actualizada.
- Resumen final al usuario: qué se hizo, qué quedó pendiente y qué decisiones requieren confirmación.