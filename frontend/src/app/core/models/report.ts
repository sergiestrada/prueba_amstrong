import { OrderType } from './order';

export type ReportDateField =
  | 'created_at'
  | 'fecha_cierre'
  | 'fecha_inicio'
  | 'fecha_programada';

export interface CostByType {
  tipo: OrderType;
  ordenes: number;
  costo: number;
}

export interface CostByVehicle {
  vehiculo_id: number;
  placa: string;
  numero_economico: string;
  marca: string;
  modelo: string;
  anio: number;
  ordenes: number;
  costo: number;
  por_tipo: Partial<Record<OrderType, { ordenes: number; costo: number }>>;
}

export interface CostsReportResponse {
  data: {
    period: {
      from: string | null;
      to: string | null;
      date_field: ReportDateField;
    };
    filters: {
      vehiculo_id: number | null;
      status: string | null;
      include_cancelled: boolean;
    };
    totals: { ordenes: number; costo: number };
    por_tipo: CostByType[];
    por_vehiculo: CostByVehicle[];
  };
}

export interface CostsReportFilters {
  from?: string;
  to?: string;
  vehiculo_id?: number;
  status?: 'abierta' | 'en_proceso' | 'cerrada' | 'cancelada';
  include_cancelled?: boolean;
  date_field?: ReportDateField;
}