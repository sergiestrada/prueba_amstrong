<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vehiculo\StoreVehiculoRequest;
use App\Http\Requests\Vehiculo\UpdateVehiculoRequest;
use App\Http\Resources\VehiculoResource;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class VehiculoController extends Controller
{
    private const SORTABLE = [
        'id', 'placa', 'marca', 'modelo', 'anio',
        'kilometraje', 'fecha_proximo_servicio', 'created_at',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vehiculo::class);

        $query = Vehiculo::query();

        // Filtro de búsqueda libre
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Filtros exactos
        foreach (['marca', 'modelo', 'tipo', 'estado'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('anio')) {
            $query->where('anio', $request->integer('anio'));
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
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

        // Paginación acotada
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return VehiculoResource::collection($query->paginate($perPage));
    }

    public function store(StoreVehiculoRequest $request): JsonResponse
    {
        $vehiculo = Vehiculo::create($request->validated());

        return (new VehiculoResource($vehiculo))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Vehiculo $vehiculo): VehiculoResource
    {
        $this->authorize('view', $vehiculo);

        return new VehiculoResource($vehiculo);
    }

    public function update(UpdateVehiculoRequest $request, Vehiculo $vehiculo): VehiculoResource
    {
        $vehiculo->fill($request->validated())->save();

        return new VehiculoResource($vehiculo->refresh());
    }

    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('delete', $vehiculo);

        $vehiculo->delete();

        return response()->json([
            'message' => 'Vehículo eliminado correctamente.',
        ], Response::HTTP_OK);
    }

    public function dueForService(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vehiculo::class);

        $validated = $request->validate([
            'days'             => ['sometimes', 'integer', 'min:-365', 'max:365'],
            'include_inactive' => ['sometimes', 'boolean'],
            'per_page'         => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $days            = (int) ($validated['days'] ?? 7);
        $includeInactive = (bool) ($validated['include_inactive'] ?? false);
        $perPage         = (int) ($validated['per_page'] ?? 15);

        $query = Vehiculo::query()->dueForService($days);

        if (! $includeInactive) {
            $query->where('activo', true);
        }

        $query->orderBy('fecha_proximo_servicio', 'asc');

        return VehiculoResource::collection($query->paginate($perPage));
    }
}