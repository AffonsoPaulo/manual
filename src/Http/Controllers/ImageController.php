<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Http\Controllers;

use Illuminate\Http\Request;
use ServeraCloud\Manual\Services\ManualPathResolver;
use Symfony\Component\HttpFoundation\Response;

final class ImageController {
    public function __construct(
        private readonly ManualPathResolver $pathResolver,
    ) {
    }

    public function __invoke(Request $request, string $path): Response {
        if (! (bool) config('manual.images.enabled', true)) {
            abort(404);
        }

        if (str_contains($path, "\0")) {
            abort(404);
        }

        $segments = explode('/', str_replace('\\', '/', $path));

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                abort(404);
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExtensions = (array) config('manual.images.extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico']);

        if (! in_array($extension, $allowedExtensions, strict: true)) {
            abort(404);
        }

        $sourcePath = $this->pathResolver->sourcePath();
        $absolutePath = rtrim($sourcePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        $imagesPath = $this->pathResolver->imagesPath();
        $realImagesPath = realpath($imagesPath);
        $realAbsolutePath = realpath($absolutePath);

        if ($realImagesPath === false || $realAbsolutePath === false) {
            abort(404);
        }

        $normalizedBase = rtrim($realImagesPath, DIRECTORY_SEPARATOR);

        if ($realAbsolutePath !== $normalizedBase
            && ! str_starts_with($realAbsolutePath, $normalizedBase . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = ($finfo !== false ? finfo_file($finfo, $absolutePath) : false) ?: 'application/octet-stream';

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! str_starts_with((string) $mimeType, 'image/')) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
