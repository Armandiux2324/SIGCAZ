import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './interceptors/auth.guard';

const routes: Routes = [
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full'
  },
  {
    path: 'login',
    loadChildren: () =>
      import('./publico/login/login.module').then(m => m.LoginPageModule)
  },
  {
    path: 'asistencia',
    canActivate: [AuthGuard],  
    loadChildren: () =>
      import('./publico/asistencia/asistencia.module').then(m => m.AsistenciaPageModule)
  },
  {
    path: 'historial',
    canActivate: [AuthGuard],  
    loadChildren: () =>
      import('./publico/historial/historial.module').then(m => m.HistorialPageModule)
  },
  {
    path: '**',
    redirectTo: 'login'
  },

];

@NgModule({
  imports: [RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })],
  exports: [RouterModule]
})
export class AppRoutingModule {}
