# 0002 — Desviaciones del prototipo (Fases 0–2)

Este documento registra las decisiones tomadas durante las Fases 0–2 que se
apartan del stack o de lo previsto en `AGENTS.md`, y su justificación. El
objetivo es no reescribir código al incorporar el stack definitivo.

## Paquetes

- **Filament no instalado (todavía).** El panel admin de esta fase se
  implementó como MVC + Blade + Policies. Motivo: acotar el prototipo
  entregable de Fase 2. Los `Resources` de Filament se agregarán en Fase 4
  reutilizando las mismas Actions y Policies; los controladores actuales no
  contienen lógica de negocio.
- **`spatie/laravel-permission` no instalado.** Roles mediante un enum
  `App\Domain\Customers\Enums\UserRole` (`customer`, `branch_staff`,
  `brand_admin`, `super_admin`) y una tabla pivote `brand_admin` para las marcas
  administradas. `User::isSuperAdmin()`, `isAdmin()`, `administersBrand()` y
  `administeredBrands()`. Se migrará a spatie si se requieren permisos
  granulares.
- **`maatwebsite/excel` no instalado.** La importación CSV usa un lector nativo
  (`App\Domain\Imports\Support\CsvReader` + `ColumnMapper`) con autodetección de
  separador. Cubre CSV; XLSX queda pendiente.
- **`spatie/laravel-activitylog` no instalado.** La auditoría de esta fase se
  apoya en columnas `created_by`/`validated_by` y en el historial inmutable del
  ledger. Se incorporará activitylog en Fase 5.
- **Livewire sí**, como exige `AGENTS.md` para el portal cliente.

## Base de datos

- **MariaDB 10.4 (XAMPP) en desarrollo**, no MySQL 8. Compatible con las
  migraciones (row format DYNAMIC, índices largos) y las pruebas en SQLite
  `:memory:`.
- Las columnas `ci` son `string(255)` porque el valor cifrado (base64) supera
  los 32 caracteres.

## Front-end

- **Tailwind CSS por CDN** en el prototipo. El `npm` del entorno no es
  ejecutable (ExecutionPolicy), por lo que el build de Vite queda pendiente.
  Las clases usadas son equivalentes a las de un build normal.

## Otros

- La cola y la caché usan el driver `database` en desarrollo (`QUEUE_CONNECTION`
  / `CACHE_STORE`); en producción se usarán Redis + Horizon.
- Los precios y montos se manejan como enteros/decimales, nunca `float`.

## Pendientes para fases siguientes

1. Filament (Fase 4) y migración de roles a spatie si aplica.
2. Importación XLSX (`maatwebsite/excel`) y mapeo de columnas configurable desde
   el panel.
3. `activitylog`, notificaciones de vencimiento, `redemptions:expire` y
   respaldos (Fase 5).
4. Internacionalización: mover los textos de interfaz a `lang/es`. En esta fase
   las vistas usan español directo.
