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

class VehiculoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vehiculo::class);

        $query = Vehiculo::query();

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'like', "%{$search}%")
                  ->orWhere('numero_economico', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $query->orderBy($request->input('sort_by', 'created_at'),
                        $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc');

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return VehiculoResource::collection($query->paginate($perPage));
    }

    public function store(StoreVehiculoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['fecha_alta'] = $data['fecha_alta'] ?? now()->toDateString();

        $vehiculo = Vehiculo::create($data);

        return (new VehiculoResource($vehiculo))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Vehiculo $vehiculo): VehiculoResource
    {
        $this->authorize('view', $vehiculo);

        // RF-05: incluye flag de requiere servicio para aviso visible
        return (new VehiculoResource($vehiculo))
            ->additional(['requiere_servicio' => $vehiculo->requiereServicio()]);
    }

    public function update(UpdateVehiculoRequest $request, Vehiculo $vehiculo): VehiculoResource
    {
        $vehiculo->fill($request->validated())->save();
        return new VehiculoResource($vehiculo->refresh());
    }

    /** RN-08: dar de baja = estatus "baja". Solo admin. */
    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('delete', $vehiculo);

        $vehiculo->update(['estatus' => Vehiculo::ESTATUS_BAJA]);

        return response()->json(['message' => 'Vehículo dado de baja.']);
    }

    /** RF-05 / RN-07 */
    public function dueForService(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vehiculo::class);

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return VehiculoResource::collection(
            Vehiculo::dueForService()->orderBy('odometro_actual', 'desc')->paginate($perPage)
        );
    }
}