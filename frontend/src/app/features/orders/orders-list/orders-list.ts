import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { Order, OrderFilters, OrderStatus, OrderType } from '../../../core/models/order';
import { AuthService } from '../../../core/services/auth';
import { OrdersService } from '../../../core/services/orders.service';
import { OrderFormModalComponent } from '../orders-form-modal/orders-form-modal';
import { OrderStatusModalComponent } from '../orders-status-modal/orders-status-modal';
import { OrderDetailModalComponent } from '../orders-detail-modal/orders-detail-modal';

@Component({
  selector: 'app-orders-list',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    OrderFormModalComponent, OrderStatusModalComponent, OrderDetailModalComponent
  ],
  templateUrl: './orders-list.html',            // ← sin .component
  styleUrl: './orders-list.css'                 // ← sin .component
})
export class OrdersListComponent implements OnInit {
  private readonly ordersService = inject(OrdersService);
  private readonly authService   = inject(AuthService);
  private readonly fb            = inject(FormBuilder);

  readonly items   = this.ordersService.items;
  readonly meta    = this.ordersService.meta;
  readonly loading = this.ordersService.loading;
  readonly isAdmin = this.authService.isAdmin;

  readonly formOpen    = signal(false);
  readonly editing     = signal<Order | null>(null);
  readonly statusOpen  = signal(false);
  readonly statusOrder = signal<Order | null>(null);
  readonly detailOpen  = signal(false);
  readonly detailOrder = signal<Order | null>(null);
  readonly feedback    = signal<{ type: 'success' | 'error'; text: string } | null>(null);

  readonly filters = this.fb.nonNullable.group({
    search:  [''],
    estatus: [''],
    tipo:    ['']
  });

  readonly pages = computed(() => {
    const m = this.meta();
    if (!m) return [] as number[];
    const s = Math.max(1, m.current_page - 2);
    const e = Math.min(m.last_page, m.current_page + 2);
    const arr: number[] = [];
    for (let i = s; i <= e; i++) arr.push(i);
    return arr;
  });

  ngOnInit(): void {
    this.reload();
    this.filters.controls.search
      .valueChanges.pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => this.reload());
    this.filters.controls.estatus.valueChanges.subscribe(() => this.reload());
    this.filters.controls.tipo.valueChanges.subscribe(() => this.reload());
  }

  reload(page = 1): void {
    const v = this.filters.getRawValue();
    const filters: OrderFilters = { page, per_page: 15, sort_by: 'created_at', sort_dir: 'desc' };
    if (v.search)  filters.search  = v.search;
    if (v.estatus) filters.estatus = v.estatus as OrderStatus;
    if (v.tipo)    filters.tipo    = v.tipo as OrderType;
    this.ordersService.list(filters).subscribe({
      error: () => this.showFeedback('error', 'No se pudieron cargar las órdenes.')
    });
  }

  openCreate(): void { this.editing.set(null); this.formOpen.set(true); }
  openEdit(o: Order): void {
    if (this.isClosed(o)) return;
    this.editing.set(o); this.formOpen.set(true);
  }
  openStatus(o: Order): void {
    if (!this.hasAvailableTransitions(o)) return;
    this.statusOrder.set(o); this.statusOpen.set(true);
  }
  openDetail(o: Order): void { this.detailOrder.set(o); this.detailOpen.set(true); }

  closeForm(): void   { this.formOpen.set(false);   this.editing.set(null); }
  closeStatus(): void { this.statusOpen.set(false); this.statusOrder.set(null); }
  closeDetail(): void { this.detailOpen.set(false); this.detailOrder.set(null); }

  onSaved(_: Order, isEdit: boolean): void {
    this.closeForm(); this.reload();
    this.showFeedback('success', isEdit ? 'Orden actualizada.' : 'Orden creada.');
  }

  onStatusChanged(_: Order): void {
    this.closeStatus(); this.reload();
    this.showFeedback('success', 'Estatus actualizado.');
  }

  goToPage(p: number): void {
    const m = this.meta();
    if (!m || p < 1 || p > m.last_page) return;
    this.reload(p);
  }

  isClosed(o: Order): boolean {
    return o.estatus === 'cerrada' || o.estatus === 'cancelada';
  }

  /** Filtra cancelar si el usuario no es admin. */
  hasAvailableTransitions(o: Order): boolean {
    if (o.transiciones_disponibles.length === 0) return false;
    if (this.isAdmin()) return true;
    return o.transiciones_disponibles.some(s => s !== 'cancelada');
  }

  statusBadge(estatus: OrderStatus): { text: string; cls: string } {
    switch (estatus) {
      case 'abierta':    return { text: 'Abierta',    cls: 'badge-pending'  };
      case 'en_proceso': return { text: 'En proceso', cls: 'badge-progress' };
      case 'cerrada':    return { text: 'Cerrada',    cls: 'badge-done'     };
      case 'cancelada':  return { text: 'Cancelada',  cls: 'badge-cancel'   };
    }
  }

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo' : 'Correctivo';
  }

  money(v: string | null): string {
    if (v === null) return '—';
    const n = Number(v);
    return isNaN(n) ? '—' : '$' + n.toFixed(2);
  }

  private showFeedback(type: 'success' | 'error', text: string): void {
    this.feedback.set({ type, text });
    setTimeout(() => this.feedback.set(null), 3200);
  }
}