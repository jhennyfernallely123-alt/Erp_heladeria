<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Motivos medicos en las solicitudes de tiempo libre.
 *
 * sick_leave es un permiso por salud de un dia e incapacitad es la incapacidad
 * medica con su incapacidad medica que respalda. Son distintos a proposito: la
 * incapacidad es un documento que se archiva y puede dar lugar a prestacion
 * economica, asi que el administrador necesita poder distinguirlas.
 *
 * Ninguna de las dos descuenta vacaciones: eso lo hace solo el motivo
 * 'vacation' en EmployeeService.
 *
 * Se hace con SQL crudo porque cambiar un enum en MySQL no lo soporta
 * ->change() de Laravel sin doctrine/dbal, y ese paquete no esta instalado en
 * este proyecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE time_off_requests MODIFY `type` ENUM('.
            "'vacation','family_day','birthday','unpaid','sick_leave','incapacity'".
            ") NOT NULL DEFAULT 'vacation'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "UPDATE time_off_requests SET type = 'unpaid' ".
            "WHERE type IN ('sick_leave','incapacity')"
        );

        DB::statement(
            'ALTER TABLE time_off_requests MODIFY `type` ENUM('.
            "'vacation','family_day','birthday','unpaid'".
            ") NOT NULL DEFAULT 'vacation'"
        );
    }
};
