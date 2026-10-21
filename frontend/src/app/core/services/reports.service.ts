import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  CostsReportFilters,
  CostsReportResponse,
} from '../models/report';

@Injectable({ providedIn: 'root' })
export class ReportsService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/v1/reports`;

  private readonly _loading = signal(false);
  private readonly _report = signal<CostsReportResponse['data'] | null>(null);
  private readonly _error = signal<string | null>(null);

  readonly loading = this._loading.asReadonly();
  readonly report = this._report.asReadonly();
  readonly error = this._error.asReadonly();

  getCosts(filters: CostsReportFilters = {}): Observable<CostsReportResponse> {
    this._loading.set(true);
    this._error.set(null);

    return this.http
      .get<CostsReportResponse>(`${this.base}/costs`, {
        params: this.toParams(filters),
      })
      .pipe(
        tap({
          next: (res) => {
            // Defensa: si el backend no envía `data`, no crasheamos el template
            this._report.set(res?.data ?? null);
            this._loading.set(false);
          },
          error: (err) => {
            const msg =
              err?.error?.message ??
              err?.error?.errors?.to?.[0] ??
              err?.error?.errors?.from?.[0] ??
              err?.message ??
              `Error ${err?.status ?? ''} al generar el reporte`;
            this._error.set(msg);
            this._report.set(null);
            this._loading.set(false);
            // Útil para depurar en DevTools → Console
            console.error('[ReportsService] getCosts error:', err);
          },
        })
      );
  }

  clear(): void {
    this._report.set(null);
    this._error.set(null);
  }

  private toParams(filters: CostsReportFilters): HttpParams {
    let hp = new HttpParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;

      // Laravel `boolean` acepta 1/0/"1"/"0", NO "true"/"false".
      if (typeof value === 'boolean') {
        if (value) hp = hp.set(key, '1');
        return; // si es false, no se envía (backend default = false)
      }

      hp = hp.set(key, String(value));
    });

    return hp;
  }
}