import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';

@Component({
  selector: 'app-register',
  standalone: false,
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss'
})
export class RegisterComponent implements OnInit {
  registerForm!: FormGroup;
  loading = false;
  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;
  page = 1;

  registrationType: 'individual' | 'group' = 'individual';
  activeParticipantIndex = 0;

  states: string[] = [
    'Aguascalientes','Baja California','Baja California Sur','Campeche','Chiapas',
    'Chihuahua','Ciudad de México','Coahuila','Colima','Durango','Guanajuato',
    'Guerrero','Hidalgo','Jalisco','Estado de México','Michoacán','Morelos','Nayarit',
    'Nuevo León','Oaxaca','Puebla','Querétaro','Quintana Roo','San Luis Potosí',
    'Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas',
  ];

  constructor(private api: ApiService, private fb: FormBuilder) {}

  ngOnInit(): void {
    this.buildForm();
  }

  buildForm(): void {
    this.registerForm = this.fb.group({
      origin_type: ['', Validators.required],
      state: ['', Validators.required],
      municipality: ['', Validators.required],
      group: ['', Validators.required],
      accommodation_type: ['', Validators.required],
      lodging: [''],
      stay_days: [1, [Validators.required, Validators.min(1)]],
      transport_method: ['', Validators.required],
      folio_delivery_method: ['email', Validators.required],

      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      phone: ['', [Validators.required, Validators.pattern(/^\d{10}$/)]],
      email: ['', [Validators.required, Validators.email]],
      gender: ['', Validators.required],
      shirt_size: ['', Validators.required],
      is_first_time: [true, Validators.required],
      participation_count: [0],
      travel_companions_count: [0, [Validators.required, Validators.min(0)]],

      participants: this.fb.array([this.createParticipant(), this.createParticipant()]),
    });
  }

  createParticipant(): FormGroup {
    return this.fb.group({
      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      phone: ['', [Validators.required, Validators.pattern(/^\d{10}$/)]],
      email: ['', [Validators.required, Validators.email]],
      gender: ['', Validators.required],
      shirt_size: ['', Validators.required],
      is_first_time: [true, Validators.required],
      participation_count: [0],
      travel_companions_count: [0, [Validators.required, Validators.min(0)]],
    });
  }

  get participants(): FormArray {
    return this.registerForm.get('participants') as FormArray;
  }

  getParticipantGroup(index: number): FormGroup {
    return this.participants.at(index) as FormGroup;
  }

  getParticipantControls(index: number) {
    return this.getParticipantGroup(index).controls;
  }

  get p() {
    return this.getParticipantControls(this.activeParticipantIndex);
  }

  setRegistrationType(type: 'individual' | 'group'): void {
    this.registrationType = type;
    this.page = 1;
    this.activeParticipantIndex = 0;

    if (type === 'group' && this.participants.length < 2) {
      this.addParticipant();
    }
  }

  addParticipant(): void {
    if (this.participants.length >= 20) return;
    this.participants.push(this.createParticipant());
    this.activeParticipantIndex = this.participants.length - 1;
  }

  removeParticipant(): void {
    if (this.participants.length <= 2) return;
    this.participants.removeAt(this.participants.length - 1);
    if (this.activeParticipantIndex > this.participants.length - 1) {
      this.activeParticipantIndex = this.participants.length - 1;
    }
  }

  setActiveParticipant(index: number): void {
    this.activeParticipantIndex = index;
  }

  get f() {
    return this.registerForm.controls;
  }

  private page1Fields = [
    'first_name','last_name','phone','email',
    'gender','shirt_size','is_first_time','participation_count','travel_companions_count',
  ];

  goToPage2(): void {
    if (this.registrationType === 'individual') {
      const page1Invalid = this.page1Fields.some(field => {
        const ctrl = this.registerForm.get(field);
        ctrl?.markAsTouched();
        return ctrl?.invalid;
      });

      if (page1Invalid) return;
    } else {
      let hasInvalid = false;
      this.participants.controls.forEach((ctrl, index) => {
        ctrl.markAllAsTouched();
        if (ctrl.invalid && !hasInvalid) {
          hasInvalid = true;
          this.activeParticipantIndex = index;
        }
      });

      if (hasInvalid) return;
    }

    this.page = 2;
  }

  goToPage1(): void {
    this.page = 1;
  }

  private mapGender(val: string): string {
    return val === 'Masculino' ? 'male' : 'female';
  }

  private mapOriginType(val: string): string {
    return val === 'Nacional' ? 'national' : 'state';
  }

  private mapAccommodationType(val: string): string {
    const map: Record<string, string> = {
      'Airbnb': 'airbnb',
      'Hotel': 'hotel',
      'Hostal': 'own_home',
      'Casa Propia / Familiar': 'family_or_friends',
    };
    return map[val] ?? val;
  }

  private mapTransportMethod(val: string): string {
    const map: Record<string, string> = {
      'Automóvil': 'car',
      'Autobús': 'bus',
      'Avión': 'airplane',
    };
    return map[val] ?? val;
  }

  addRegister(): void {
    const groupFieldsInvalid = ['origin_type','state','municipality','group','accommodation_type','stay_days','transport_method']
      .some(field => this.registerForm.get(field)?.invalid);

    if (this.registrationType === 'individual' && this.registerForm.invalid) {
      this.registerForm.markAllAsTouched();
      const page1Invalid = this.page1Fields.some(f => this.registerForm.get(f)?.invalid);
      if (page1Invalid) this.page = 1;
      return;
    }

    if (this.registrationType === 'group' && (this.participants.invalid || groupFieldsInvalid)) {
      this.registerForm.markAllAsTouched();
      this.participants.markAllAsTouched();
      if (this.participants.invalid) this.page = 1;
      return;
    }

    this.loading = true;
    const f = this.registerForm.value;

    const members = this.registrationType === 'individual'
      ? [{
          firstName: f.first_name,
          lastName: f.last_name,
          phone: f.phone,
          email: f.email,
          gender: this.mapGender(f.gender),
          shirtSize: f.shirt_size,
          isFirstTime: f.is_first_time,
          participationCount: f.is_first_time ? Number(f.participation_count) : 0,
          travelCompanionsCount: Number(f.travel_companions_count),
        }]
      : f.participants.map((m: any) => ({
          firstName: m.first_name,
          lastName: m.last_name,
          phone: m.phone,
          email: m.email,
          gender: this.mapGender(m.gender),
          shirtSize: m.shirt_size,
          isFirstTime: m.is_first_time,
          participationCount: m.is_first_time ? Number(m.participation_count) : 0,
          travelCompanionsCount: Number(m.travel_companions_count),
        }));

    this.api.addRegister(
      this.mapOriginType(f.origin_type),
      f.state,
      f.municipality,
      f.group,
      this.registrationType === 'individual' ? 'alone' : 'group',
      members.length,
      this.mapAccommodationType(f.accommodation_type),
      f.lodging,
      Number(f.stay_days),
      this.mapTransportMethod(f.transport_method),
      f.folio_delivery_method,
      members,
    ).then(() => {
      this.toastMessage = 'Registro completado. Recibirás un correo de confirmación.';
      this.showToast('success');
      this.loading = false;
      this.resetForm();
    }).catch((error: any) => {
      const msg = error?.response?.data?.message ?? 'Error al enviar el registro.';
      this.toastMessage = msg;
      this.showToast('error');
      this.loading = false;
    });
  }

  resetForm(): void {
    this.page = 1;
    this.registrationType = 'individual';
    this.activeParticipantIndex = 0;
    this.buildForm();
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }
}