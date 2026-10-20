
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface LoginPayload {
  correo: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user?: {
    id: number;
    nombre: string;
    correo: string;
    rol?: string;
  };
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private apiUrl = '/api/v1';
  private tokenKey = 'flotilla_access_token';

  login(
    payload: LoginPayload,
    recordar: boolean
  ): Observable<LoginResponse> {
    return this.http
      .post<LoginResponse>(
        `${this.apiUrl}/login`,
        payload
      )
      .pipe(
        tap(response => {
          if (!response.token) {
            throw new Error('La API no devolvió el token.');
          }

          if (recordar) {
            localStorage.setItem(this.tokenKey, response.token);
            sessionStorage.removeItem(this.tokenKey);
          } else {
            sessionStorage.setItem(this.tokenKey, response.token);
            localStorage.removeItem(this.tokenKey);
          }
        })
      );
  }

  getToken(): string | null {
    return sessionStorage.getItem(this.tokenKey)
      ?? localStorage.getItem(this.tokenKey);
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  clearToken(): void {
    localStorage.removeItem(this.tokenKey);
    sessionStorage.removeItem(this.tokenKey);
  }

  logout(): Observable<unknown> {
    return this.http.post(
      `${this.apiUrl}/logout`,
      {}
    ).pipe(
      tap(() => this.clearToken())
    );
  }
}
