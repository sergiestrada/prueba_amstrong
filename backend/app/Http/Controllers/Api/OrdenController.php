<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orden\StoreOrdenRequest;
use App\Http\Requests\Orden\UpdateOrdenRequest;
use App\Http\Requests\Orden\UpdateOrdenStatusRequest;
use App\Http\Resources\OrdenResource;
use App\Models\Orden;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Orden::class);

        $query = Orden::with(['vehiculo:id,placa,numero_economico,marca,modelo,anio', 'creadoPor:id,nombre,apellido']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        foreach (['estatus', 'tipo'] as $f) {
            if ($request->filled($f)) $query->where($f, $request->input($f));
        }
        if ($request->filled('vehiculo_id'))  $query->where('vehiculo_id', $request->integer('vehiculo_id'));
        if ($request->filled('fecha_desde'))  $query->whereDate('fecha_programada', '>=', $request->input('fecha_desde'));
        if ($request->filled('fecha_hasta'))  $query->whereDate('fecha_programada', '<=', $request->input('fecha_hasta'));

        $query->orderBy($request->input('sort_by', 'created_at'),
                        $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc');

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return OrdenResource::collection($query->paginate($perPage));
    }

    public function store(StoreOrdenRequest $request): JsonResponse
    {
        // RN-09: transacción
        $orden = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['creado_por_id'] = $request->user()->id;
            $data['estatus']       = Orden::ESTATUS_ABIERTA;

            return Orden::create($data);
        });

        $orden->load(['vehiculo', 'creadoPor']);

        return (new OrdenResource($orden))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Orden $orden): OrdenResource
    {
        $this->authorize('view', $orden);
        $orden->load(['vehiculo', 'creadoPor']);
        return new OrdenResource($orden);
    }

    public function update(UpdateOrdenRequest $request, Orden $orden): OrdenResource
    {
        $orden->fill($request->validated())->save();
        $orden->load(['vehiculo', 'creadoPor']);
        return new OrdenResource($orden);
    }

    /**
     * RN-03 / RN-04 / RN-05 / RN-09: cambio de estatus transaccional
     * con actualización del vehículo relacionado.
     */
    public function updateStatus(UpdateOrdenStatusRequest $request, Orden $orden): OrdenResource
    {
        $data      = $request->validated();
        $newStatus = $data['estatus'];

        // RN-08: solo admin puede cancelar
        if ($newStatus === Orden::ESTATUS_CANCELADA) {
            $this->authorize('cancel', $orden);
        }

        // RN-05: al cerrar, costo y fecha son obligatorios (ya validado en request)
        DB::transaction(function () use ($orden, $newStatus, $data) {
            $orden->estatus = $newStatus;

            if ($newStatus === Orden::ESTATUS_CERRADA) {
                $orden->costo        = $data['costo'] ?? null;
                $orden->fecha_cierre = $data['fecha_cierre'] ?? now()->toDateString();

                // RN-04: actualizar odómetro del vehículo al cerrar
                $vehiculo = Vehiculo::lockForUpdate()->find($orden->vehiculo_id);
                if ($vehiculo) {
                    $vehiculo->odometro_actual = $orden->odometro_ingreso;
                    // RN-03: al cerrar → activo
                    $vehiculo->estatus = Vehiculo::ESTATUS_ACTIVO;
                    $vehiculo->save();
                }
            }

            if ($newStatus === Orden::ESTATUS_EN_PROCESO) {
                // RN-03: al pasar a en_proceso → vehículo a en_taller
                Vehiculo::where('id', $orden->vehiculo_id)
                    ->update(['estatus' => Vehiculo::ESTATUS_EN_TALLER]);
            }

            if ($newStatus === Orden::ESTATUS_CANCELADA) {
                // RN-03: al cancelar → vehículo a activo
                Vehiculo::where('id', $orden->vehiculo_id)
                    ->where('estatus', Vehiculo::ESTATUS_EN_TALLER)
                    ->update(['estatus' => Vehiculo::ESTATUS_ACTIVO]);
            }

            if (! empty($data['notas'])) {
                $prefijo = '[' . now()->format('Y-m-d H:i') . ' · ' . $newStatus . '] ';
                $orden->notas = ($orden->notas ? $orden->notas . "\n" : '') . $prefijo . trim($data['notas']);
            }

            $orden->save();
        });

        $orden->refresh()->load(['vehiculo', 'creadoPor']);
        return new OrdenResource($orden);
    }
}