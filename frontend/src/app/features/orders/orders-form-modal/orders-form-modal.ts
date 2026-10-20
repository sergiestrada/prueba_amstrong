import { CommonModule } from '@angular/common';
import {
  Component, EventEmitter, HostListener, Input,
  OnInit, Output, inject, signal
} from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Order, OrderPayload, OrderPriority, OrderType } from '../../../core/models/order';
import { OrdersService } from '../../../core/services/orders.service';
import { VehiclesService } from '../../../core/services/vehicles.service';

interface VehicleOption {
  id: number;
  placa: string;
  marca: string;
  modelo: string;
  anio: number;
}

@Component({
  selector: 'app-order-form-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './orders-form-modal.html',
  styleUrl: './orders-form-modal.css'
})
export class OrderFormModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly ordersService = inject(OrdersService);
  private readonly vehiclesService = inject(VehiclesService);

  @Input() order: Order | null = null;
  @Output() saved  = new EventEmitter<Order>();
  @Output() closed = new EventEmitter<void>();

  readonly submitting    = signal(false);
  readonly errorMessage  = signal<string | null>(null);
  readonly isEdit        = signal(false);
  readonly vehicles      = signal<VehicleOption[]>([]);
  readonly vehiclesReady = signal(false);
  readonly vehiclesError = signal<string | null>(null);

  private orderId: number | null = null;

  readonly form = this.fb.nonNullable.group({
    vehiculo_id:          [0, [Validators.required, Validators.min(1)]],
    tipo:                 ['preventivo' as OrderType, [Validators.required]],
    prioridad:            ['media' as OrderPriority, [Validators.required]],
    fecha_programada:     [''],
    costo_estimado:       [null as number | null],
    kilometraje_ingreso:  [null as number | null],
    descripcion:          ['', [Validators.required, Validators.maxLength(2000)]],
    notas:                ['', [Validators.maxLength(2000)]]
  });

  ngOnInit(): void {
    if (this.order) {
      this.isEdit.set(true);
      this.orderId = this.order.id;
      this.form.patchValue({
        vehiculo_id:         this.order.vehiculo_id,
        tipo:                this.order.tipo,
        prioridad:           this.order.prioridad,
        fecha_programada:    this.order.fecha_programada ?? '',
        costo_estimado:      this.order.costo_estimado !== null
          ? Number(this.order.costo_estimado) : null,
        kilometraje_ingreso: this.order.kilometraje_ingreso,
        descripcion:         this.order.descripcion,
        notas:               this.order.notas ?? ''
      });
    }
    this.loadVehicles();
  }

  private loadVehicles(): void {
    this.vehiclesService.list({ per_page: 100, sort_by: 'placa', sort_dir: 'asc' })
      .subscribe({
        next: (res) => {
          const list: VehicleOption[] = res.data.map(v => ({
            id: v.id, placa: v.placa, marca: v.marca, modelo: v.modelo, anio: v.anio
          }));

          // Si estamos editando y el vehículo no está en la lista cargada, lo añadimos
          const currentId = this.order?.vehiculo_id;
          if (currentId && !list.some(v => v.id === currentId) && this.order?.vehiculo) {
            list.unshift({
              id: currentId,
              placa:  this.order.vehiculo.placa,
              marca:  this.order.vehiculo.marca,
              modelo: this.order.vehiculo.modelo,
              anio:   this.order.vehiculo.anio
            });
          }

          this.vehicles.set(list);
          this.vehiclesReady.set(true);
        },
        error: () => {
          this.vehiclesError.set('No se pudieron cargar los vehículos.');
          this.vehiclesReady.set(true);
        }
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

    const payload: OrderPayload = {
      vehiculo_id:         Number(raw.vehiculo_id),
      tipo:                raw.tipo,
      prioridad:           raw.prioridad,
      descripcion:         raw.descripcion.trim(),
      notas:               raw.notas ? raw.notas.trim() : null,
      fecha_programada:    raw.fecha_programada || null,
      costo_estimado:      raw.costo_estimado !== null && raw.costo_estimado !== undefined
        ? Number(raw.costo_estimado) : null,
      kilometraje_ingreso: raw.kilometraje_ingreso !== null && raw.kilometraje_ingreso !== undefined
        ? Number(raw.kilometraje_ingreso) : null
    };

    this.submitting.set(true);

    const op$ = this.isEdit() && this.orderId !== null
      ? this.ordersService.update(this.orderId, payload)
      : this.ordersService.create(payload);

    op$.subscribe({
      next: (res) => {
        this.submitting.set(false);
        this.saved.emit(res.data);
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

  get vehiculo_id() { return this.form.controls.vehiculo_id; }
  get descripcion() { return this.form.controls.descripcion; }
  get notas()       { return this.form.controls.notas; }

  private extractError(err: any): string {
    const errors = err?.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      const first = Object.values(errors)[0];
      if (Array.isArray(first) && first.length) return first[0];
    }
    return err?.error?.message ?? 'No se pudo guardar la orden.';
  }
}