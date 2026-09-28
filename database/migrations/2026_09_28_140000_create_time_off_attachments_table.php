<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencia documental de las solicitudes de tiempo libre.
 *
 * La cédula, la incapacidad y el certificado medico son documentos personales
 * y de salud: NO van a public/. El filesystem por defecto del proyecto es
 * 'local' (storage/app, fuera del web root) y los archivos se sirven por un
 * endpoint autenticado, no por URL publica.
 *
 * Los archivos se guardan en una carpeta por mes (YYYY/M) para no dejar
 * cientos de entradas en un mismo directorio, que en Windows se vuelve lento
 * de listar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_off_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_off_request_id')
                ->constrained('time_off_requests')
                ->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index('time_off_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off_attachments');
    }
};
