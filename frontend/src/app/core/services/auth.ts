import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, finalize, tap } from 'rxjs';

// Ruta correcta: services/ -> core/ -> app/ -> src/ -> environments/
import { environment } from '../../environments/environment';
import { LoginPayload, LoginResponse, MeResponse, User } from '../models/auth';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  private readonly _token = signal<string | null>(this.readToken());
  private readonly _user = signal<User | null>(this.readUser());

  readonly token = this._token.asReadonly();
  readonly user = this._user.asReadonly();
  readonly isAuthenticated = computed(() => !!this._token());

  login(payload: LoginPayload, recordar: boolean): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, payload).pipe(
      tap((res) => this.persist(res.token, res.user, recordar))
    );
  }

  me(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${this.apiUrl}/me`).pipe(
      tap((res) => {
        this._user.set(res.user);
        this.activeStorage().setItem(USER_KEY, JSON.stringify(res.user));
      })
    );
  }

  logout(): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.apiUrl}/logout`, {}).pipe(
      finalize(() => this.clearSession())
    );
  }

  clearSession(): void {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(USER_KEY);
    this._token.set(null);
    this._user.set(null);
  }

  private persist(token: string, user: User, recordar: boolean): void {
    const storage = recordar ? localStorage : sessionStorage;

    // Limpia la otra para no dejar restos
    (recordar ? sessionStorage : localStorage).removeItem(TOKEN_KEY);
    (recordar ? sessionStorage : localStorage).removeItem(USER_KEY);

    storage.setItem(TOKEN_KEY, token);
    storage.setItem(USER_KEY, JSON.stringify(user));

    this._token.set(token);
    this._user.set(user);
  }

  private readToken(): string | null {
    return localStorage.getItem(TOKEN_KEY) ?? sessionStorage.getItem(TOKEN_KEY);
  }

  private readUser(): User | null {
    const raw =
      localStorage.getItem(USER_KEY) ?? sessionStorage.getItem(USER_KEY);
    try {
      return raw ? (JSON.parse(raw) as User) : null;
    } catch {
      return null;
    }
  }

  /** Devuelve el Storage donde vive el token actual (local o session). */
  private activeStorage(): Storage {
    return localStorage.getItem(TOKEN_KEY) ? localStorage : sessionStorage;
  }
}