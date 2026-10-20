import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { OrderStatus, OrderType } from '../../../core/models/order';
import {
  CostByVehicle,
  ReportDateField
} from '../../../core/models/report';
import { ReportsService } from '../../../core/services/reports.service';

@Component({
  selector: 'app-costs-report',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './costs-report.html',
  styleUrl: './costs-report.css'
})
export class CostsReportComponent implements OnInit {
  private readonly reportsService = inject(ReportsService);
  private readonly fb = inject(FormBuilder);

  readonly loading = this.reportsService.loading;
  readonly report  = this.reportsService.report;

  readonly feedback  = signal<{ type: 'success' | 'error'; text: string } | null>(null);
  readonly expanded  = signal<Set<number>>(new Set());

  // Fechas por defecto: año en curso
  readonly filters = this.fb.nonNullable.group({
    from:              [this.defaultFrom()],
    to:                [this.defaultTo()],
    vehiculo_id:       [null as number | null],
    status:            ['' as '' | OrderStatus],
    include_cancelled: [false],
    date_field:        ['created_at' as ReportDateField]
  });

  /** Totales del reporte actual. */
  readonly totals = computed(() => this.report()?.totals ?? null);

  /** Máximo costo real por tipo (para escalar las barras del gráfico). */
  readonly maxCostByType = computed(() => {
    const tipos = this.report()?.por_tipo ?? [];
    return tipos.reduce((m, t) => Math.max(m, t.costo_real, t.costo_estimado), 0);
  });

  /** Tipos ordenados por costo real descendente (para el gráfico). */
  readonly chartData = computed(() => {
    const tipos = [...(this.report()?.por_tipo ?? [])];
    tipos.sort((a, b) => b.costo_real - a.costo_real);
    const max = this.maxCostByType() || 1;
    return tipos.map(t => ({
      ...t,
      pctEstimado: Math.round((t.costo_estimado / max) * 100),
      pctReal:     Math.round((t.costo_real / max) * 100)
    }));
  });

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    const v = this.filters.getRawValue();
    this.reportsService.getCosts({
      from:              v.from || undefined,
      to:                v.to || undefined,
      vehiculo_id:       v.vehiculo_id ?? undefined,
      status:            (v.status || undefined) as OrderStatus | undefined,
      include_cancelled: v.include_cancelled,
      date_field:        v.date_field
    }).subscribe({
      error: () => this.showFeedback('error', 'No se pudo generar el reporte.')
    });
  }

  resetDates(): void {
    this.filters.patchValue({
      from: this.defaultFrom(),
      to:   this.defaultTo()
    });
    this.reload();
  }

  clearDates(): void {
    this.filters.patchValue({ from: '', to: '' });
    this.reload();
  }

  toggleExpand(id: number): void {
    this.expanded.update((set) => {
      const next = new Set(set);
      if (next.has(id)) next.delete(id); else next.add(id);
      return next;
    });
  }

  isExpanded(id: number): boolean {
    return this.expanded().has(id);
  }

  expandAll(): void {
    const ids = this.report()?.por_vehiculo.map(v => v.vehiculo_id) ?? [];
    this.expanded.set(new Set(ids));
  }

  collapseAll(): void { this.expanded.set(new Set()); }

  // ─── Helpers de presentación ──────────────────────

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo'
      : t === 'correctivo'    ? 'Correctivo'
      : 'Emergencia';
  }

  typeClass(t: OrderType): string {
    return t === 'preventivo' ? 'type-preventivo'
      : t === 'correctivo'    ? 'type-correctivo'
      : 'type-emergencia';
  }

  money(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '$0.00';
    const n = typeof value === 'number' ? value : Number(value);
    return isNaN(n) ? '$0.00' : '$' + n.toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  /** Diferencia porcentual entre costo estimado y real (+ = sobrecosto). */
  deviation(est: number, real: number): { pct: number; cls: string } | null {
    if (est <= 0) return null;
    const pct = ((real - est) / est) * 100;
    const cls = pct > 5 ? 'dev-up' : pct < -5 ? 'dev-down' : 'dev-flat';
    return { pct, cls };
  }

  tipoEntries(v: CostByVehicle): { tipo: OrderType; data: any }[] {
    return (Object.entries(v.por_tipo) as [OrderType, any][])
      .map(([tipo, data]) => ({ tipo, data }));
  }

  trackByVehicle = (_: number, v: CostByVehicle) => v.vehiculo_id;

  private showFeedback(type: 'success' | 'error', text: string): void {
    this.feedback.set({ type, text });
    setTimeout(() => this.feedback.set(null), 3200);
  }

  private defaultFrom(): string {
    const y = new Date().getFullYear();
    return `${y}-01-01`;
  }

  private defaultTo(): string {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }
}