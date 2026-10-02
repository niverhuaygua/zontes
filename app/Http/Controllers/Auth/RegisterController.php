<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\Actions\RegisterCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function show(RegisterRequest $request): View
    {
        return view('auth.register', [
            'brand' => $this->currentBrand->require(),
            'ref' => $request->query('ref'),
        ]);
    }

    public function store(RegisterRequest $request, RegisterCustomer $register): RedirectResponse
    {
        $brand = $this->currentBrand->require();

        $result = $register->handle($brand, [
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
            'password' => (string) $request->string('password'),
            'ci' => (string) $request->string('ci'),
            'phone' => $request->input('phone') !== null ? (string) $request->string('phone') : null,
            'birth_date' => $request->input('birth_date') !== null ? (string) $request->string('birth_date') : null,
        ], $request->input('ref'));

        Auth::login($result['user']);
        $request->session()->regenerate();

        $message = 'Tu cuenta fue creada. Ya puedes acumular puntos con tus compras.';

        if ($result['linked']) {
            $message = $result['credited'] > 0
                ? "Encontramos tus compras anteriores: acreditamos puntos de {$result['credited']} movimientos."
                : 'Encontramos tu historial de cliente y lo vinculamos a tu cuenta.';
        } elseif ($result['record'] !== null) {
            $message = 'Recibimos tu registro. Estamos verificando tus datos antes de acreditar puntos.';
        }

        return redirect()->route('portal.home')->with('status', $message);
    }
}
