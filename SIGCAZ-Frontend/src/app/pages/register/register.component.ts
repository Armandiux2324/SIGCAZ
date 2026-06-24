import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';

interface ParticipantForm {
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
  gender: string;
  shirtSize: string;
}

@Component({
  selector: 'app-register',
  standalone: false,
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss'
})
export class RegisterComponent implements OnInit {
  loading = false;
  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  dataToAdd = {
    originType: '',
    state: '',
    municipality: '',
    group: '',
    isFirstTime: true,
    participationCount: 0,
    attendanceType: '',
    participantCount: 1,
    accommodationType: '',
    lodging: '',
    stayDays: 1,
    transportMethod: '',
    folioDeliveryMethod: '',
    participants: [] as ParticipantForm[],
  };

  constructor(private api: ApiService) { }

  ngOnInit(): void {
  }

  addRegister() {
    this.loading = true;

    this.api.addRegister(
      this.dataToAdd.originType,
      this.dataToAdd.state,
      this.dataToAdd.municipality,
      this.dataToAdd.group,
      this.dataToAdd.isFirstTime,
      this.dataToAdd.participationCount,
      this.dataToAdd.attendanceType,
      this.dataToAdd.participantCount,
      this.dataToAdd.accommodationType,
      this.dataToAdd.lodging,
      this.dataToAdd.stayDays,
      this.dataToAdd.transportMethod,
      this.dataToAdd.folioDeliveryMethod,
      this.dataToAdd.participants
    ).subscribe({
      next: (data: any) => {
        this.toastMessage = 'Registro agregado exitosamente.';
        this.showToast('success');
        this.loading = false;

        this.resetForm();
      },
      error: (error: any) => {
        this.toastMessage = 'Error al agregar el registro.';
        this.showToast('error');
        this.loading = false;
      }
    });
  }

  resetForm() {
    this.dataToAdd = {
      originType: '',
      state: '',
      municipality: '',
      group: '',
      isFirstTime: true,
      participationCount: 0,
      attendanceType: '',
      participantCount: 1,
      accommodationType: '',
      lodging: '',
      stayDays: 1,
      transportMethod: '',
      folioDeliveryMethod: '',
      participants: [],
    };
  }

  showToast(type: 'success' | 'error') {
    this.toastType = type;
    this.showToastFlag = true;

    setTimeout(() => {
      this.showToastFlag = false;
    }, 3000);
  }
}
