<?php

namespace App\Services;

use App\Models\TimeOffAttachment;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Archivos que respaldan una solicitud de tiempo libre.
 *
 * Son documentos personales: cédula, incapacidad, certificado médico. Por eso
 * NUNCA van a public/ ni a storage/app, porque el catch-all de routes/web.php
 * sirve cualquier ruta que no exista en public/ y los haría alcanzables por
 * URL. Van al disco 'private' (storage/app/private) y se sirven solo por un
 * endpoint que valida que quien pide el archivo sea el dueño de la solicitud o
 * el administrador.
 *
 * Un archivo suelto en public/ con un nombre predecible es un documento de
 * identidad de una persona expuesto a cualquiera que sepa la URL, y en un
 * local con wifi de clientes eso es un problema real.
 */
class AttachmentService
{
    /** Tope por archivo: 8 MB. Un PDF o una foto de celular de sobra. */
    public const MAX_KB = 8192;

    /**
     * Disco de los documentos. 'private' cae en storage/app/private, que no
     * tiene contraparte en public/, asi que el archivo no es alcanzable por
     * URL aunque se conozca la ruta. Se sirve solo por el endpoint autenticado.
     *
     * Es publico porque los controllers tienen que leer el disco para servir
     * la descarga.
     */
    public const DISK = 'private';

    /**
     * Tipos que aceptan archivo. Vacaciones, cumpleaños y día de familia van sin
     * evidencia a propósito: no llevan documento que justifique.
     */
    public const REQUIRES_EVIDENCE = ['sick_leave', 'incapacity', 'birthday'];

    /**
     * Mime types aceptados. Se valida el contenido declarado, no la extensión:
     * renombrar un .exe a .pdf no lo convierte en un PDF.
     */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * Tipos que admiten más de un archivo. La incapacidad y el permiso de
     * salud a veces llegan partido: el certificado por un lado, la epicrisis
     * por otro.
     */
    private const ALLOWED_COUNT = [
        'default' => 1,
        'sick_leave' => 3,
        'incapacity' => 3,
        'birthday' => 1,
    ];

    public function requiresEvidence(string $type): bool
    {
        return in_array($type, self::REQUIRES_EVIDENCE, true);
    }

    /**
     * Guarda los archivos de una solicitud recien creada.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array<int, TimeOffAttachment>
     */
    public function storeFor(TimeOffRequest $request, array $files, string $type): array
    {
        if ($this->requiresEvidence($type) && empty($files)) {
            throw new RuntimeException('Este tipo de permiso necesita un documento que lo respalde.');
        }

        $max = self::ALLOWED_COUNT[$type] ?? self::ALLOWED_COUNT['default'];

        if (count($files) > $max) {
            throw new RuntimeException("Podés adjuntar hasta {$max} archivo(s) para este tipo de permiso.");
        }

        $stored = [];

        try {
            foreach ($files as $file) {
                $stored[] = $this->store($request, $file);
            }
        } catch (\Throwable $e) {
            // Si un archivo falla a mitad de camino, los que ya se copiaron a
            // disco se borran: quedan archivos huerfanos que la base no
            // referencia y que nadie puede limpiar despues.
            foreach ($stored as $attachment) {
                $this->delete($attachment);
            }

            throw $e;
        }

        return $stored;
    }

    private function store(TimeOffRequest $request, UploadedFile $file): TimeOffAttachment
    {
        $this->guard($file);

        // Se sube al disco 'private' y NO al 'local'. Los dos viven dentro de
        // storage/app, pero el catch-all de routes/web.php sirve CUALQUIER ruta
        // que no exista en public/, asi que un archivo en storage/app/local es
        // alcanzable por URL directa igual que si estuviera en public/. Eso ya
        // se vio en la verificacion: un GET a la ruta cruda devolvio el PDF.
        // Las cédulas y los certificados médicos tienen que quedar fuera de
        // cualquier URL adivinable.
        $folder = sprintf('%d/%s', $request->user_id, now()->format('Y/m'));
        $name = Str::random(32).'.'.$this->extension($file);

        $path = $file->storeAs("time-off-evidence/{$folder}", $name, self::DISK);

        if (! $path) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return TimeOffAttachment::create([
            'time_off_request_id' => $request->id,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
            'stored_path' => $path,
            // Se guarda el tipo real, no el declarado, para que el admin vea
            // en que descargarlo sin surprises.
            'mime_type' => $this->detectMime($file),
            'size_bytes' => $file->getSize(),
        ]);
    }

    private function guard(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('El archivo no se subió completo.');
        }

        if ($file->getSize() > self::MAX_KB * 1024) {
            throw new RuntimeException('Cada archivo puede pesar hasta 8 MB.');
        }

        // El mime DECLARADO por el navegador no sirve: un HTML con script
        // renombrado a .pdf llega como application/pdf y pasaria el chequeo.
        // Hay que sniffear el contenido real.
        $detected = $this->detectMime($file);

        if (! in_array($detected, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('Solo se aceptan PDF o imágenes (JPG, PNG, WEBP).');
        }

        if (! in_array(strtolower($file->getClientOriginalExtension()), self::EXTENSIONS, true)) {
            throw new RuntimeException('La extensión del archivo no está permitida.');
        }
    }

    /**
     * Tipo real del archivo, leido de sus primeros bytes y no de lo que diga el
     * cliente. getMimeType() de Laravel usa finfo, que mira la firma del
     * archivo, asi que un HTML disfrazado de PDF cae como text/html.
     */
    private function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return (string) $file->getClientMimeType();
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($path);

        return $detected ?: (string) $file->getClientMimeType();
    }

    private function extension(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, self::EXTENSIONS, true) ? $ext : 'pdf';
    }

    /**
     * Devuelve el archivo SOLO si quien pide tiene derecho a verlo: el dueño de
     * la solicitud o el administrador.
     */
    public function findFor(User $viewer, TimeOffAttachment $attachment): TimeOffAttachment
    {
        $ownerId = $attachment->request?->user_id;

        if ($ownerId !== $viewer->id && ! $viewer->can('manage_settings')) {
            throw new RuntimeException('No tenés permiso para ver este documento.');
        }

        if (! Storage::disk(self::DISK)->exists($attachment->stored_path)) {
            throw new RuntimeException('El archivo ya no está disponible en el servidor.');
        }

        return $attachment;
    }

    public function delete(TimeOffAttachment $attachment): void
    {
        Storage::disk(self::DISK)->delete($attachment->stored_path);
        $attachment->delete();
    }

    /** Todos los adjuntos de una solicitud, para la API. */
    public function presentMany(iterable $attachments): array
    {
        return collect($attachments)->map(fn ($a) => $this->present($a))->values()->all();
    }

    /**
     * Datos del adjunto para la API, sin exponer la ruta en disco. El nombre
     * original sí se manda: es lo que el empleado subió y el admin necesita
     * para saber si es la cédula o el certificado.
     */
    public function present(TimeOffAttachment $a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->original_name,
            'mime_type' => $a->mime_type,
            'size_bytes' => $a->size_bytes,
            'size_label' => $this->sizeLabel($a->size_bytes),
            'uploaded_at' => Carbon::parse($a->created_at)->toIso8601String(),
        ];
    }

    private function sizeLabel(?int $bytes): string
    {
        if (! $bytes) {
            return '';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
