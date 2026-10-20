import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/guards/auth-guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'login' },
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./features/auth/login/login').then((m) => m.LoginComponent)
  },
   {
    path: 'dashboard',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./layout/layout/layout').then((m) => m.LayoutComponent),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'vehicles' },
      {
        path: 'vehicles',
        loadComponent: () =>
          import('./features/vehicles/vehicles-list/vehicles-list')
            .then((m) => m.VehiclesListComponent),
        data: { mode: 'list' }
      },
      {
        path: 'due',
        loadComponent: () =>
          import('./features/vehicles/vehicles-list/vehicles-list')
            .then((m) => m.VehiclesListComponent),
        data: { mode: 'due' }
      }
    ]
  },
  { path: '**', redirectTo: 'login' }
];