import { CommonModule } from '@angular/common';
import { Component, EventEmitter, HostListener, Input, Output } from '@angular/core';
import { Order, OrderPriority, OrderStatus } from '../../../core/models/order';

@Component({
  selector: 'app-order-detail-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './orders-detail-modal.html',
  styleUrl: './orders-detail-modal.css'
})
export class OrderDetailModalComponent {
  @Input({ required: true }) order!: Order;
  @Output() closed = new EventEmitter<void>();

  @HostListener('document:keydown.escape')
  onEscape(): void { this.closed.emit(); }

  close(): void { this.closed.emit(); }

  statusLabel(s: OrderStatus): string {
    switch (s) {
      case 'pendiente':   return 'Pendiente';
      case 'en_progreso': return 'En progreso';
      case 'completada':  return 'Completada';
      case 'cancelada':   return 'Cancelada';
    }
  }

  priorityLabel(p: OrderPriority): string {
    switch (p) {
      case 'baja':    return 'Baja';
      case 'media':   return 'Media';
      case 'alta':    return 'Alta';
      case 'critica': return 'Crítica';
    }
  }

  statusClass(s: OrderStatus): string {
    return s === 'pendiente' ? 'badge-pending'
      : s === 'en_progreso' ? 'badge-progress'
      : s === 'completada'  ? 'badge-done'
      : 'badge-cancel';
  }

  priorityClass(p: OrderPriority): string {
    return p === 'baja' ? 'pri-baja'
      : p === 'media'   ? 'pri-media'
      : p === 'alta'    ? 'pri-alta'
      : 'pri-critica';
  }

  money(v: string | null): string {
    if (v === null || v === '') return '—';
    const n = Number(v);
    return isNaN(n) ? '—' : '$' + n.toFixed(2);
  }
}