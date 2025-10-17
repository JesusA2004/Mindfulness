<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class UploadController extends Controller
{
    /**
     * POST /api/uploads
     *
     * Campos esperados:
     * - file: archivo (multipart/form-data)
     * - dest: (opcional) 'assets/images/Recursos' | 'assets/media/Recursos' | 'auto'
     *
     * Respuesta:
     * 200: { url, filename, mime, size, tipo, dest }
     * 422: { message, errors: {...} }
     */
    public function store(Request $request): JsonResponse
    {
        // 1) Validación básica
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                // ajusta tamaños si lo necesitas
                'max:153600', // 150 MB (en KB)
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,video/mp4,video/webm,video/ogg,video/quicktime'
            ],
            'dest' => ['nullable', 'string']
        ], [
            'file.required' => 'Debe adjuntar un archivo.',
            'file.file'     => 'El archivo no es válido.',
            'file.max'      => 'El archivo supera el tamaño máximo permitido.',
            'file.mimetypes'=> 'Solo se permiten imágenes (jpg, png, gif, webp, avif) y videos (mp4, webm, ogg, mov).'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $mime = $file->getMimeType() ?: '';
        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');

        // 2) Resolver destino
        $allowed = [
            'assets/images/Recursos',
            'assets/media/Recursos',
        ];
        $destReq = trim((string) $request->input('dest', 'auto'));

        if ($destReq === 'auto' || $destReq === '') {
            $targetDir = $isImage ? 'assets/images/Recursos' : 'assets/media/Recursos';
        } else {
            // Sanitizar y validar destino
            $destReq = str_replace(['\\', '..'], ['/', ''], $destReq);
            if (!in_array($destReq, $allowed, true)) {
                return response()->json([
                    'message' => 'Destino no permitido.',
                    'errors'  => ['dest' => ['El destino debe ser assets/images/Recursos o assets/media/Recursos.']]
                ], 422);
            }
            $targetDir = $destReq;
        }

        // 3) Asegurar que el tipo concuerde con el destino
        if ($isImage && $targetDir === 'assets/media/Recursos') {
            return response()->json([
                'message' => 'El destino indicado es para videos.',
                'errors'  => ['dest' => ['Para imágenes usa assets/images/Recursos o deja auto.']]
            ], 422);
        }
        if ($isVideo && $targetDir === 'assets/images/Recursos') {
            return response()->json([
                'message' => 'El destino indicado es para imágenes.',
                'errors'  => ['dest' => ['Para videos usa assets/media/Recursos o deja auto.']]
            ], 422);
        }

        try {
            // 4) Preparar nombre seguro
            $original   = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension  = strtolower($file->getClientOriginalExtension() ?: ($isImage ? 'png' : 'mp4'));
            $safeBase   = Str::slug(substr($original, 0, 80)); // base legible
            $filename   = $safeBase !== '' ? $safeBase : 'recurso';
            $filename  .= '-' . Str::random(8) . '.' . $extension;

            // 5) Crear carpeta si no existe (en public/)
            $publicPath = public_path($targetDir);
            File::ensureDirectoryExists($publicPath, 0755);

            // 6) Mover el archivo
            $file->move($publicPath, $filename);

            // 7) Armar URL pública
            // asset() usa APP_URL; asegúrate que APP_URL esté correcto en .env
            $relative = $targetDir . '/' . $filename;
            $url      = asset($relative);

            return response()->json([
                'url'      => $url,           // p.ej. https://tuapp.com/assets/images/Recursos/xyz.png
                'filename' => $filename,
                'mime'     => $mime,
                'size'     => $file->getSize(), // podría ser null dependiendo del SAPI
                'tipo'     => $isImage ? 'Imagen' : ($isVideo ? 'Video' : 'Desconocido'),
                'dest'     => $targetDir,
            ], 200);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No fue posible guardar el archivo.',
            ], 500);
        }
    }
}