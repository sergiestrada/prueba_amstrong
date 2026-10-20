import { OrderType } from './order';

export type ReportDateField =
  | 'created_at'
  | 'fecha_cierre'
  | 'fecha_inicio'
  | 'fecha_programada';

export interface CostTotals {
  ordenes: number;
  costo_estimado: number;
  costo_real: number;
}

export interface CostByType {
  tipo: OrderType;
  ordenes: number;
  costo_estimado: number;
  costo_real: number;
}

export interface CostByTypeBreakdown {
  ordenes: number;
  costo_estimado: number;
  costo_real: number;
}

export interface CostByVehicle {
  vehiculo_id: number;
  placa: string;
  marca: string;
  modelo: string;
  anio: number;
  ordenes: number;
  costo_estimado: number;
  costo_real: number;
  por_tipo: Partial<Record<OrderType, CostByTypeBreakdown>>;
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
    totals: CostTotals;
    por_tipo: CostByType[];
    por_vehiculo: CostByVehicle[];
  };
}

export interface CostsReportFilters {
  from?: string;
  to?: string;
  vehiculo_id?: number;
  status?: 'pendiente' | 'en_progreso' | 'completada' | 'cancelada';
  include_cancelled?: boolean;
  date_field?: ReportDateField;
}