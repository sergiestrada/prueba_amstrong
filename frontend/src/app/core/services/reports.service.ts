import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  CostsReportFilters,
  CostsReportResponse
} from '../models/report';

@Injectable({ providedIn: 'root' })
export class ReportsService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiUrl}/v1/reports`;

  private readonly _loading  = signal(false);
  private readonly _report   = signal<CostsReportResponse['data'] | null>(null);

  readonly loading = this._loading.asReadonly();
  readonly report  = this._report.asReadonly();

  getCosts(filters: CostsReportFilters = {}): Observable<CostsReportResponse> {
    this._loading.set(true);
    return this.http
      .get<CostsReportResponse>(`${this.base}/costs`, { params: this.toParams(filters) })
      .pipe(tap({
        next: (res) => {
          this._report.set(res.data);
          this._loading.set(false);
        },
        error: () => this._loading.set(false)
      }));
  }

  clear(): void { this._report.set(null); }

  private toParams(filters: CostsReportFilters): HttpParams {
    let hp = new HttpParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        hp = hp.set(key, String(value));
      }
    });
    return hp;
  }
}