<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orden\StoreOrdenRequest;
use App\Http\Requests\Orden\UpdateOrdenRequest;
use App\Http\Requests\Orden\UpdateOrdenStatusRequest;
use App\Http\Resources\OrdenResource;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{
    private const SORTABLE = [
        'id', 'folio', 'prioridad', 'status', 'fecha_programada', 'created_at',
    ];

    private const RELATIONS = [
        'vehiculo:id,placa,marca,modelo,anio',
        'creadoPor:id,nombre,apellido,email',
        'asignadoA:id,nombre,apellido',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Orden::class);

        $query = Orden::with(self::RELATIONS);

        // Búsqueda libre por folio o descripción
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtros exactos
        foreach (['status', 'prioridad', 'tipo'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('vehiculo_id')) {
            $query->where('vehiculo_id', $request->integer('vehiculo_id'));
        }
        if ($request->filled('asignado_a_id')) {
            $query->where('asignado_a_id', $request->integer('asignado_a_id'));
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_programada', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_programada', '<=', $request->input('fecha_hasta'));
        }

        // Ordenamiento
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc'
            ? 'asc' : 'desc';

        if (in_array($sortBy, self::SORTABLE, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('id');
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return OrdenResource::collection($query->paginate($perPage));
    }

    public function store(StoreOrdenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['creado_por_id'] = $request->user()->id;
        $data['status']        = Orden::STATUS_PENDIENTE;

        // Transacción: el folio se genera con lockForUpdate
        $orden = DB::transaction(fn () => Orden::create($data));
        $orden->load(self::RELATIONS);

        return (new OrdenResource($orden))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Orden $orden): OrdenResource
    {
        $this->authorize('view', $orden);

        $orden->load(self::RELATIONS);
        return new OrdenResource($orden);
    }

    public function update(UpdateOrdenRequest $request, Orden $orden): OrdenResource
    {
        $orden->fill($request->validated())->save();
        $orden->load(self::RELATIONS);

        return new OrdenResource($orden);
    }

    public function updateStatus(UpdateOrdenStatusRequest $request, Orden $orden): OrdenResource
    {
        $data      = $request->validated();
        $newStatus = $data['status'];

        $orden->status = $newStatus;

        // Timestamps automáticos según la transición
        if ($newStatus === Orden::STATUS_EN_PROGRESO && ! $orden->fecha_inicio) {
            $orden->fecha_inicio = now();
        }
        if (in_array($newStatus, [Orden::STATUS_COMPLETADA, Orden::STATUS_CANCELADA], true)) {
            $orden->fecha_cierre = now();
        }

        if (array_key_exists('costo_real', $data) && $data['costo_real'] !== null) {
            $orden->costo_real = $data['costo_real'];
        }

        // Anexa nota de cambio de estatus al historial
        if (! empty($data['notas'])) {
            $prefijo  = '[' . now()->format('Y-m-d H:i') . ' · ' . $newStatus . '] ';
            $nuevaNota = $prefijo . trim($data['notas']);
            $orden->notas = $orden->notas
                ? $orden->notas . "\n" . $nuevaNota
                : $nuevaNota;
        }

        $orden->save();
        $orden->load(self::RELATIONS);

        return new OrdenResource($orden);
    }
}