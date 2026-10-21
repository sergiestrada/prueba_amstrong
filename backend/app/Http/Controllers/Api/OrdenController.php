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
    'vehiculo:id,placa,numero_economico,marca,modelo,anio',
    'creadoPor:id,nombre,email',
    'asignadoA:id,nombre,email',
];

    public function index(Request $request): AnonymousResourceCollection
    {
       // $this->authorize('viewAny', Orden::class);

        $query = Orden::with(self::RELATIONS);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

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
    $usuario = $request->user();

    if (! $usuario || ! $usuario->id) {
        return response()->json(
            ['message' => 'No hay un usuario autenticado.'],
            Response::HTTP_UNAUTHORIZED
        );
    }

    $data = $request->validated();

    $orden = DB::transaction(function () use ($data, $usuario) {
        $data['creado_por_id'] = $usuario->id;
        $data['status']        = Orden::STATUS_ABIERTA;   // ← status
        $data['prioridad']     = $data['prioridad'] ?? 'media';

        return Orden::create($data);
    }, 5);

    $orden->load(self::RELATIONS);

    return (new OrdenResource($orden))
        ->response()
        ->setStatusCode(Response::HTTP_CREATED);
}

public function updateStatus(UpdateOrdenStatusRequest $request, Orden $orden): OrdenResource
{
    $data  = $request->validated();
    $nuevo = $data['status'];                              // ← status

    $orden->status = $nuevo;

    if ($nuevo === Orden::STATUS_EN_PROCESO && ! $orden->fecha_inicio) {
        $orden->fecha_inicio = now();
    }
    if (in_array($nuevo, [Orden::STATUS_CERRADA, Orden::STATUS_CANCELADA], true)) {
        $orden->fecha_cierre = now();
    }

    if (array_key_exists('costo', $data) && $data['costo'] !== null) {
        $orden->costo = $data['costo'];
    }

    if (! empty($data['notas'])) {
        $prefijo   = '[' . now()->format('Y-m-d H:i') . ' · ' . $nuevo . '] ';
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