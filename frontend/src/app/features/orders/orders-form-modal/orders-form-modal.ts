import { CommonModule } from '@angular/common';
import { Component, EventEmitter, HostListener, Input, OnInit, Output, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Order, OrderPayload, OrderType } from '../../../core/models/order';
import { Vehicle } from '../../../core/models/vehicle';
import { OrdersService } from '../../../core/services/orders.service';
import { VehiclesService } from '../../../core/services/vehicles.service';

@Component({
  selector: 'app-order-form-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './orders-form-modal.html',
  styleUrl: './orders-form-modal.css'
})
export class OrderFormModalComponent implements OnInit {
  private readonly fb             = inject(FormBuilder);
  private readonly ordersService  = inject(OrdersService);
  private readonly vehiclesService = inject(VehiclesService);

  @Input() order: Order | null = null;
  @Output() saved  = new EventEmitter<Order>();
  @Output() closed = new EventEmitter<void>();

  readonly submitting   = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly isEdit       = signal(false);
  readonly vehicles     = signal<Vehicle[]>([]);

  private orderId: number | null = null;

  readonly form = this.fb.nonNullable.group({
    vehiculo_id:       [0, [Validators.required, Validators.min(1)]],
    tipo:              ['preventivo' as OrderType, [Validators.required]],
    descripcion:       ['', [Validators.required, Validators.maxLength(2000)]],
    odometro_ingreso:  [0, [Validators.required, Validators.min(0)]],
    fecha_programada:  ['', [Validators.required]],
    notas:             ['']
  });

  ngOnInit(): void {
    if (this.order) {
      this.isEdit.set(true);
      this.orderId = this.order.id;
      this.form.patchValue({
        vehiculo_id:      this.order.vehiculo_id,
        tipo:             this.order.tipo,
        descripcion:      this.order.descripcion,
        odometro_ingreso: this.order.odometro_ingreso,
        fecha_programada: this.order.fecha_programada,
        notas:            this.order.notas ?? ''
      });
    }
    this.loadVehicles();
  }

  private loadVehicles(): void {
    this.vehiclesService.list({ per_page: 100, sort_by: 'placa', sort_dir: 'asc' })
      .subscribe({
        next: (res) => {
          // RN-06: excluir vehículos en baja
          const list = res.data.filter(v => v.estatus !== 'baja');
          this.vehicles.set(list);
        },
        error: () => this.errorMessage.set('No se pudieron cargar los vehículos.')
      });
  }

  @HostListener('document:keydown.escape')
  onEscape(): void { if (!this.submitting()) this.closed.emit(); }

  /** Odómetro actual del vehículo seleccionado (para validación visual). */
  selectedVehicleOdometer(): number | null {
    const id = this.form.controls.vehiculo_id.value;
    return this.vehicles().find(v => v.id === id)?.odometro_actual ?? null;
  }

  submit(): void {
    this.errorMessage.set(null);
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }

    const raw = this.form.getRawValue();

    // Validación local RN-04 (defensa extra al backend)
    const actual = this.selectedVehicleOdometer();
    if (actual !== null && Number(raw.odometro_ingreso) < actual) {
      this.errorMessage.set(`El odómetro no puede ser menor al actual del vehículo (${actual} km).`);
      return;
    }

    const payload: OrderPayload = {
      vehiculo_id:      Number(raw.vehiculo_id),
      tipo:             raw.tipo,
      descripcion:      raw.descripcion.trim(),
      odometro_ingreso: Number(raw.odometro_ingreso),
      fecha_programada: raw.fecha_programada,
      notas:            raw.notas ? raw.notas.trim() : null
    };

    this.submitting.set(true);
    const op$ = this.isEdit() && this.orderId !== null
      ? this.ordersService.update(this.orderId, payload as any)
      : this.ordersService.create(payload);

    op$.subscribe({
      next:  (res) => { this.submitting.set(false); this.saved.emit(res.data); },
      error: (err) => { this.submitting.set(false); this.errorMessage.set(this.extractError(err)); }
    });
  }

  cancel(): void { if (!this.submitting()) this.closed.emit(); }

  get vehiculo_id()      { return this.form.controls.vehiculo_id; }
  get descripcion()      { return this.form.controls.descripcion; }
  get odometro_ingreso() { return this.form.controls.odometro_ingreso; }
  get fecha_programada() { return this.form.controls.fecha_programada; }

  private extractError(err: any): string {
    const errors = err?.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      const first = Object.values(errors)[0];
      if (Array.isArray(first) && first.length) return first[0];
    }
    return err?.error?.message ?? 'No se pudo guardar la orden.';
  }
}