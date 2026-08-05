import { Component } from '@angular/core';
import { ApiService } from '../../../services/api.service';
import { SessionService } from '../../../services/session.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  standalone: false,
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  email = '';
  password = '';
  loading = false;

  showPassword = false;
  loginError = false;
  loginSuccess = false;

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  constructor(private api: ApiService, private router: Router, private session: SessionService) { }

  togglePasswordVisibility(): void {
    this.showPassword = !this.showPassword;
  }

  private triggerLoginError(): void {
    // Solo controla la animación/mensaje inline; no cambia la lógica de login.
    this.loginError = false;
    // Se reinicia en el siguiente tick para que la animación CSS vuelva a dispararse
    // aunque el error anterior siga visible (dos intentos fallidos seguidos).
    setTimeout(() => {
      this.loginError = true;
      setTimeout(() => { this.loginError = false; }, 4000);
    });
  }

  login() {
    if (!this.email || !this.password) {
      this.toastMessage = 'Todos los campos son obligatorios.';
      this.showToast('error');
      this.triggerLoginError();
      return;
    }

    this.loading = true;

    this.api.login(this.email, this.password).then(res => {
      this.loading = false;
      localStorage.setItem('userId', res.data.user.id);
      localStorage.setItem('role', res.data.user.role);
      localStorage.setItem('accessToken', res.data.access_token);
      this.session.setName(res.data.user.name);

      this.toastMessage = 'Inicio de sesión exitoso';
      this.showToast('success');
      this.loginSuccess = true;

      // Pequeña pausa para que la animación de éxito sea visible antes de navegar.
      setTimeout(() => {
        this.router.navigate(['/admin/dashboard']);
      }, 550);
    }).catch((error) => {
      this.loading = false;
      this.toastMessage = "Error al iniciar sesión. Verifica tus datos";
      this.showToast('error');
      this.triggerLoginError();
      console.log(error)
    })
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }

  backToHome() {
    this.router.navigate(['/home']);
  }

}