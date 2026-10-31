<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ClubBrandingService
{
    public function update(Club $club, string $palette, ?UploadedFile $logo, bool $remove): void
    {
        $newPath = null;
        $oldPath = null;
        try {
            DB::transaction(function () use ($club, $palette, $logo, $remove, &$newPath, &$oldPath): void {
                $locked = Club::query()->lockForUpdate()->findOrFail($club->id);
                $oldPath = $locked->logo_path;
                if ($logo) {
                    $image = imagecreatefromstring($logo->get());
                    if ($image === false) {
                        throw new RuntimeException('No se pudo procesar el logo.');
                    }
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    ob_start();
                    try {
                        if (! imagepng($image)) {
                            throw new RuntimeException('No se pudo procesar el logo.');
                        }
                        $png = ob_get_contents();
                    } finally {
                        ob_end_clean();
                        imagedestroy($image);
                    }
                    $newPath = 'club-logos/'.$locked->id.'/'.Str::uuid().'.png';
                    if (! Storage::disk('local')->put($newPath, $png)) {
                        throw new RuntimeException('No se pudo guardar el logo.');
                    }
                }
                $locked->forceFill([
                    'brand_palette' => $palette,
                    'logo_path' => $newPath ?? ($remove ? null : $oldPath),
                ])->save();
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
        if (($newPath || $remove) && $oldPath && str_starts_with($oldPath, 'club-logos/'.$club->id.'/')) {
            Storage::disk('local')->delete($oldPath);
        }
    }
}
