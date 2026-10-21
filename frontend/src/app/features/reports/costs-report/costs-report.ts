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
  styleUrl: './costs-report.css'
})
export class CostsReportComponent implements OnInit {
  private readonly reportsService = inject(ReportsService);
  private readonly fb = inject(FormBuilder);

  readonly loading = this.reportsService.loading;
  readonly report  = this.reportsService.report;

  readonly feedback = signal<{ type: 'success' | 'error'; text: string } | null>(null);
  readonly expanded = signal<Set<number>>(new Set());

  readonly filters = this.fb.nonNullable.group({
    from:              [this.defaultFrom()],
    to:                [this.defaultTo()],
    status:            ['' as '' | OrderStatus],
    include_cancelled: [false],
    date_field:        ['created_at' as ReportDateField]
  });

  readonly totals = computed(() => this.report()?.totals ?? null);

  readonly maxCostByType = computed(() => {
    const tipos = this.report()?.por_tipo ?? [];
    return tipos.reduce((m, t) => Math.max(m, t.costo), 0) || 1;
  });

  readonly chartData = computed(() => {
    const tipos = [...(this.report()?.por_tipo ?? [])];
    tipos.sort((a, b) => b.costo - a.costo);
    const max = this.maxCostByType();
    return tipos.map(t => ({ ...t, pct: Math.round((t.costo / max) * 100) }));
  });

  ngOnInit(): void { this.reload(); }

  reload(): void {
    const v = this.filters.getRawValue();
    this.reportsService.getCosts({
      from:              v.from || undefined,
      to:                v.to || undefined,
      status:            (v.status || undefined) as OrderStatus | undefined,
      include_cancelled: v.include_cancelled,
      date_field:        v.date_field
    }).subscribe({
      error: () => this.showFeedback('error', 'No se pudo generar el reporte.')
    });
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

  isExpanded(id: number): boolean { return this.expanded().has(id); }

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo' : 'Correctivo';
  }

  typeClass(t: OrderType): string {
    return t === 'preventivo' ? 'type-preventivo' : 'type-correctivo';
  }

  money(v: number | string | null | undefined): string {
    if (v === null || v === undefined || v === '') return '$0.00';
    const n = typeof v === 'number' ? v : Number(v);
    return isNaN(n) ? '$0.00' : '$' + n.toLocaleString('es-MX', {
      minimumFractionDigits: 2, maximumFractionDigits: 2
    });
  }

  tipoEntries(v: CostByVehicle): { tipo: OrderType; data: { ordenes: number; costo: number } }[] {
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