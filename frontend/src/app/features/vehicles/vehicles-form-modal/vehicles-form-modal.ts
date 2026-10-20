import { CommonModule } from '@angular/common';
import {
  Component,
  EventEmitter,
  HostListener,
  Input,
  OnInit,
  Output,
  inject,
  signal
} from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  ValidatorFn,
  Validators
} from '@angular/forms';
import { Vehicle, VehiclePayload } from '../../../core/models/vehicle';
import { VehiclesService } from '../../../core/services/vehicles.service';

function maxDateValidator(max: Date): ValidatorFn {
  return (control: AbstractControl): ValidationErrors | null => {
    if (!control.value) return null;
    return new Date(control.value) > max ? { maxDate: { max: max.toISOString() } } : null;
  };
}

function serviceDatesOrder(group: AbstractControl): ValidationErrors | null {
  const ultimo = group.get('fecha_ultimo_servicio')?.value;
  const proximo = group.get('fecha_proximo_servicio')?.value;
  if (!ultimo || !proximo) return null;
  return new Date(proximo) < new Date(ultimo) ? { datesOrder: true } : null;
}

@Component({
  selector: 'app-vehicle-form-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './vehicles-form-modal.html',
  styleUrl: './vehicles-form-modal.css'
})
export class VehicleFormModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly vehiclesService = inject(VehiclesService);

  @Input() vehicle: Vehicle | null = null;
  @Output() saved = new EventEmitter<Vehicle>();
  @Output() closed = new EventEmitter<void>();

  readonly submitting = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly isEdit = signal(false);
  private vehicleId: number | null = null;

  readonly form = this.fb.nonNullable.group(
    {
      placa: ['', [Validators.required, Validators.maxLength(20)]],
      marca: ['', [Validators.required, Validators.maxLength(80)]],
      modelo: ['', [Validators.required, Validators.maxLength(80)]],
      anio: [new Date().getFullYear(), [Validators.required, Validators.min(1900), Validators.max(new Date().getFullYear() + 1)]],
      color: [''],
      vin: ['', [Validators.maxLength(17)]],
      tipo: ['sedan' as const, [Validators.required]],
      kilometraje: [0, [Validators.required, Validators.min(0)]],
      fecha_ultimo_servicio: ['', [maxDateValidator(new Date())]],
      fecha_proximo_servicio: [''],
      estado: ['activo' as const, [Validators.required]],
      activo: [true],
      notas: ['']
    },
    { validators: [serviceDatesOrder] }
  );

  ngOnInit(): void {
    if (this.vehicle) {
      this.isEdit.set(true);
      this.vehicleId = this.vehicle.id;
      this.form.patchValue({
        placa: this.vehicle.placa,
        marca: this.vehicle.marca,
        modelo: this.vehicle.modelo,
        anio: this.vehicle.anio,
        color: this.vehicle.color ?? '',
        vin: this.vehicle.vin ?? '',
        tipo: this.vehicle.tipo as any,
        kilometraje: this.vehicle.kilometraje,
        fecha_ultimo_servicio: this.vehicle.fecha_ultimo_servicio ?? '',
        fecha_proximo_servicio: this.vehicle.fecha_proximo_servicio ?? '',
        estado: this.vehicle.estado as any,
        activo: this.vehicle.activo,
        notas: this.vehicle.notas ?? ''
      });
    }
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
    const payload: VehiclePayload = {
      placa: raw.placa.toUpperCase().trim(),
      marca: raw.marca.trim(),
      modelo: raw.modelo.trim(),
      anio: Number(raw.anio),
      color: raw.color ? raw.color.trim() : null,
      vin: raw.vin ? raw.vin.toUpperCase().trim() : null,
      tipo: raw.tipo as any,
      kilometraje: Number(raw.kilometraje),
      fecha_ultimo_servicio: raw.fecha_ultimo_servicio || null,
      fecha_proximo_servicio: raw.fecha_proximo_servicio || null,
      estado: raw.estado as any,
      activo: raw.activo,
      notas: raw.notas ? raw.notas.trim() : null
    };

    this.submitting.set(true);

    const op$ = this.isEdit() && this.vehicleId !== null
      ? this.vehiclesService.update(this.vehicleId, payload)
      : this.vehiclesService.create(payload);

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

  get placa() { return this.form.controls.placa; }
  get marca() { return this.form.controls.marca; }
  get modelo() { return this.form.controls.modelo; }
  get anio() { return this.form.controls.anio; }
  get vin() { return this.form.controls.vin; }
  get kilometraje() { return this.form.controls.kilometraje; }
  get notas() { return this.form.controls.notas; }

  private extractError(err: any): string {
    const errors = err?.error?.errors as Record<string, string[]> | undefined;
    if (errors) {
      const first = Object.values(errors)[0];
      if (Array.isArray(first) && first.length) return first[0];
    }
    return err?.error?.message ?? 'No se pudo guardar el vehículo.';
  }
}