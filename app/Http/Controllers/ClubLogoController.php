<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClubLogoController extends Controller
{
    public function __invoke(Club $club, string $version): StreamedResponse
    {
        $path = 'club-logos/'.$club->id.'/'.$version.'.png';
        abort_unless($club->logo_path === $path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'logo.png', [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
