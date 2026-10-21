export type OrderStatus = 'abierta' | 'en_proceso' | 'cerrada' | 'cancelada';
export type OrderType = 'preventivo' | 'correctivo';
export type OrderPriority = 'baja' | 'media' | 'alta';

export interface OrderVehicleRef {
  id: number;
  placa: string;
  numero_economico: string;
  marca: string;
  modelo: string;
  anio: number;
}

export interface OrderUserRef {
  id: number;
  nombre: string;
  email: string;
}

export interface Order {
  id: number;
  folio: string;
  tipo: OrderType;
  status: OrderStatus;
  prioridad: OrderPriority;
  transiciones_disponibles: OrderStatus[];

  vehiculo_id: number;
  vehiculo?: OrderVehicleRef;
  creado_por?: OrderUserRef;
  asignado_a?: OrderUserRef | null;

  descripcion: string;
  odometro_ingreso: number;
  fecha_programada: string;
  fecha_inicio?: string | null;
  fecha_cierre: string | null;

  costo: string | null;
  costo_estimado?: string | null;
  costo_real?: string | null;

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
  prioridad?: OrderPriority;
  costo?: number | null;        // ← nuevo
  notas?: string | null;
}

export interface OrderStatusPayload {
  status: OrderStatus;
  costo?: number | null;
  fecha_cierre?: string | null;
  notas?: string | null;
}

export interface OrderFilters {
  search?: string;
  status?: OrderStatus;
  tipo?: OrderType;
  prioridad?: OrderPriority;
  vehiculo_id?: number;
  asignado_a_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

// ─── Paginación (Laravel) ─────────────────────────────
export interface PaginatedMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
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