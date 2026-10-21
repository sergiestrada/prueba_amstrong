import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  PaginatedResponse,
  Vehicle,
  VehicleFilters,
  VehiclePayload
} from '../models/vehicle';

@Injectable({ providedIn: 'root' })
export class VehiclesService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/vehicles`;

  private readonly _loading = signal(false);
  private readonly _items = signal<Vehicle[]>([]);
  private readonly _meta = signal<PaginatedResponse<Vehicle>['meta'] | null>(null);

  readonly loading = this._loading.asReadonly();
  readonly items = this._items.asReadonly();
  readonly meta = this._meta.asReadonly();

  list(filters: VehicleFilters = {}): Observable<PaginatedResponse<Vehicle>> {
    this._loading.set(true);
    return this.http
      .get<PaginatedResponse<Vehicle>>(this.base, { params: this.toParams(filters) })
      .pipe(tap({
        next: (res) => {
          this._items.set(res.data);
          this._meta.set(res.meta);
          this._loading.set(false);
        },
        error: () => this._loading.set(false)
      }));
  }

  dueForService(params: { per_page?: number; page?: number } = {}) {
    this._loading.set(true);
    let hp = new HttpParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null) hp = hp.set(k, String(v));
    });
    return this.http
      .get<PaginatedResponse<Vehicle>>(`${this.base}/due-for-service`, { params: hp })
      .pipe(tap({
        next: (res) => {
          this._items.set(res.data);
          this._meta.set(res.meta);
          this._loading.set(false);
        },
        error: () => this._loading.set(false)
      }));
  }

  create(payload: VehiclePayload): Observable<{ data: Vehicle }> {
    return this.http.post<{ data: Vehicle }>(this.base, payload);
  }

  update(id: number, payload: Partial<VehiclePayload>): Observable<{ data: Vehicle }> {
    return this.http.put<{ data: Vehicle }>(`${this.base}/${id}`, payload);
  }

  remove(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.base}/${id}`);
  }

  private toParams(filters: VehicleFilters): HttpParams {
    let hp = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        hp = hp.set(key, String(value));
      }
    });
    return hp;
  }
}