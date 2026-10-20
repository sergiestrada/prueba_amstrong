import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  template: `
    <main class="dashboard">
      <header>
        <h1>Panel</h1>
        <button type="button" (click)="logout()">Cerrar sesión</button>
      </header>

      @if (auth.user(); as u) {
        <section class="card">
          <h2>Hola, {{ u.nombre }} {{ u.apellido }}</h2>
          <p>Email: {{ u.email }}</p>
          <p>Rol: {{ u.rol ?? 'Sin rol' }}</p>
        </section>
      }
    </main>
  `,
  styles: [`
    .dashboard { max-width: 720px; margin: 40px auto; padding: 24px; font-family: Inter, sans-serif; }
    header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    button { padding: 10px 16px; border: 0; border-radius: 8px; background: #174e82; color: #fff; cursor: pointer; }
    .card { border: 1px solid #e3e9f0; border-radius: 12px; padding: 20px; background: #fff; }
  `]
})
export class DashboardComponent {
  readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  logout(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigateByUrl('/login'),
      error: () => this.router.navigateByUrl('/login')
    });
  }
}