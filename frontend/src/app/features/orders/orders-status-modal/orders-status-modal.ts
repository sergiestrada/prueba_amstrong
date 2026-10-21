import { CommonModule } from '@angular/common';
import { Component, EventEmitter, HostListener, Input, OnInit, Output, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Order, OrderStatus } from '../../../core/models/order';
import { AuthService } from '../../../core/services/auth';
import { OrdersService } from '../../../core/services/orders.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-order-status-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './orders-status-modal.html',
  styleUrl: './orders-status-modal.css'
})
export class OrderStatusModalComponent implements OnInit {
  private readonly fb            = inject(FormBuilder);
  private readonly ordersService = inject(OrdersService);
  private readonly authService   = inject(AuthService);

  @Input({ required: true }) order!: Order;
  @Output() changed = new EventEmitter<Order>();
  @Output() closed  = new EventEmitter<void>();

  readonly submitting   = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    estatus:      ['' as OrderStatus | '', [Validators.required]],
    costo:        [null as number | null],
    fecha_cierre: [''],
    notas:        ['']
  });

  /** Solo admin puede ver "cancelada". */
  get availableTransitions(): OrderStatus[] {
    const t = this.order.transiciones_disponibles;
    return this.authService.isAdmin() ? t : t.filter(s => s !== 'cancelada');
  }

  ngOnInit(): void {
    if (this.availableTransitions.length === 1) {
      this.form.controls.estatus.setValue(this.availableTransitions[0]);
    }

    this.form.controls.estatus.valueChanges.subscribe((v) => {
      const costo = this.form.controls.costo;
      const fecha = this.form.controls.fecha_cierre;
      if (v === 'cerrada') {
        costo.setValidators([Validators.required, Validators.min(0)]);
        fecha.setValidators([Validators.required]);
      } else {
        costo.clearValidators();
        fecha.clearValidators();
      }
      costo.updateValueAndValidity();
      fecha.updateValueAndValidity();
    });
  }

  @HostListener('document:keydown.escape')
  onEscape(): void { if (!this.submitting()) this.closed.emit(); }

  submit(): void {
    this.errorMessage.set(null);
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    const raw = this.form.getRawValue();
    if (!raw.estatus) return;

    this.submitting.set(true);
   this.ordersService.changeStatus(this.order.id, {
  estatus:      raw.estatus as OrderStatus,
  costo:        raw.estatus === 'cerrada' && raw.costo !== null ? Number(raw.costo) : null,
  fecha_cierre: raw.estatus === 'cerrada' ? (raw.fecha_cierre || null) : null,
  notas:        raw.notas ? raw.notas.trim() : null
}).subscribe({
  next:  (res: { data: Order }) => {
    this.submitting.set(false);
    this.changed.emit(res.data);
  },
  error: (err: HttpErrorResponse) => {
    this.submitting.set(false);
    this.errorMessage.set(this.extractError(err));
  }
});
  }

  cancel(): void { if (!this.submitting()) this.closed.emit(); }

  isComplete(): boolean { return this.form.controls.estatus.value === 'cerrada'; }

  statusLabel(s: OrderStatus): string {
    return s === 'abierta' ? 'Abierta'
      : s === 'en_proceso' ? 'En proceso'
      : s === 'cerrada' ? 'Cerrada'
      : 'Cancelada';
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