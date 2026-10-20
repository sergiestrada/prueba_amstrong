import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import {
  Order,
  OrderFilters,
  OrderPriority,
  OrderStatus,
  OrderType
} from '../../../core/models/order';
import { OrdersService } from '../../../core/services/orders.service';
import { OrderFormModalComponent } from '../orders-form-modal/orders-form-modal';
import { OrderStatusModalComponent } from '../orders-status-modal/orders-status-modal';
import { OrderDetailModalComponent } from '../orders-detail-modal/orders-detail-modal';

@Component({
  selector: 'app-orders-list',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    OrderFormModalComponent,
    OrderStatusModalComponent,
    OrderDetailModalComponent
  ],
  templateUrl: './orders-list.html',
  styleUrl: './orders-list.css'
})
export class OrdersListComponent implements OnInit {
  private readonly ordersService = inject(OrdersService);
  private readonly fb = inject(FormBuilder);

  readonly items   = this.ordersService.items;
  readonly meta    = this.ordersService.meta;
  readonly loading = this.ordersService.loading;

  readonly formOpen    = signal(false);
  readonly editing     = signal<Order | null>(null);
  readonly statusOpen  = signal(false);
  readonly statusOrder = signal<Order | null>(null);
  readonly detailOpen  = signal(false);
  readonly detailOrder = signal<Order | null>(null);
  readonly feedback    = signal<{ type: 'success' | 'error'; text: string } | null>(null);

  readonly filters = this.fb.nonNullable.group({
    search:    [''],
    status:    [''],
    prioridad: [''],
    tipo:      ['']
  });

  readonly pages = computed(() => {
    const m = this.meta();
    if (!m) return [] as number[];
    const start = Math.max(1, m.current_page - 2);
    const end   = Math.min(m.last_page, m.current_page + 2);
    const arr: number[] = [];
    for (let i = start; i <= end; i++) arr.push(i);
    return arr;
  });

  ngOnInit(): void {
    this.reload();

    this.filters.controls.search
      .valueChanges.pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => this.reload());

    this.filters.controls.status.valueChanges.subscribe(() => this.reload());
    this.filters.controls.prioridad.valueChanges.subscribe(() => this.reload());
    this.filters.controls.tipo.valueChanges.subscribe(() => this.reload());
  }

  reload(page = 1): void {
    const v = this.filters.getRawValue();
    const filters: OrderFilters = {
      page,
      per_page: 15,
      sort_by: 'created_at',
      sort_dir: 'desc'
    };
    if (v.search)    filters.search    = v.search;
    if (v.status)    filters.status    = v.status as OrderStatus;
    if (v.prioridad) filters.prioridad = v.prioridad as OrderPriority;
    if (v.tipo)      filters.tipo      = v.tipo as OrderType;

    this.ordersService.list(filters).subscribe({
      error: () => this.showFeedback('error', 'No se pudieron cargar las órdenes.')
    });
  }

  // ---- Modales ----

  openCreate(): void { this.editing.set(null); this.formOpen.set(true); }

  openEdit(order: Order): void {
    if (this.isClosed(order)) return;
    this.editing.set(order);
    this.formOpen.set(true);
  }

  openStatus(order: Order): void {
    if (order.transiciones_disponibles.length === 0) return;
    this.statusOrder.set(order);
    this.statusOpen.set(true);
  }

  openDetail(order: Order): void {
    this.detailOrder.set(order);
    this.detailOpen.set(true);
  }

  closeForm(): void    { this.formOpen.set(false);   this.editing.set(null); }
  closeStatus(): void  { this.statusOpen.set(false); this.statusOrder.set(null); }
  closeDetail(): void  { this.detailOpen.set(false); this.detailOrder.set(null); }

  onSaved(_: Order, isEdit: boolean): void {
    this.closeForm();
    this.reload();
    this.showFeedback('success', isEdit ? 'Orden actualizada.' : 'Orden creada.');
  }

  onStatusChanged(_: Order): void {
    this.closeStatus();
    this.reload();
    this.showFeedback('success', 'Estatus actualizado.');
  }

  // ---- Helpers de vista ----

  goToPage(page: number): void {
    const m = this.meta();
    if (!m || page < 1 || page > m.last_page) return;
    this.reload(page);
  }

  isClosed(order: Order): boolean {
    return order.status === 'completada' || order.status === 'cancelada';
  }

  statusBadge(status: OrderStatus): { text: string; cls: string } {
    switch (status) {
      case 'pendiente':   return { text: 'Pendiente',    cls: 'badge-pending'  };
      case 'en_progreso': return { text: 'En progreso',  cls: 'badge-progress' };
      case 'completada':  return { text: 'Completada',   cls: 'badge-done'     };
      case 'cancelada':   return { text: 'Cancelada',    cls: 'badge-cancel'   };
    }
  }

  priorityBadge(p: OrderPriority): { text: string; cls: string } {
    switch (p) {
      case 'baja':    return { text: 'Baja',    cls: 'pri-baja'    };
      case 'media':   return { text: 'Media',   cls: 'pri-media'   };
      case 'alta':    return { text: 'Alta',    cls: 'pri-alta'    };
      case 'critica': return { text: 'Crítica', cls: 'pri-critica' };
    }
  }

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo'
      : t === 'correctivo'    ? 'Correctivo'
      : 'Emergencia';
  }

  formatMoney(value: string | null): string {
    if (value === null || value === '') return '—';
    const n = Number(value);
    return isNaN(n) ? '—' : '$' + n.toFixed(2);
  }

  private showFeedback(type: 'success' | 'error', text: string): void {
    this.feedback.set({ type, text });
    setTimeout(() => this.feedback.set(null), 3200);
  }
}