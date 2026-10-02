<?php

declare(strict_types=1);

namespace App\Domain\Imports\Exceptions;

use RuntimeException;

/**
 * Error controlado de una fila del CSV: no aborta el archivo, se guarda en
 * csv_import_errors.
 */
class ImportRowException extends RuntimeException {}
