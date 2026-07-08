// src/app/publico/login/login.page.ts
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false
})
export class LoginPage {

  email    = '';
  password = '';
  cargando = false;
  error    = '';
  mostrarPassword = false;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {
    // Si ya está autenticado, saltar directo a asistencia
    if (this.authService.estaAutenticado()) {
      this.router.navigate(['/asistencia'], { replaceUrl: true });
    }
  }

  login(): void {
    if (!this.email || !this.password) {
      this.error = 'Ingresa tu correo y contraseña.';
      return;
    }

    this.cargando = true;
    this.error    = '';

    this.authService.login(this.email, this.password).subscribe({
      next: () => {
        this.cargando = false;
        this.router.navigate(['/asistencia'], { replaceUrl: true });
      },
      error: (err) => {
        this.cargando = false;
        if (err.status === 401) {
          this.error = 'Correo o contraseña incorrectos.';
        } else if (err.status === 0) {
          this.error = 'Sin conexión al servidor.';
        } else {
          this.error = err.error?.message ?? 'Error al iniciar sesión.';
        }
      }
    });
  }

  togglePassword(): void {
    this.mostrarPassword = !this.mostrarPassword;
  }
}
