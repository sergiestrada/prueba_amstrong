import { CommonModule } from '@angular/common';
import {
  Component, EventEmitter, HostListener, Input,
  OnInit, Output, inject, signal
} from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Order, OrderStatus } from '../../../core/models/order';
import { OrdersService } from '../../../core/services/orders.service';

@Component({
  selector: 'app-order-status-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './orders-status-modal.html',
  styleUrl: './orders-status-modal.css'
})
export class OrderStatusModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly ordersService = inject(OrdersService);

  @Input({ required: true }) order!: Order;
  @Output() changed = new EventEmitter<Order>();
  @Output() closed  = new EventEmitter<void>();

  readonly submitting   = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    status:     ['' as OrderStatus | '', [Validators.required]],
    notas:      [''],
    costo_real: [null as number | null]
  });

  ngOnInit(): void {
    // Si solo hay una transición válida, la seleccionamos por defecto.
    const t = this.order.transiciones_disponibles;
    if (t.length === 1) {
      this.form.controls.status.setValue(t[0]);
    }
    // costo_real solo es relevante al completar
    this.form.controls.status.valueChanges.subscribe((v) => {
      if (v !== 'completada') this.form.controls.costo_real.setValue(null);
    });
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (!this.submitting()) this.closed.emit();
  }

  submit(): void {
    this.errorMessage.set(null);

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.getRawValue();
    if (!raw.status) return;

    this.submitting.set(true);

    this.ordersService.changeStatus(this.order.id, {
      status:     raw.status as OrderStatus,
      notas:      raw.notas ? raw.notas.trim() : null,
      costo_real: raw.status === 'completada' && raw.costo_real !== null
        ? Number(raw.costo_real) : null
    }).subscribe({
      next: (res) => {
        this.submitting.set(false);
        this.changed.emit(res.data);
      },
      error: (err) => {
        this.submitting.set(false);
        this.errorMessage.set(this.extractError(err));
      }
    });
  }

  cancel(): void {
    if (!this.submitting()) this.closed.emit();
  }

  isComplete(): boolean {
    return this.form.controls.status.value === 'completada';
  }

  statusLabel(s: OrderStatus): string {
    switch (s) {
      case 'pendiente':   return 'Pendiente';
      case 'en_progreso': return 'En progreso';
      case 'completada':  return 'Completada';
      case 'cancelada':   return 'Cancelada';
    }
  }

  private extractError(err: any): string {
    const errors = err?.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      const first = Object.values(errors)[0];
      if (Array.isArray(first) && first.length) return first[0];
    }
    return err?.error?.message ?? 'No se pudo cambiar el estatus.';
  }
}