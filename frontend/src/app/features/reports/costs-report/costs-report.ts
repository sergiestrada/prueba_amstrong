import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { OrderStatus, OrderType } from '../../../core/models/order';
import { CostByVehicle, ReportDateField } from '../../../core/models/report';
import { ReportsService } from '../../../core/services/reports.service';

@Component({
  selector: 'app-costs-report',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './costs-report.html',
  styleUrl: './costs-report.css',
})
export class CostsReportComponent implements OnInit {
  private readonly reportsService = inject(ReportsService);
  private readonly fb = inject(FormBuilder);

  readonly loading = this.reportsService.loading;
  readonly report = this.reportsService.report;
  readonly error = this.reportsService.error;

  readonly expanded = signal<Set<number>>(new Set());

  readonly filters = this.fb.nonNullable.group({
    from: [this.defaultFrom()],
    to: [this.defaultTo()],
    status: ['' as '' | OrderStatus],
    include_cancelled: [false],
    date_field: ['created_at' as ReportDateField],
  });

  readonly totalOrdenes = computed(() => this.report()?.totals?.ordenes ?? 0);
  readonly totalCosto = computed(() => this.report()?.totals?.costo ?? 0);
  readonly hayDatos = computed(() => this.totalOrdenes() > 0);
  readonly porTipo = computed(() => this.report()?.por_tipo ?? []);
  readonly porVehiculo = computed(() => this.report()?.por_vehiculo ?? []);

  readonly maxCostByType = computed(() => {
    const tipos = this.porTipo();
    return tipos.reduce((m, t) => Math.max(m, t.costo ?? 0), 0) || 1;
  });

  readonly chartData = computed(() => {
    const tipos = [...this.porTipo()].sort((a, b) => b.costo - a.costo);
    const max = this.maxCostByType();
    return tipos.map((t) => ({
      ...t,
      pct: Math.max(2, Math.round(((t.costo || 0) / max) * 100)),
    }));
  });

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    const v = this.filters.getRawValue();
    this.reportsService
      .getCosts({
        from: v.from || undefined,
        to: v.to || undefined,
        status: (v.status || undefined) as OrderStatus | undefined,
        include_cancelled: !!v.include_cancelled,
        date_field: v.date_field,
      })
      .subscribe();
  }

  resetDates(): void {
    this.filters.patchValue({ from: this.defaultFrom(), to: this.defaultTo() });
    this.reload();
  }

  clearDates(): void {
    this.filters.patchValue({ from: '', to: '' });
    this.reload();
  }

  toggleExpand(id: number): void {
    this.expanded.update((set) => {
      const next = new Set(set);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  }

  isExpanded(id: number): boolean {
    return this.expanded().has(id);
  }

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo' : 'Correctivo';
  }

  typeClass(t: OrderType): string {
    return t === 'preventivo' ? 'type-preventivo' : 'type-correctivo';
  }

  money(v: number | string | null | undefined): string {
    const n = Number(v ?? 0);
    return isNaN(n)
      ? '$0.00'
      : '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  tipoEntries(v: CostByVehicle): { tipo: OrderType; data: { ordenes: number; costo: number } }[] {
    return (
      Object.entries(v.por_tipo ?? {}) as [OrderType, { ordenes: number; costo: number }][]
    ).map(([tipo, data]) => ({ tipo, data }));
  }

  private defaultFrom(): string {
    return `${new Date().getFullYear()}-01-01`;
  }

  private defaultTo(): string {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }
}