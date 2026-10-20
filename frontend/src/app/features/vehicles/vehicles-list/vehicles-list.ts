import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import {
  Vehicle,
  VehicleFilters,
  VehiclePayload,
  VehicleStatus,
  VehicleType
} from '../../../core/models/vehicle';
import { VehiclesService } from '../../../core/services/vehicles.service';
import { VehicleFormModalComponent } from '../vehicles-form-modal/vehicles-form-modal';

type Mode = 'list' | 'due';

@Component({
  selector: 'app-vehicles-list',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, VehicleFormModalComponent],
  templateUrl: './vehicles-list.html',
  styleUrl: './vehicles-list.css'
})
export class VehiclesListComponent implements OnInit {
  private readonly vehiclesService = inject(VehiclesService);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);

  readonly items = this.vehiclesService.items;
  readonly meta = this.vehiclesService.meta;
  readonly loading = this.vehiclesService.loading;

  readonly mode = signal<Mode>('list');
  readonly modalOpen = signal(false);
  readonly editingVehicle = signal<Vehicle | null>(null);
  readonly deleting = signal<Vehicle | null>(null);
  readonly feedback = signal<{ type: 'success' | 'error'; text: string } | null>(null);

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    estado: [''],
    tipo: [''],
    activo: ['']
  });

  readonly pages = computed(() => {
    const m = this.meta();
    if (!m) return [] as number[];
    const total = m.last_page;
    const current = m.current_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(total, current + 2);
    const arr: number[] = [];
    for (let i = start; i <= end; i++) arr.push(i);
    return arr;
  });

  ngOnInit(): void {
    const dataMode = (this.route.snapshot.data['mode'] as Mode) ?? 'list';
    this.mode.set(dataMode);
    this.reload();

    this.filters.controls.search.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => this.reload());

    this.filters.controls.estado.valueChanges.subscribe(() => this.reload());
    this.filters.controls.tipo.valueChanges.subscribe(() => this.reload());
    this.filters.controls.activo.valueChanges.subscribe(() => this.reload());
  }

  reload(page = 1): void {
    if (this.mode() === 'due') {
      this.vehiclesService
        .dueForService({ days: 7, per_page: 15, page })
        .subscribe({ error: () => this.showFeedback('error', 'No se pudieron cargar los vehículos.') });
      return;
    }

    const v = this.filters.getRawValue();
    const filters: VehicleFilters = {
      page,
      per_page: 15,
      sort_by: 'created_at',
      sort_dir: 'desc'
    };
    if (v.search) filters.search = v.search;
    if (v.estado) filters.estado = v.estado as VehicleStatus;
    if (v.tipo) filters.tipo = v.tipo as VehicleType;
    if (v.activo) filters.activo = v.activo === '1';

    this.vehiclesService.list(filters).subscribe({
      error: () => this.showFeedback('error', 'No se pudieron cargar los vehículos.')
    });
  }

  openCreate(): void {
    this.editingVehicle.set(null);
    this.modalOpen.set(true);
  }

  openEdit(v: Vehicle): void {
    this.editingVehicle.set(v);
    this.modalOpen.set(true);
  }

  closeModal(): void {
    this.modalOpen.set(false);
    this.editingVehicle.set(null);
  }

  onSaved(_: Vehicle, isEdit: boolean): void {
    this.closeModal();
    this.reload();
    this.showFeedback('success', isEdit ? 'Vehículo actualizado.' : 'Vehículo registrado.');
  }

  confirmDelete(v: Vehicle): void { this.deleting.set(v); }
  cancelDelete(): void { this.deleting.set(null); }

  doDelete(): void {
    const v = this.deleting();
    if (!v) return;
    this.vehiclesService.remove(v.id).subscribe({
      next: () => {
        this.deleting.set(null);
        this.reload();
        this.showFeedback('success', 'Vehículo eliminado.');
      },
      error: () => this.showFeedback('error', 'No se pudo eliminar el vehículo.')
    });
  }

  goToPage(page: number): void {
    const m = this.meta();
    if (!m || page < 1 || page > m.last_page) return;
    this.reload(page);
  }

  stateBadge(estado: VehicleStatus): { text: string; class: string } {
    switch (estado) {
      case 'activo': return { text: 'Activo', class: 'badge-green' };
      case 'mantenimiento': return { text: 'Mantenimiento', class: 'badge-yellow' };
      case 'inactivo': return { text: 'Inactivo', class: 'badge-gray' };
    }
  }

  serviceBadge(v: Vehicle): { text: string; class: string } {
    if (v.dias_para_servicio === null) {
      return { text: 'Sin fecha', class: 'badge-gray' };
    }
    if (v.dias_para_servicio < 0) {
      return { text: `Vencido ${Math.abs(v.dias_para_servicio)}d`, class: 'badge-red' };
    }
    if (v.dias_para_servicio <= 7) {
      return { text: `${v.dias_para_servicio}d`, class: 'badge-yellow' };
    }
    return { text: `${v.dias_para_servicio}d`, class: 'badge-green' };
  }

  private showFeedback(type: 'success' | 'error', text: string): void {
    this.feedback.set({ type, text });
    setTimeout(() => this.feedback.set(null), 3200);
  }
}