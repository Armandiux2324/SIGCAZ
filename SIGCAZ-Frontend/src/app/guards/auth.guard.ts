import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { ApiService } from '../services/api.service';

// Protege las rutas bajo /admin: sin token no entra, y si el token ya no es
// válido (expiró, se revocó, etc.) lo manda a /login y limpia la sesión.
export const authGuard: CanActivateFn = () => {
  const router = inject(Router);
  const api = inject(ApiService);

  const token = localStorage.getItem('accessToken');

  if (!token) {
    router.navigate(['/login']);
    return false;
  }

  return api.getUser(token).then(() => true).catch(() => {
      localStorage.clear();
      router.navigate(['/login']);
      return false;
    });
};