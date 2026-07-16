import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../../services/api.service';

@Component({
  selector: 'app-settings',
  standalone: false,
  templateUrl: './settings.component.html',
  styleUrl: './settings.component.scss'
})
export class SettingsComponent implements OnInit {
  token = localStorage.getItem('accessToken') ?? '';

  loading = false;
  saving = false;

  eventAddress = '';
  eventDate = '';
  eventTime = '';

  currentImageUrl: string | null = null;
  currentImageName = '';
  selectedImageFile: File | null = null;
  previewUrl: string | null = null;

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.loadSettings();
  }

  loadSettings(): void {
    this.loading = true;
    this.api.getSettings(this.token).then((res: any) => {
      const data = res.data.data;
      this.eventAddress = data.event_address ?? '';
      this.eventDate = data.event_date ?? '';
      this.eventTime = data.event_time ?? '';
      this.currentImageUrl = data.event_image_url ?? null;
      this.currentImageName = this.currentImageUrl ? this.currentImageUrl.split('/').pop()! : 'Sin logo asignado';
      this.loading = false;
    }).catch(() => {
      this.loading = false;
    });
  }

  onImageSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    if (!file) {
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      this.toastMessage = 'La imagen supera el tamaño máximo de 2 MB.';
      this.showToast('error');
      input.value = '';
      return;
    }

    this.selectedImageFile = file;
    this.previewUrl = URL.createObjectURL(file);
  }

  saveChanges(): void {
    if (!this.eventAddress.trim() || !this.eventDate || !this.eventTime) {
      this.toastMessage = 'Completa todos los campos de información general.';
      this.showToast('error');
      return;
    }

    this.saving = true;
    this.api.updateSettings(this.eventAddress, this.eventDate, this.eventTime, this.selectedImageFile, this.token)
      .then((res: any) => {
        const data = res.data.data;
        this.currentImageUrl = data.event_image_url ?? this.currentImageUrl;
        this.currentImageName = this.currentImageUrl ? this.currentImageUrl.split('/').pop()! : this.currentImageName;
        this.selectedImageFile = null;
        this.previewUrl = null;
        this.toastMessage = 'Configuración actualizada correctamente.';
        this.showToast('success');
        this.saving = false;
      }).catch(() => {
        this.toastMessage = 'Error al actualizar la configuración.';
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