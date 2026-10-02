# Formatos de importación CSV

Los encabezados de abajo son una **propuesta inicial**. El mapeo real es
configurable en `config/loyalty.php` (`imports.column_map`) para adaptarlo al
formato que finalmente entregue el cliente **sin tocar código**.

## Reglas generales

- Codificación: **UTF-8**.
- Separador: autodetectado entre `,` `;` `TAB` `|` (ver
  `imports.delimiter_candidates`). Se puede forzar por archivo.
- La primera fila contiene los encabezados.
- La marca se elige al subir el archivo (o por columna `marca` validada contra
  `brands.slug`).
- Los encabezados se normalizan antes de mapear: minúsculas, sin acentos y sin
  separadores (`Número Factura` → `numero_factura`).
- Límite de tamaño: `imports.max_kilobytes` (10 MB por defecto).
- Se rechazan filas con fecha futura o monto ≤ 0.
- Cada fila es independiente: una fila inválida no aborta el archivo; el error
  se guarda en `csv_import_errors` y el reporte final permite descargar un CSV
  de errores.
- Los archivos se guardan en disco **privado** (`imports.disk`, por defecto
  `local`) y se retienen `imports.retention_days` (30 días).

## `customers`

Clientes precargados (aún sin cuenta). Se hace *upsert* por `(brand_id, ci)`
en `customer_records`.

| Columna canónica | Encabezados aceptados | Obligatorio |
|---|---|---|
| `ci` | `ci`, `cedula`, `cedula_identidad`, `documento`, `nit` | Sí |
| `first_name` | `nombres`, `nombre`, `nombres_cliente` | Sí |
| `last_name` | `apellidos`, `apellido`, `apellidos_cliente` | Sí |
| `email` | `email`, `correo`, `correo_electronico` | No |
| `phone` | `telefono`, `telefono_celular`, `celular`, `movil` | No |
| `registered_at` | `fecha_registro`, `fecha`, `fecha_de_registro` | No |

Ejemplo:

```csv
ci,nombres,apellidos,email,telefono,fecha_registro
1234567,Juan,Pérez,juan@example.test,70011223,2026-01-15
7654321-1A,María,Gómez,,70099887,2026-02-01
```

## `purchases`

Compras. **Idempotencia** por `(brand_id, numero_factura)`: reimportar el mismo
archivo no duplica puntos.

| Columna canónica | Encabezados aceptados | Obligatorio |
|---|---|---|
| `ci` | `ci`, `cedula`, `ci_cliente`, `documento` | Sí |
| `invoice_number` | `numero_factura`, `nro_factura`, `factura`, `numero_documento` | Sí |
| `purchased_at` | `fecha`, `fecha_factura`, `fecha_compra` | Sí |
| `amount` | `monto`, `total`, `monto_total`, `importe` | Sí |
| `vin` | `vin`, `chasis`, `numero_chasis` | No |
| `model` | `modelo`, `modelo_moto` | No |

Si el cliente aún **no tiene cuenta**, la compra se guarda como evento pendiente
(`pending_events`) y los puntos se acreditan al reclamar la cuenta por CI
(conservando la fecha original para el vencimiento del lote).

Ejemplo:

```csv
ci,numero_factura,fecha,monto,vin,modelo
1234567,F-000123,2026-03-10,18500.00,LC0A1B2C3D4E5F6G,Zontes ZT350
```

## `maintenances`

Mantenimientos. Idempotencia por `(brand_id, numero_orden)`. Mismo
comportamiento de eventos pendientes que `purchases`.

| Columna canónica | Encabezados aceptados | Obligatorio |
|---|---|---|
| `ci` | `ci`, `cedula`, `ci_cliente`, `documento` | Sí |
| `order_number` | `numero_orden`, `nro_orden`, `orden`, `numero_orden_servicio` | Sí |
| `service_at` | `fecha`, `fecha_orden`, `fecha_servicio` | Sí |
| `type` | `tipo`, `tipo_mantenimiento`, `servicio` | Sí |
| `vin` | `vin`, `chasis`, `numero_chasis` | No |

Ejemplo:

```csv
ci,numero_orden,fecha,tipo,vin
1234567,M-000045,2026-04-02,preventivo,LC0A1B2C3D4E5F6G
```

## Normalización de CI

`trim`, mayúsculas, sin puntos ni espacios; se conserva el complemento
(p. ej. `7654321-1A`). Para búsqueda se almacena además un HMAC-SHA256
(`ci_hash`); ver `docs/decisions/0001-ci-cifrado-y-hash.md`.

## Carga por consola

```bash
php artisan imports:process archivo.csv --type=purchases --brand=zontes
```

Tipos válidos: `customers`, `purchases`, `maintenances`.
