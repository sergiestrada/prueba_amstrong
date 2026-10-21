<?php

namespace Database\Seeders;

use App\Models\Orden;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdenSeeder extends Seeder
{
    public function run(): void
    {
        if (Orden::count() > 0) {
            $this->command->info('Ya existen órdenes; se omite OrdenSeeder.');
            return;
        }

        $admin      = Usuario::where('email', 'admin@test.com')->first();
        $capturista = Usuario::where('email', 'capturista@test.com')->first();

        if (! $admin || ! $capturista) {
            $this->command->error('Faltan usuarios. Corre UsuarioSeeder primero.');
            return;
        }

        $v = Vehiculo::orderBy('id')->pluck('id')->all();

        $ordenes = [
            [3,  'preventivo', 'cerrada',    'Servicio 5,000 km - cambio de aceite',       5000,  'media', 60,  60,  1500.00, 1500.00, null],
            [4,  'preventivo', 'cerrada',    'Revisión de frenos y balatas',               3000,  'media', 30,  30,   800.00,  800.00, null],
            [8,  'preventivo', 'cerrada',    'Servicio mayor 20,000 km',                  20000, 'alta',  60,  45,  3500.00, 3500.00, 0],
            [9,  'preventivo', 'cerrada',    'Cambio de aceite y filtros',                22000, 'baja',  40,  30,  1200.00, 1200.00, null],
            [11, 'preventivo', 'cerrada',    'Servicio 30,000 km - afinación',            30000, 'alta',  270, 250, 4000.00, 4000.00, 0],
            [17, 'preventivo', 'cerrada',    'Rotación de llantas y alineación',          35000, 'baja',  25,  20,  1000.00, 1000.00, null],
            [19, 'preventivo', 'cerrada',    'Cambio de aceite',                          20000, 'baja',  20,  15,   900.00,  900.00, null],
            [10, 'correctivo', 'cerrada',    'Reparación de sistema eléctrico',            68000, 'alta',  90,  85,  5500.00, 5500.00, 0],
            [12, 'correctivo', 'cerrada',    'Cambio de batería',                           2800, 'baja',  45,  45,  2500.00, 2500.00, null],
            [14, 'correctivo', 'cerrada',    'Reparación de transmisión',                  88000, 'alta',  120, 110, 8000.00, 8000.00, 0],
            [1,  'preventivo', 'abierta',    'Servicio programado 15,000 km',             12000, 'media', -5,  null,  2000.00, null, 1],
            [2,  'correctivo', 'abierta',    'Ruido en suspensión delantera',               8000, 'alta',  -2,  null,  1500.00, null, 1],
            [6,  'preventivo', 'abierta',    'Afinación mayor',                            35000, 'media', -10, null,  2500.00, null, 1],
            [7,  'correctivo', 'abierta',    'Fuga de aceite en cárter',                   15000, 'alta',  -3,  null,  1800.00, null, null],
            [16, 'preventivo', 'abierta',    'Cambio de aceite preventivo',                28000, 'baja',  -7,  null,  1000.00, null, null],
            [5,  'preventivo', 'en_proceso', 'Servicio 60,000 km',                         58000, 'alta',   -1,  null,  4500.00, null, 1],
            [13, 'correctivo', 'en_proceso', 'Reparación de aire acondicionado',            20000, 'media',  -5,  null,  3000.00, null, 0],
            [18, 'preventivo', 'en_proceso', 'Alineación y balanceo',                       65000, 'baja',   -2,  null,   700.00, null, 1],
            [0,  'correctivo', 'cancelada',  'Cliente canceló el servicio',                45000, 'baja',  -15, null,  null,    null, null],
            [15, 'preventivo', 'cancelada',  'Reagendado por disponibilidad',              42000, 'media', -20, null,  null,    null, null],
        ];

        foreach ($ordenes as $row) {
            [$vIdx, $tipo, $status, $descripcion, $odoIng, $prioridad,
             $diasProg, $diasCierre, $costoEst, $costoReal, $asignadoKey] = $row;

            $vehiculoId = $v[$vIdx] ?? null;
            if (! $vehiculoId) {
                continue;
            }

            $asignadoId = $asignadoKey === 0 ? $admin->id
                        : ($asignadoKey === 1 ? $capturista->id : null);

            DB::transaction(function () use (
                $admin, $vehiculoId, $tipo, $status, $descripcion,
                $odoIng, $prioridad, $diasProg, $diasCierre,
                $costoEst, $costoReal, $asignadoId
            ) {
                $payload = [
                    'vehiculo_id'      => $vehiculoId,
                    'creado_por_id'    => $admin->id,
                    'asignado_a_id'    => $asignadoId,
                    'tipo'             => $tipo,
                    'status'           => $status,
                    'prioridad'        => $prioridad,
                    'descripcion'      => $descripcion,
                    'odometro_ingreso' => $odoIng,
                    'fecha_programada' => now()->subDays($diasProg)->toDateString(),
                    'costo'            => $costoReal,
                    'costo_estimado'   => $costoEst,
                    'costo_real'       => $costoReal,
                    'notas'            => null,
                ];

                if (in_array($status, ['en_proceso', 'cerrada'], true)) {
                    $payload['fecha_inicio'] = now()
                        ->subDays($diasCierre ?? 5)
                        ->toDateTimeString();
                }

                if ($status === 'cerrada') {
                    $payload['fecha_cierre'] = now()
                        ->subDays($diasCierre)
                        ->toDateString();
                }

                Orden::create($payload);
            }, 5);
        }

        $this->command->info('Órdenes creadas: ' . Orden::count());
    }
}