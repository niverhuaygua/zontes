<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'brands' => Brand::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }
}
