import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  Order,
  OrderFilters,
  OrderPayload,
  OrderStatusPayload,
  PaginatedResponse
} from '../models/order';

@Injectable({ providedIn: 'root' })
export class OrdersService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/orders`;

  private readonly _loading = signal(false);
  private readonly _items   = signal<Order[]>([]);
  private readonly _meta    = signal<PaginatedResponse<Order>['meta'] | null>(null);

  readonly loading = this._loading.asReadonly();
  readonly items   = this._items.asReadonly();
  readonly meta    = this._meta.asReadonly();

  list(filters: OrderFilters = {}): Observable<PaginatedResponse<Order>> {
    this._loading.set(true);
    return this.http
      .get<PaginatedResponse<Order>>(this.base, { params: this.toParams(filters) })
      .pipe(tap({
        next: (res) => {
          this._items.set(res.data);
          this._meta.set(res.meta);
          this._loading.set(false);
        },
        error: () => this._loading.set(false)
      }));
  }

  get(id: number): Observable<{ data: Order }> {
    return this.http.get<{ data: Order }>(`${this.base}/${id}`);
  }

  create(payload: OrderPayload): Observable<{ data: Order }> {
    return this.http.post<{ data: Order }>(this.base, payload);
  }

  update(id: number, payload: Partial<OrderPayload>): Observable<{ data: Order }> {
    return this.http.put<{ data: Order }>(`${this.base}/${id}`, payload);
  }

  changeStatus(id: number, payload: OrderStatusPayload): Observable<{ data: Order }> {
    return this.http.patch<{ data: Order }>(`${this.base}/${id}/status`, payload);
  }

  private toParams(filters: OrderFilters): HttpParams {
    let hp = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        hp = hp.set(key, String(value));
      }
    });
    return hp;
  }
}