<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL protege contra solapes con la restricción EXCLUDE USING gist
     * creada en create_reservas_table. MySQL/MariaDB no soporta EXCLUDE, por lo
     * que aquí se replican las mismas reglas mediante disparadores BEFORE
     * INSERT/UPDATE que rechazan cualquier reserva solapada por habitación.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER reservas_sin_solape_insert
            BEFORE INSERT ON reservas
            FOR EACH ROW
            BEGIN
                IF NEW.estado <> 'cancelada' AND EXISTS (
                    SELECT 1 FROM reservas
                    WHERE habitacion_id = NEW.habitacion_id
                      AND estado <> 'cancelada'
                      AND fecha_entrada < NEW.fecha_salida
                      AND fecha_salida > NEW.fecha_entrada
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'La habitación ya tiene una reserva que se solapa con el rango de fechas solicitado.';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER reservas_sin_solape_update
            BEFORE UPDATE ON reservas
            FOR EACH ROW
            BEGIN
                IF NEW.estado <> 'cancelada' AND EXISTS (
                    SELECT 1 FROM reservas
                    WHERE habitacion_id = NEW.habitacion_id
                      AND estado <> 'cancelada'
                      AND id <> NEW.id
                      AND fecha_entrada < NEW.fecha_salida
                      AND fecha_salida > NEW.fecha_entrada
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'La habitación ya tiene una reserva que se solapa con el rango de fechas solicitado.';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_update');
    }
};
