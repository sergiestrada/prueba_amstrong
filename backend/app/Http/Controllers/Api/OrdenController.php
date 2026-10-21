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
       // $this->authorize('create', Orden::class);
 $query = Orden::with(self::RELATIONS);
        $usuario = $request->user();
        if (! $usuario) {
            return response()->json(
                ['message' => 'No hay un usuario autenticado.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = $request->validated();
        $data['creado_por_id'] = $usuario->id;
        $data['status']        = Orden::STATUS_PENDIENTE;
        $data['prioridad']     = $data['prioridad'] ?? 'media';

        // El folio se genera en el hook creating() del modelo, dentro de la transacción.
        $orden = DB::transaction(fn () => Orden::create($data));

        $orden->load(self::RELATIONS);

        return (new OrdenResource($orden))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Orden $orden): OrdenResource
    {
       // $this->authorize('view', $orden);

        $orden->load(self::RELATIONS);
        return new OrdenResource($orden);
    }

    public function update(UpdateOrdenRequest $request, Orden $orden): OrdenResource
    {
     //   $this->authorize('update', $orden);

        $orden->fill($request->validated())->save();
        $orden->load(self::RELATIONS);

        return new OrdenResource($orden);
    }

    public function updateStatus(UpdateOrdenStatusRequest $request, Orden $orden): OrdenResource
    {
      //  $this->authorize('changeStatus', $orden);

        $data      = $request->validated();
        $newStatus = $data['status'];

        $orden->status = $newStatus;

        if ($newStatus === Orden::STATUS_EN_PROGRESO && ! $orden->fecha_inicio) {
            $orden->fecha_inicio = now();
        }
        if (in_array($newStatus, [Orden::STATUS_COMPLETADA, Orden::STATUS_CANCELADA], true)) {
            $orden->fecha_cierre = now();
        }

        if (array_key_exists('costo_real', $data) && $data['costo_real'] !== null) {
            $orden->costo_real = $data['costo_real'];
        }

        if (! empty($data['notas'])) {
            $prefijo   = '[' . now()->format('Y-m-d H:i') . ' · ' . $newStatus . '] ';
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