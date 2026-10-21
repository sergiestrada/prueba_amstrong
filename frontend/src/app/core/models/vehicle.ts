export type VehicleType = 'sedan' | 'suv' | 'pickup' | 'camion' | 'moto' | 'otro';
export type VehicleStatus = 'activo' | 'en_taller' | 'baja';

export interface Vehicle {
  id: number;
  placa: string;
  numero_economico: string;
  marca: string;
  modelo: string;
  anio: number;
  odometro_actual: number;
  estatus: VehicleStatus;
  fecha_alta: string | null;
  notas: string | null;
  requiere_servicio: boolean;
  created_at: string;
  updated_at: string;
}

export interface VehiclePayload {
  placa: string;
  numero_economico: string;
  marca: string;
  modelo: string;
  anio: number;
  odometro_actual: number;
  estatus: VehicleStatus;
  fecha_alta?: string | null;
  notas?: string | null;
}

export interface VehicleFilters {
  search?: string;
  estatus?: VehicleStatus;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

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
  links: { first: string; last: string; prev: string | null; next: string | null };
  meta: PaginatedMeta;
}