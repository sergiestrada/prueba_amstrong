import { CommonModule } from '@angular/common';
import { Component, EventEmitter, HostListener, Input, Output } from '@angular/core';
import { Order, OrderStatus, OrderType } from '../../../core/models/order';

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
    return s === 'abierta' ? 'Abierta'
      : s === 'en_proceso' ? 'En proceso'
      : s === 'cerrada' ? 'Cerrada'
      : 'Cancelada';
  }

  statusClass(s: OrderStatus): string {
    return s === 'abierta' ? 'badge-pending'
      : s === 'en_proceso' ? 'badge-progress'
      : s === 'cerrada' ? 'badge-done'
      : 'badge-cancel';
  }

  typeLabel(t: OrderType): string {
    return t === 'preventivo' ? 'Preventivo' : 'Correctivo';
  }

  money(v: string | null): string {
    if (v === null) return '—';
    const n = Number(v);
    return isNaN(n) ? '—' : '$' + n.toFixed(2);
  }
}