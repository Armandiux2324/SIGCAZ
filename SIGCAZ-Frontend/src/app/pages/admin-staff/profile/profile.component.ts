import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../../services/api.service';
import { SessionService } from '../../../services/session.service';

@Component({
  selector: 'app-profile',
  standalone: false,
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss'
})
export class ProfileComponent implements OnInit {
  token = localStorage.getItem('accessToken') ?? '';

  loading = false;
  saving = false;

  userId: any = null;
  name = '';
  email = '';
  phone = '';
  role = '';
  memberSince = '';

  // Cambio de contraseña (opcional)
  password = '';
  confirmPassword = '';

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  constructor(private api: ApiService, private session: SessionService) {}

  ngOnInit(): void {
    this.loadProfile();
  }

  loadProfile(): void {
    this.loading = true;
    this.api.getUser(this.token).then((res: any) => {
      const user = res.data.data;
      this.userId = user.id;
      this.name = user.name ?? '';
      this.email = user.email ?? '';
      this.phone = user.phone ?? '';
      this.role = user.role ?? '';
      this.memberSince = user.created_at
        ? new Date(user.created_at).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })
        : '';
      this.loading = false;
    }).catch(() => {
      this.toastMessage = 'Error al cargar la información del perfil.';
      this.showToast('error');
      this.loading = false;
    });
  }

  get roleLabel(): string {
    return this.role === 'admin' ? 'Administrador' : 'Personal';
  }

  saveChanges(): void {
    if (!this.name.trim() || !this.email.trim()) {
      this.toastMessage = 'El nombre y el correo son obligatorios.';
      this.showToast('error');
      return;
    }

    if (this.password || this.confirmPassword) {
      if (this.password.length < 8) {
        this.toastMessage = 'La contraseña debe tener al menos 8 caracteres.';
        this.showToast('error');
        return;
      }
      if (this.password !== this.confirmPassword) {
        this.toastMessage = 'Las contraseñas no coinciden.';
        this.showToast('error');
        return;
      }
    }

    if (!this.userId) {
      return;
    }

    const trimmedName = this.name.trim();

    this.saving = true;
    this.api.updateUser(this.userId, trimmedName, this.email.trim(), this.phone.trim(), this.password || undefined, this.token)
      .then(() => {
        this.session.setName(trimmedName);
        this.toastMessage = 'Perfil actualizado correctamente.';
        this.showToast('success');
        this.password = '';
        this.confirmPassword = '';
        this.saving = false;
      }).catch((err: any) => {
        const message = err?.response?.data?.message ?? 'Error al actualizar el perfil.';
        this.toastMessage = message;
        this.showToast('error');
        this.saving = false;
      });
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }
}