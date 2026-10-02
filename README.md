# Plataforma de Fidelización — Zontes / Kiden / NIU

Aplicación web para centralizar la fidelización de tres marcas de motos
(**Zontes**, **Kiden**, **NIU**) en Bolivia. Los puntos son **independientes por
marca**; la información de clientes, compras y mantenimientos llega por
**archivos CSV**.

El proyecto sigue `AGENTS.md` (fuente de verdad). Estado: **Fases 0 y 2
completadas** (cimientos, multimarca, autenticación, motor de puntos e
importación CSV; ver "Estado" más abajo).

## Requisitos

- PHP 8.2+ (extensiones habituales de Laravel).
- Composer 2.
- MariaDB 10.4 / MySQL 8 (o SQLite para pruebas).
- Node 22+ (opcional; el prototipo usa Tailwind por CDN).

## Instalación (desarrollo)

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Edita `.env` y define, al menos:

```
DB_DATABASE=zontes
DB_USERNAME=root
DB_PASSWORD=
CI_HASH_KEY=una-cadena-larga-y-secreta   # HMAC del ci_hash (no cambiar en caliente)
```

Crea la base de datos y carga los datos de demostración:

```bash
php artisan migrate --seed
```

Levanta el servidor:

```bash
php artisan serve
```

## Usuarios de demostración

| Rol | Email | Contraseña |
|---|---|---|
| Super admin | `admin@zontes.test` | `password` |
| Admin de Zontes | `zontes.admin@zontes.test` | `password` |
| Staff de sucursal | `staff@zontes.test` | `password` |

Los seeders cargan las marcas `zontes`, `kiden` y `niu`, reglas de puntos,
clientes precargados y una sucursal. **Solo para desarrollo.**

## Comandos útiles

```bash
# Motor de puntos
php artisan points:expire                 # genera vencimientos de lotes caducados
php artisan points:reconcile              # saldo de billetera = suma de lotes

# Importación CSV
php artisan imports:process archivo.csv --type=purchases --brand=zontes
#   --type: customers | purchases | maintenances

# Calidad y pruebas
vendor/bin/pest                           # suite Pest
vendor/bin/pint                           # formateo (o --test para verificar)
vendor/bin/phpstan analyse --no-progress  # Larastan nivel 6
```

El scheduler ejecuta `points:expire` a diario (`routes/console.php`).

## Resolución de marca

La marca activa se resuelve por **subdominio** (`zontes.dominio.bo`) o por
**prefijo de ruta** (`/zontes`, `/kiden`, `/niu`) para desarrollo local. La
marca activa se expone vía `CurrentBrand` y se aplica a las consultas con el
global scope `BrandScope`.

## Estructura

- `app/Domain/` — lógica de negocio (Brands, Customers, Loyalty, Referrals,
  Imports) organizada por dominio; Actions de una sola responsabilidad.
- `app/Http/` — controladores, middleware (`ResolveBrand`, `RequireRole`),
  Form Requests.
- `app/Livewire/Portal/` — componentes del portal cliente (saldo, historial,
  puntos por vencer, referidos).
- `config/loyalty.php` — CI, importaciones CSV, puntos, claim y referidos.

## Documentación

- [`docs/architecture.md`](docs/architecture.md)
- [`docs/csv-formats.md`](docs/csv-formats.md)
- [`docs/decisions/`](docs/decisions/)
- [`AGENTS.md`](AGENTS.md)

## Estado por fases

- **Fase 0 — Cimientos:** proyecto Laravel, Pint, Larastan, Pest, CI, estructura
  `app/Domain`, `docs/`.
- **Fase 1 — Multimarca y autenticación:** migraciones `brands`, seeder de
  marcas, `ResolveBrand`, `CurrentBrand`, `BrandScope`, temas, registro/login y
  roles/policies (base).
- **Fase 2 — Motor de puntos (prototipo entregable):** `users`/`brand_members`/
  `customer_records`/`vehicles`/`branches`, importación CSV de clientes, compras
  y mantenimientos con idempotencia y eventos pendientes, reglas y ledger de
  puntos, `points:expire`, `points:reconcile`, portal cliente y panel admin.
  **28 pruebas en verde.**
- **Pendiente:** Fases 3–6 (referidos/catálogo/canjes, reportes, endurecimiento,
  API). Ver `docs/decisions/0002-desviaciones-del-prototipo.md`.

## Licencia

MIT.
