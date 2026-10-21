import { CommonModule } from '@angular/common';
import { Component, EventEmitter, HostListener, Input, OnInit, Output, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Vehicle, VehiclePayload, VehicleStatus } from '../../../core/models/vehicle';
import { VehiclesService } from '../../../core/services/vehicles.service';

@Component({
  selector: 'app-vehicle-form-modal',
  standalone: true,                                    // ← ESTO ES LO QUE FALTA
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './vehicles-form-modal.html',
  styleUrl: './vehicles-form-modal.css'
})
export class VehicleFormModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly vehiclesService = inject(VehiclesService);

  @Input() vehicle: Vehicle | null = null;
  @Output() saved  = new EventEmitter<Vehicle>();
  @Output() closed = new EventEmitter<void>();

  readonly submitting   = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly isEdit       = signal(false);

  private vehicleId: number | null = null;
  readonly maxYear = new Date().getFullYear() + 1;

  readonly form = this.fb.nonNullable.group({
    placa:            ['', [Validators.required, Validators.maxLength(20)]],
    numero_economico: ['', [Validators.required, Validators.maxLength(30)]],
    marca:            ['', [Validators.required, Validators.maxLength(80)]],
    modelo:           ['', [Validators.required, Validators.maxLength(80)]],
    anio:             [new Date().getFullYear(), [Validators.required, Validators.min(1900), Validators.max(this.maxYear)]],
    odometro_actual:  [0, [Validators.required, Validators.min(0)]],
    estatus:          ['activo' as VehicleStatus, [Validators.required]],
    fecha_alta:       [new Date().toISOString().slice(0, 10)],
    notas:            ['']
  });

  ngOnInit(): void {
    if (this.vehicle) {
      this.isEdit.set(true);
      this.vehicleId = this.vehicle.id;
      this.form.patchValue({
        placa:            this.vehicle.placa,
        numero_economico: this.vehicle.numero_economico,
        marca:            this.vehicle.marca,
        modelo:           this.vehicle.modelo,
        anio:             this.vehicle.anio,
        odometro_actual:  this.vehicle.odometro_actual,
        estatus:          this.vehicle.estatus,
        fecha_alta:       this.vehicle.fecha_alta ?? '',
        notas:            this.vehicle.notas ?? ''
      });
    }
  }

  @HostListener('document:keydown.escape')
  onEscape(): void { if (!this.submitting()) this.closed.emit(); }

  submit(): void {
    this.errorMessage.set(null);
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }

    const raw = this.form.getRawValue();
    const payload: VehiclePayload = {
      placa:            raw.placa.toUpperCase().trim(),
      numero_economico: raw.numero_economico.trim(),
      marca:            raw.marca.trim(),
      modelo:           raw.modelo.trim(),
      anio:             Number(raw.anio),
      odometro_actual:  Number(raw.odometro_actual),
      estatus:          raw.estatus as VehicleStatus,
      fecha_alta:       raw.fecha_alta || null,
      notas:            raw.notas ? raw.notas.trim() : null
    };

    this.submitting.set(true);
    const op$ = this.isEdit() && this.vehicleId !== null
      ? this.vehiclesService.update(this.vehicleId, payload)
      : this.vehiclesService.create(payload);

    op$.subscribe({
      next:  (res: { data: Vehicle }) => {
        this.submitting.set(false);
        this.saved.emit(res.data);
      },
      error: (err: any) => {
        this.submitting.set(false);
        this.errorMessage.set(this.extractError(err));
      }
    });
  }

  cancel(): void { if (!this.submitting()) this.closed.emit(); }

  get placa()            { return this.form.controls.placa; }
  get numero_economico() { return this.form.controls.numero_economico; }
  get marca()            { return this.form.controls.marca; }
  get modelo()           { return this.form.controls.modelo; }
  get anio()             { return this.form.controls.anio; }
  get odometro_actual()  { return this.form.controls.odometro_actual; }

  private extractError(err: any): string {
    const errors = err?.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      const first = Object.values(errors)[0];
      if (Array.isArray(first) && first.length) return first[0];
    }
    return err?.error?.message ?? 'No se pudo guardar el vehiculo.';
  }
}