# Arquitectura

Sistema de fidelización multimarca (Zontes, Kiden, NIU). Mercado: Bolivia
(BOB, `America/La_Paz`, español).

## Stack

- PHP 8.2+, Laravel 12.
- Portal cliente: Blade + Livewire 3 + Tailwind (CDN en el prototipo).
- Panel admin: MVC + Blade + Policies (ver desviaciones).
- BD: MariaDB 10.4 / MySQL 8 en desarrollo; pruebas en SQLite `:memory:`.
- Pruebas: Pest. Calidad: Pint + Larastan (nivel 6).
- Colas/caché: `database` en el prototipo (Redis/Horizon en producción).

## Monolito modular, multimarca por columna

Una sola base de datos. La marca activa se determina por **subdominio** o
**prefijo de ruta** (`/{brand}`).

- `App\Domain\Brands\CurrentBrand`: singleton por request con la marca activa.
- `App\Http\Middleware\ResolveBrand`: resuelve y fija la marca.
- `App\Domain\Brands\BrandScope`: global scope que filtra por `brand_id`.
- `App\Domain\Brands\Concerns\BelongsToBrand`: aplica el scope y la relación
  `brand()` a los modelos con `brand_id`.

El admin puede desactivar el scope explícitamente con `withoutGlobalScope` (las
membresías del portal validan además la pertenencia al usuario autenticado).

## Estructura

```
app/
  Domain/
    Brands/      # Brand, CurrentBrand, BrandScope, BelongsToBrand, CiHasher
    Customers/   # BrandMember, CustomerRecord, Vehicle, Branch, BranchStaff,
                 # Enums (UserRole, MemberStatus) y Actions (RegisterCustomer)
    Loyalty/     # PointRule, PointWallet, PointLedger, Actions (AwardPoints,
                 # ExpirePoints, ConsumePoints, ReconcileWallet), PointsCalculator
    Referrals/   # Referral
    Imports/     # CsvImport, CsvImportError, PendingEvent, Jobs, Support
  Http/
    Controllers/Admin, Auth, Portal
    Middleware/ResolveBrand, RequireRole
    Requests/
  Livewire/Portal # WalletSummary, PointsHistory, ExpiringLots, ReferralPanel
  Models/         # Brand, User
  Policies/
  Console/Commands/ # points:expire, points:reconcile, imports:process
config/loyalty.php  # CI, importaciones, puntos, claim, referidos
```

## Modelo de datos (resumen)

`brands`, `users`, `brand_members`, `vehicles`, `branches`, `branch_staff`,
`customer_records`, `point_rules`, `point_wallets`, `point_ledger`,
`pending_events`, `referrals`, `csv_imports`, `csv_import_errors`,
`brand_admin`.

Índices clave: `point_ledger(brand_member_id, expires_at)`,
`point_ledger(idempotency_key)` único, `customer_records(brand_id, ci)` único.

## Flujos

### Acumulación (`AwardPoints`)

Evento de dominio → se buscan reglas activas de la marca/tipo vigentes en la
fecha → se calcula (`fixed`, `per_amount`, `percent`, respetando `min_amount`).
Cada acumulación crea un lote `earn` con `lot_remaining = points` y
`expires_at = fecha_evento + expires_after_days`, y actualiza
`point_wallets.balance` en la **misma transacción**.

Idempotencia: `point_ledger.idempotency_key` único y determinista
(`purchase:{brand}:{invoice}`, `maintenance:{brand}:{order}`,
`pending:{event_id}`, `expire:{ledger_id}`).

### Vencimiento (`ExpirePoints`, comando `points:expire`)

Por cada lote vencido con `lot_remaining > 0` crea un movimiento `expire`
negativo, deja `lot_remaining = 0` y descuenta la billetera. Idempotente.

### Canje FIFO (`ConsumePoints`)

Consume los lotes no vencidos ordenados por `expires_at ASC` (nulos al final),
decrementando `lot_remaining`; un canje puede abarcar varios lotes (detalle en
`point_ledger.meta.lots`). Bloqueos `lockForUpdate()` sobre la billetera y el
recurso para evitar doble gasto. Los `refund` restituyen puntos en un nuevo
lote.

### Reconciliación (`points:reconcile`)

El saldo de la billetera siempre debe ser igual a la suma de `lot_remaining`
vigente. El comando reconstruye y verifica.

## Independencia por marca

Los puntos son independientes por marca y nunca se suman, transfieren ni
canjean entre marcas. El portal cliente siempre consulta con el scope de marca
activo y valida que el `brand_member` pertenezca al usuario autenticado.

## Seguridad

- CI cifrado (cast `encrypted`) + `ci_hash` HMAC-SHA256 para búsqueda por
  igualdad; ver `docs/decisions/0001-ci-cifrado-y-hash.md`.
- Reclamo de cliente precargado por CI exige un segundo dato coincidente
  (teléfono o correo); si no, queda `pending_review`.
- Policies por modelo + verificación de pertenencia a marca (evitar IDOR).
- Form Requests para toda validación.

## Referencias

- `docs/csv-formats.md`
- `docs/decisions/`
- `AGENTS.md` (fuente de verdad del proyecto)
