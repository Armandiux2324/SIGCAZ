import { NgModule } from '@angular/core';
import { PreloadAllModules, PreloadingStrategy, RouterModule, Routes } from '@angular/router';

const routes: Routes = [
  {
    path: 'home',
    loadChildren: () =>
      import('./pages/home/home.module').then(m => m.HomeModule)
  },
  {
    path: 'register',
    loadChildren: () =>
      import('./pages/register/register.module').then(m => m.RegisterModule)
  },
  {
    path: 'search-register',
    loadChildren: () =>
      import('./pages/search-register/search-register.module').then(m => m.SearchRegisterModule)
  },
  {
    path: 'login',
    loadChildren: () =>
      import('./pages/admin-staff/login/login.module').then(m => m.LoginModule)
  },
  {
    path: 'dashboard',
    loadChildren: () =>
      import('./pages/admin-staff/dashboard/dashboard.module').then(m => m.DashboardModule)
  },
  {
    path: 'registers',
    loadChildren: () =>
      import('./pages/admin-staff/registers/registers.module').then(m => m.RegistersModule)
  },
  {
    path: 'stats',
    loadChildren: () =>
      import('./pages/admin-staff/stats/stats.module').then(m => m.StatsModule)
  },
  {
    path: 'users',
    loadChildren: () =>
      import('./pages/admin/users/users.module').then(m => m.UsersModule)
  },
  {
    path: 'settings',
    loadChildren: () =>
      import('./pages/admin/settings/settings.module').then(m => m.SettingsModule)
  },
  { path: '', redirectTo: 'home', pathMatch: 'full' },
  { path: '**', redirectTo: 'home' }
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {preloadingStrategy: PreloadAllModules})],
  exports: [RouterModule]
})
export class AppRoutingModule { }
