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

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  constructor(private api: ApiService, private router: Router, private session: SessionService) { }

  login() {
    if (!this.email || !this.password) {
      this.toastMessage = 'Todos los campos son obligatorios.';
      this.showToast('error');
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

      this.router.navigate(['/admin/dashboard']);
    }).catch((error) => {
      this.loading = false;
      this.toastMessage = "Error al iniciar sesión. Verifica tus datos";
      this.showToast('error');
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