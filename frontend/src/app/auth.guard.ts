import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from './auth.service';

// Solo deja entrar al Admin a quien tenga token y rol Administrador.
export const adminGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  if (auth.token && auth.esAdmin()) {
    return true;
  }
  return router.createUrlTree(['/login']);
};