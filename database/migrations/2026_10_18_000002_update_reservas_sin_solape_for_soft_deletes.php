<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Actualiza la protección de solape de reservas para Soft Deletes.
 *
 * Los triggers (MySQL/MariaDB) y la restricción EXCLUDE (PostgreSQL)
 * ahora ignoran las reservas dadas de baja (deleted_at IS NULL), de modo
 * que una reserva eliminada lógicamente deja de bloquear el rango de
 * fechas de la habitación.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->crearTriggersMysql();
        }

        if ($driver === 'pgsql') {
            $this->crearRestriccionPostgres();
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_update');

            // Restaura los triggers originales (sin filtro de deleted_at).
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER reservas_sin_solape_insert BEFORE INSERT ON reservas FOR EACH ROW
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
                CREATE TRIGGER reservas_sin_solape_update BEFORE UPDATE ON reservas FOR EACH ROW
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

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE reservas DROP CONSTRAINT IF EXISTS reservas_sin_solape');
            DB::statement(<<<'SQL'
                ALTER TABLE reservas ADD CONSTRAINT reservas_sin_solape
                EXCLUDE USING gist (
                    habitacion_id WITH =,
                    daterange(fecha_entrada, fecha_salida, '[)') WITH &&
                ) WHERE (estado <> 'cancelada')
            SQL);
        }
    }

    private function crearTriggersMysql(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS reservas_sin_solape_update');

        // INSERT: solo valida solape si la reserva entrante no está dada de baja.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER reservas_sin_solape_insert BEFORE INSERT ON reservas FOR EACH ROW
            BEGIN
                IF NEW.deleted_at IS NULL AND NEW.estado <> 'cancelada' AND EXISTS (
                    SELECT 1 FROM reservas
                    WHERE habitacion_id = NEW.habitacion_id
                      AND estado <> 'cancelada'
                      AND deleted_at IS NULL
                      AND fecha_entrada < NEW.fecha_salida
                      AND fecha_salida > NEW.fecha_entrada
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'La habitación ya tiene una reserva que se solapa con el rango de fechas solicitado.';
                END IF;
            END
        SQL);

        // UPDATE: ignora la propia fila, las canceladas y las dadas de baja.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER reservas_sin_solape_update BEFORE UPDATE ON reservas FOR EACH ROW
            BEGIN
                IF NEW.deleted_at IS NULL AND NEW.estado <> 'cancelada' AND EXISTS (
                    SELECT 1 FROM reservas
                    WHERE habitacion_id = NEW.habitacion_id
                      AND estado <> 'cancelada'
                      AND deleted_at IS NULL
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

    private function crearRestriccionPostgres(): void
    {
        DB::statement('ALTER TABLE reservas DROP CONSTRAINT IF EXISTS reservas_sin_solape');
        DB::statement(<<<'SQL'
            ALTER TABLE reservas ADD CONSTRAINT reservas_sin_solape
            EXCLUDE USING gist (
                habitacion_id WITH =,
                daterange(fecha_entrada, fecha_salida, '[)') WITH &&
            ) WHERE (estado <> 'cancelada' AND deleted_at IS NULL)
        SQL);
    }
};
