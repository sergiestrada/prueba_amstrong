<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class VehiculoSeeder extends Seeder
{
    public function run(): void
    {
        // [placa, num_eco, marca, modelo, año, odómetro, estatus, días_desde_alta]
        $data = [
            ['ABC-101-A', 'ECO-001', 'Toyota',    'Hilux',   2020,  45000, 'activo',    730],
            ['ABC-102-A', 'ECO-002', 'Nissan',    'NP300',   2021,  12000, 'activo',    365],
            ['ABC-103-A', 'ECO-003', 'Chevrolet', 'Tornado', 2019,   8000, 'activo',    1095],
            ['ABC-104-A', 'ECO-004', 'Ford',      'Ranger',  2022,   5000, 'activo',    90],
            ['ABC-105-A', 'ECO-005', 'Honda',     'CR-V',    2020,  60000, 'activo',    1095],
            ['ABC-106-A', 'ECO-006', 'Toyota',    'Corolla', 2021,  35000, 'activo',    730],
            ['ABC-107-A', 'ECO-007', 'Nissan',    'Versa',   2022,  15000, 'activo',    365],
            ['ABC-108-A', 'ECO-008', 'Mazda',     'CX-5',    2020,  40000, 'en_taller', 900],
            ['ABC-109-A', 'ECO-009', 'Volkswagen','Jetta',   2021,  25000, 'activo',    600],
            ['ABC-110-A', 'ECO-010', 'Kia',       'Rio',     2019,  70000, 'activo',    1400],
            ['ABC-111-A', 'ECO-011', 'Hyundai',   'Tucson',  2020,  55000, 'activo',    1200],
            ['ABC-112-A', 'ECO-012', 'Toyota',    'RAV4',    2022,   3000, 'activo',    60],
            ['ABC-113-A', 'ECO-013', 'Nissan',    'Sentra',  2021,  20000, 'activo',    400],
            ['ABC-114-A', 'ECO-014', 'Chevrolet', 'Aveo',    2019,  90000, 'en_taller', 1500],
            ['ABC-115-A', 'ECO-015', 'Ford',      'F-150',   2020,  42000, 'activo',    800],
            ['ABC-116-A', 'ECO-016', 'Dodge',     'Ram',     2021,  28000, 'activo',    500],
            ['ABC-117-A', 'ECO-017', 'Volkswagen','Golf',    2020,  38000, 'activo',    950],
            ['ABC-118-A', 'ECO-018', 'Seat',      'Ibiza',   2019,  65000, 'activo',    1300],
            ['ABC-119-A', 'ECO-019', 'Peugeot',   '208',     2021,  22000, 'activo',    450],
            ['ABC-120-A', 'ECO-020', 'Renault',   'Duster',  2020,  32000, 'baja',      1000],
        ];

        foreach ($data as [$placa, $eco, $marca, $modelo, $anio, $odometro, $estatus, $dias]) {
            Vehiculo::firstOrCreate(
                ['placa' => $placa],
                [
                    'numero_economico' => $eco,
                    'marca'            => $marca,
                    'modelo'           => $modelo,
                    'anio'             => $anio,
                    'odometro_actual'  => $odometro,
                    'estatus'          => $estatus,
                    'fecha_alta'       => now()->subDays($dias)->toDateString(),
                    'notas'            => null,
                ]
            );
        }
    }
}