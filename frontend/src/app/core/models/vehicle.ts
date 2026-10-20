export type VehicleType = 'sedan' | 'suv' | 'pickup' | 'camion' | 'moto' | 'otro';
export type VehicleStatus = 'activo' | 'mantenimiento' | 'inactivo';

export interface Vehicle {
  id: number;
  placa: string;
  marca: string;
  modelo: string;
  anio: number;
  color: string | null;
  vin: string | null;
  tipo: VehicleType;
  kilometraje: number;
  fecha_ultimo_servicio: string | null;
  fecha_proximo_servicio: string | null;
  dias_para_servicio: number | null;
  estado: VehicleStatus;
  activo: boolean;
  notas: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface VehiclePayload {
  placa: string;
  marca: string;
  modelo: string;
  anio: number;
  color?: string | null;
  vin?: string | null;
  tipo: VehicleType;
  kilometraje: number;
  fecha_ultimo_servicio?: string | null;
  fecha_proximo_servicio?: string | null;
  estado: VehicleStatus;
  activo?: boolean;
  notas?: string | null;
}

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

export interface VehicleFilters {
  search?: string;
  marca?: string;
  modelo?: string;
  tipo?: VehicleType;
  estado?: VehicleStatus;
  anio?: number;
  activo?: boolean;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}