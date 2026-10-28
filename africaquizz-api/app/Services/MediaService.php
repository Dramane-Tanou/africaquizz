<?php
namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use kornrunner\Blurhash\Blurhash;

class MediaService
{
    const MAX_SIZE = 5 * 1024 * 1024; // 5 MB
    const MAX_WIDTH = 1920;

    public function upload(UploadedFile $file, ?string $adminId = null, array $meta = []): Media
    {
        // Validation
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed)) {
            throw new \Exception("Type non autorisé : {$file->getMimeType()}. Autorisé : JPEG, PNG, WebP.");
        }
        if ($file->getSize() > self::MAX_SIZE) {
            throw new \Exception("Fichier trop lourd (max 5 Mo).");
        }

        $contents = file_get_contents($file->getRealPath());
        $hash = hash('sha256', $contents);

        // Doublon ?
        $existing = Media::where('hash', $hash)->first();
        if ($existing) return $existing;

        // Traiter avec Intervention
        $manager = new ImageManager(new Driver());
        $image = $manager->read($contents);

        // Resize si trop grand
        if ($image->width() > self::MAX_WIDTH) {
            $image->scale(width: self::MAX_WIDTH);
        }

        // Encoder en WebP optimisé
        $webp = $image->toWebp(80);

        // Générer le blurhash (32x32 pour calcul rapide)
        $tiny = $manager->read($contents)->scale(width: 32);
        $pixels = [];
        $w = $tiny->width();
        $h = $tiny->height();
        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $rgba = $tiny->pickColor($x, $y)->toArray();
                $row[] = [$rgba[0], $rgba[1], $rgba[2]];
            }
            $pixels[] = $row;
        }
        $blurhash = Blurhash::encode($pixels, 4, 4);

        // Stocker
        $filename = Str::uuid() . '.webp';
        $path = 'media/' . date('Y/m') . '/' . $filename;
        Storage::disk('public')->put($path, (string) $webp);

        // Créer l'enregistrement
        return Media::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/webp',
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => strlen((string) $webp),
            'hash' => $hash,
            'blurhash' => $blurhash,
            'license' => $meta['license'] ?? null,
            'author' => $meta['author'] ?? null,
            'source_url' => $meta['source_url'] ?? null,
            'attribution_text' => $meta['attribution_text'] ?? null,
            'alt_text_fr' => $meta['alt_text_fr'] ?? null,
            'alt_text_en' => $meta['alt_text_en'] ?? null,
            'uploaded_by_admin_id' => $adminId,
        ]);
    }
}