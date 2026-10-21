export type OrderStatus = 'abierta' | 'en_proceso' | 'cerrada' | 'cancelada';
export type OrderType   = 'preventivo' | 'correctivo';
export type OrderPriority = 'baja' | 'media' | 'alta' | 'critica';

export interface OrderVehicleRef {
  id: number;
  placa: string;
  numero_economico: string;
  marca: string;
  modelo: string;
  anio: number;
}


export interface Order {
  id: number;
  folio: string;
  tipo: OrderType;
  estatus: OrderStatus;
  transiciones_disponibles: OrderStatus[];
  vehiculo_id: number;
  vehiculo?: OrderVehicleRef;
  creado_por?: { id: number; nombre: string };
  descripcion: string;
  odometro_ingreso: number;
  fecha_programada: string;
  fecha_cierre: string | null;
  costo: string | null;
  notas: string | null;
  created_at: string;
  updated_at: string;
}

export interface OrderPayload {
  vehiculo_id: number;
  tipo: OrderType;
  descripcion: string;
  odometro_ingreso: number;
  fecha_programada: string;
  notas?: string | null;
}

// ─── Paginación (Laravel) ─────────────────────────────
export interface PaginatedMeta {
  current_page: number;
  from: number;
  last_page: number;
  per_page: number;
  to: number;
  total: number;
  path: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: PaginatedMeta;
}

export interface OrderStatusPayload {
  estatus: OrderStatus;
  costo?: number | null;
  fecha_cierre?: string | null;
  notas?: string | null;
}

export interface OrderFilters {
  search?: string;
  estatus?: OrderStatus;
  tipo?: OrderType;
  vehiculo_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}