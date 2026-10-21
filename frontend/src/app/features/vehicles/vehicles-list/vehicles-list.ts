import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { Vehicle, VehicleFilters, VehicleStatus } from '../../../core/models/vehicle';
import { AuthService } from '../../../core/services/auth';
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
  private readonly authService     = inject(AuthService);
  private readonly fb              = inject(FormBuilder);
  private readonly route           = inject(ActivatedRoute);

  readonly items   = this.vehiclesService.items;
  readonly meta    = this.vehiclesService.meta;
  readonly loading = this.vehiclesService.loading;
  readonly isAdmin = this.authService.isAdmin;

  readonly mode       = signal<Mode>('list');
  readonly modalOpen  = signal(false);
  readonly editing    = signal<Vehicle | null>(null);
  readonly deleting   = signal<Vehicle | null>(null);
  readonly feedback   = signal<{ type: 'success' | 'error'; text: string } | null>(null);

  readonly filters = this.fb.nonNullable.group({
    search:  [''],
    estatus: ['']
  });

  readonly pages = computed(() => {
    const m = this.meta();
    if (!m) return [] as number[];
    const start = Math.max(1, m.current_page - 2);
    const end   = Math.min(m.last_page, m.current_page + 2);
    const arr: number[] = [];
    for (let i = start; i <= end; i++) arr.push(i);
    return arr;
  });

  ngOnInit(): void {
    this.mode.set((this.route.snapshot.data['mode'] as Mode) ?? 'list');
    this.reload();

    this.filters.controls.search
      .valueChanges.pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => this.reload());
    this.filters.controls.estatus.valueChanges.subscribe(() => this.reload());
  }

  reload(page = 1): void {
    if (this.mode() === 'due') {
      this.vehiclesService.dueForService({ per_page: 15, page })
        .subscribe({ error: () => this.showFeedback('error', 'No se pudieron cargar los vehículos.') });
      return;
    }

    const v = this.filters.getRawValue();
    const filters: VehicleFilters = {
      page, per_page: 15, sort_by: 'created_at', sort_dir: 'desc'
    };
    if (v.search)  filters.search  = v.search;
    if (v.estatus) filters.estatus = v.estatus as VehicleStatus;

    this.vehiclesService.list(filters).subscribe({
      error: () => this.showFeedback('error', 'No se pudieron cargar los vehículos.')
    });
  }

  openCreate(): void { this.editing.set(null); this.modalOpen.set(true); }
  openEdit(v: Vehicle): void { this.editing.set(v); this.modalOpen.set(true); }
  closeModal(): void { this.modalOpen.set(false); this.editing.set(null); }

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
        this.showFeedback('success', 'Vehículo dado de baja.');
      },
      error: () => this.showFeedback('error', 'No se pudo dar de baja el vehículo.')
    });
  }

  goToPage(p: number): void {
    const m = this.meta();
    if (!m || p < 1 || p > m.last_page) return;
    this.reload(p);
  }

  stateBadge(estatus: VehicleStatus): { text: string; cls: string } {
    switch (estatus) {
      case 'activo':    return { text: 'Activo',     cls: 'badge-green'  };
      case 'en_taller': return { text: 'En taller',  cls: 'badge-yellow' };
      case 'baja':      return { text: 'Baja',       cls: 'badge-gray'   };
    }
  }

  private showFeedback(type: 'success' | 'error', text: string): void {
    this.feedback.set({ type, text });
    setTimeout(() => this.feedback.set(null), 3200);
  }
}