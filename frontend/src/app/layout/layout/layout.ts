import { CommonModule } from '@angular/common';
import { Component, HostListener, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../../core/services/auth';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './layout.html',
  styleUrl: './layout.css'
})
export class LayoutComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly user = this.auth.user;
  readonly collapsed = signal(false);
  readonly mobileOpen = signal(false);

  toggleSidebar(): void { this.collapsed.update(v => !v); }
  toggleMobile(): void { this.mobileOpen.update(v => !v); }
  closeMobile(): void { this.mobileOpen.set(false); }

  logout(): void {
    this.auth.logout().subscribe({
      next: () => this.router.navigateByUrl('/login'),
      error: () => this.router.navigateByUrl('/login')
    });
  }

  @HostListener('window:resize')
  onResize(): void {
    if (window.innerWidth > 900) this.mobileOpen.set(false);
  }
}