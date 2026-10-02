<?php

declare(strict_types=1);

it('responde 200 en la pagina de inicio', function (): void {
    $this->get('/')->assertOk();
});
