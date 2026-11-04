<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function __invoke(string $document): View
    {
        abort_unless(in_array($document, ['terms', 'privacy'], true), 404);

        return view('legal.policy', ['title' => $document === 'terms' ? 'Condiciones de uso' : 'Política de privacidad',
            'sections' => config('billing.'.$document), 'version' => config('billing.version')]);
    }
}
