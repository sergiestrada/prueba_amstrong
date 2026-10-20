export type OrderStatus   = 'pendiente' | 'en_progreso' | 'completada' | 'cancelada';
export type OrderType     = 'preventivo' | 'correctivo' | 'emergencia';
export type OrderPriority = 'baja' | 'media' | 'alta' | 'critica';

export interface OrderVehicleRef {
  id: number;
  placa: string;
  marca: string;
  modelo: string;
  anio: number;
}

export interface OrderUserRef {
  id: number;
  nombre: string;
  apellido?: string | null;
  email?: string;
}

export interface Order {
  id: number;
  folio: string;
  status: OrderStatus;
  tipo: OrderType;
  prioridad: OrderPriority;
  transiciones_disponibles: OrderStatus[];
  vehiculo_id: number;
  vehiculo?: OrderVehicleRef;
  creado_por?: OrderUserRef;
  asignado_a?: OrderUserRef | null;
  descripcion: string;
  notas: string | null;
  fecha_programada: string | null;
  fecha_inicio: string | null;
  fecha_cierre: string | null;
  costo_estimado: string | null;
  costo_real: string | null;
  kilometraje_ingreso: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface OrderPayload {
  vehiculo_id: number;
  asignado_a_id?: number | null;
  tipo: OrderType;
  prioridad: OrderPriority;
  descripcion: string;
  notas?: string | null;
  fecha_programada?: string | null;
  costo_estimado?: number | null;
  kilometraje_ingreso?: number | null;
}

export interface OrderStatusPayload {
  status: OrderStatus;
  notas?: string | null;
  costo_real?: number | null;
}

export interface OrderFilters {
  search?: string;
  status?: OrderStatus;
  prioridad?: OrderPriority;
  tipo?: OrderType;
  vehiculo_id?: number;
  asignado_a_id?: number;
  fecha_desde?: string;
  fecha_hasta?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

/** Payload de paginación reutilizable (mismo formato que Laravel). */
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
  links: { first: string; last: string; prev: string | null; next: string | null };
  meta: PaginatedMeta;
}