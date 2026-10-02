<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de la cedula de identidad (CI)
    |--------------------------------------------------------------------------
    |
    | El CI se almacena cifrado (cast "encrypted") y ademas se guarda un
    | HMAC-SHA256 en "ci_hash" para poder buscarlo por igualdad sin exponer
    | el valor en claro. Ver docs/decisions/0001-ci-cifrado-y-hash.md
    |
    */

    'ci' => [
        'hash_key' => env('CI_HASH_KEY', env('APP_KEY', '')),
        'complement_pattern' => '/^[0-9]{4,10}\s*-\s*[0-9A-Z]{1,3}$/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Importacion de archivos CSV
    |--------------------------------------------------------------------------
    |
    | Los encabezados son una propuesta inicial: el mapeo es configurable para
    | poder adaptarlo al formato real que entregue el cliente sin tocar codigo.
    | Ver docs/csv-formats.md
    |
    */

    'imports' => [
        'disk' => env('IMPORT_DISK', 'local'),
        'max_kilobytes' => (int) env('IMPORT_MAX_KB', 10240),
        'chunk_size' => 200,
        'preview_rows' => 10,
        'retention_days' => (int) env('IMPORT_RETENTION_DAYS', 30),
        'delimiter_candidates' => [',', ';', "\t", '|'],
        'default_delimiter' => ',',
        'encoding' => 'UTF-8',

        /*
        | Mapeo de columnas por tipo de importacion. La clave es el encabezado
        | canonico del dominio y el valor los encabezados aceptados del CSV
        | (normalizados: minusculas, sin acentos ni separadores).
        */
        'column_map' => [
            'customers' => [
                'ci' => ['ci', 'cedula', 'cedula_identidad', 'documento', 'nit'],
                'first_name' => ['nombres', 'nombre', 'nombres_cliente'],
                'last_name' => ['apellidos', 'apellido', 'apellidos_cliente'],
                'email' => ['email', 'correo', 'correo_electronico'],
                'phone' => ['telefono', 'telefono_celular', 'celular', 'movil'],
                'registered_at' => ['fecha_registro', 'fecha', 'fecha_de_registro'],
            ],
            'purchases' => [
                'ci' => ['ci', 'cedula', 'ci_cliente', 'documento'],
                'invoice_number' => ['numero_factura', 'nro_factura', 'factura', 'numero_documento'],
                'purchased_at' => ['fecha', 'fecha_factura', 'fecha_compra'],
                'amount' => ['monto', 'total', 'monto_total', 'importe'],
                'vin' => ['vin', 'chasis', 'numero_chasis'],
                'model' => ['modelo', 'modelo_moto'],
            ],
            'maintenances' => [
                'ci' => ['ci', 'cedula', 'ci_cliente', 'documento'],
                'order_number' => ['numero_orden', 'nro_orden', 'orden', 'numero_orden_servicio'],
                'service_at' => ['fecha', 'fecha_orden', 'fecha_servicio'],
                'type' => ['tipo', 'tipo_mantenimiento', 'servicio'],
                'vin' => ['vin', 'chasis', 'numero_chasis'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Puntos
    |--------------------------------------------------------------------------
    |
    | Valores por defecto usados cuando la regla de puntos no define otra cosa.
    |
    */

    'points' => [
        'default_expiry_days' => 365,
        'expiring_notice_days' => (int) env('POINTS_EXPIRING_NOTICE_DAYS', 30),
        'expiring_banner_days' => 30,
        'refund_min_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Claim de clientes precargados (vinculo por CI)
    |--------------------------------------------------------------------------
    |
    | Para enlazar un cliente precargado con una cuenta nueva se exige ademas
    | un segundo dato (telefono o correo). Ver AGENTS.md seccion 7.
    |
    */

    'claim' => [
        'second_factor' => ['phone', 'email'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Codigos de referido y cupones
    |--------------------------------------------------------------------------
    */

    'referrals' => [
        'code_length' => 8,
    ],

];
