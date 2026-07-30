import { Component, OnInit, ViewChildren, QueryList, ElementRef } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ESTADOS, ESTADOS_MUNICIPIOS } from '../../data/mexico-estados';

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

  // ====== Estado de confirmación de registro ======
  // Controla si se muestra el formulario o la pantalla de confirmación,
  // ambos dentro del mismo RegisterComponent (sin nueva ruta/componente).
  registrationComplete = false;
  registrationResult: any = null;
  lastSubmissionType: 'individual' | 'group' = 'individual';
  private lastSubmissionCount = 0;

  @ViewChildren('tabRef') tabRefs!: QueryList<ElementRef<HTMLButtonElement>>;

  states: string[] = ESTADOS;

  // Municipios disponibles según el estado seleccionado
  municipalities: string[] = [];

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

    this.registerForm.get('state')?.valueChanges.subscribe((estado: string) => {
      this.municipalities = ESTADOS_MUNICIPIOS[estado] ?? [];
      this.registerForm.get('municipality')?.setValue('');
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
    this.setActiveParticipant(this.participants.length - 1);
  }

  removeParticipant(): void {
    if (this.participants.length <= 2) return;
    this.participants.removeAt(this.participants.length - 1);
    if (this.activeParticipantIndex > this.participants.length - 1) {
      this.setActiveParticipant(this.participants.length - 1);
    }
  }

  setActiveParticipant(index: number): void {
    if (index < 0 || index > this.participants.length - 1) return;
    this.activeParticipantIndex = index;
    this.scrollActiveTabIntoView();
  }

  previousParticipant(): void {
    if (this.activeParticipantIndex === 0) return;
    this.setActiveParticipant(this.activeParticipantIndex - 1);
  }

  nextParticipant(): void {
    if (this.activeParticipantIndex === this.participants.length - 1) return;
    this.setActiveParticipant(this.activeParticipantIndex + 1);
  }

  private scrollActiveTabIntoView(): void {
    setTimeout(() => {
      const tabs = this.tabRefs?.toArray();
      const el = tabs?.[this.activeParticipantIndex]?.nativeElement;
      el?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    });
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
          this.setActiveParticipant(index);
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

    if (this.registrationType === 'individual') {
      const individualFieldsInvalid =
        this.page1Fields.some(f => this.registerForm.get(f)?.invalid) || groupFieldsInvalid;

      if (individualFieldsInvalid) {
        this.registerForm.markAllAsTouched();
        const page1Invalid = this.page1Fields.some(f => this.registerForm.get(f)?.invalid);
        if (page1Invalid) this.page = 1;
        return;
      }
    }

    if (this.registrationType === 'group' && (this.participants.invalid || groupFieldsInvalid)) {
      this.registerForm.markAllAsTouched();
      this.participants.markAllAsTouched();
      if (this.participants.invalid) this.page = 1;
      return;
    }

    this.loading = true;
    const f = this.registerForm.value;

    const attendanceType = this.registrationType === 'group' ? 'accompanied' : Number(f.travel_companions_count ?? 0) > 0 ? 'accompanied' : 'alone';

    const members = this.registrationType === 'individual'
      ? [{
          firstName: f.first_name,
          lastName: f.last_name,
          phone: f.phone,
          email: f.email,
          gender: this.mapGender(f.gender),
          shirtSize: f.shirt_size,
          isFirstTime: f.is_first_time,
          participationCount: f.is_first_time ? 0 : Number(f.participation_count),
        }]
      : f.participants.map((m: any) => ({
          firstName: m.first_name,
          lastName: m.last_name,
          phone: m.phone,
          email: m.email,
          gender: this.mapGender(m.gender),
          shirtSize: m.shirt_size,
          isFirstTime: m.is_first_time,
          participationCount: m.is_first_time ? 0 : Number(m.participation_count),
        }));

    // Guardamos el tipo y la cantidad enviados, ya que resetForm() reinicia
    // registrationType y el formulario en cuanto el registro se confirma.
    this.lastSubmissionType = this.registrationType;
    this.lastSubmissionCount = members.length;

    this.api.addRegister(
      this.mapOriginType(f.origin_type),
      f.state,
      f.municipality,
      f.group,
      attendanceType,
      members.length,
      this.mapAccommodationType(f.accommodation_type),
      f.lodging,
      Number(f.stay_days),
      this.mapTransportMethod(f.transport_method),
      f.folio_delivery_method,
      members,
    ).then((res: any) => {
      // Reutilizamos la respuesta del backend tal cual venga (mismo contrato
      // que ya usan el resto de vistas: res.data.data), sin duplicar modelos.
      this.registrationResult = res?.data?.data ?? res?.data ?? null;
      this.registrationComplete = true;

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

  /** Vuelve a mostrar el formulario inicial sin recargar la página. */
  startNewRegistration(): void {
    this.registrationComplete = false;
    this.registrationResult = null;
  }

  /**
   * Reutiliza exactamente la misma lógica que el módulo "Consulta de Registro":
   * this.api.getReceiptUrl(folio, email) + window.open. Sin segunda implementación.
   */
  downloadReceipt(folio: string, email: string): void {
    if (!folio || folio === '—') return;
    const url = this.api.getReceiptUrl(folio, email);
    window.open(url, '_blank');
  }

  // ====== Datos derivados para la pantalla de confirmación ======
  get resultFolio(): string {
    return this.registrationResult?.folio
      ?? this.registrationResult?.participants?.[0]?.folio
      ?? '—';
  }

  get resultParticipantName(): string {
    const p = this.registrationResult?.participants?.[0];
    if (p?.first_name || p?.last_name) {
      return `${p.first_name ?? ''} ${p.last_name ?? ''}`.trim();
    }
    return this.registrationResult?.name ?? '—';
  }

  get resultParticipantEmail(): string {
    return this.registrationResult?.participants?.[0]?.email
      ?? this.registrationResult?.email
      ?? '—';
  }

  get resultQrUrl(): string | null {
    return this.registrationResult?.qr_url ?? this.registrationResult?.qr_code ?? null;
  }

  get resultTypeLabel(): string {
    return this.lastSubmissionType === 'group' ? 'Grupal' : 'Individual';
  }

  get isGroupRegistration(): boolean {
    return this.lastSubmissionType === 'group';
  }

  /**
   * Folios individuales para la vista grupal (una tarjeta por participante).
   * Si el backend no regresa el arreglo `participants`, se cae de vuelta a
   * un solo resultado (mismo comportamiento que el registro individual).
   */
  get resultParticipants(): Array<{ folio: string; name: string; email: string; qrUrl: string | null }> {
    const participants = this.registrationResult?.participants;

    if (Array.isArray(participants) && participants.length > 0) {
      return participants.map((p: any) => ({
        folio: p.folio ?? this.resultFolio,
        name: `${p.first_name ?? ''} ${p.last_name ?? ''}`.trim() || (p.name ?? '—'),
        email: p.email ?? '—',
        qrUrl: p.qr_url ?? p.qr_code ?? null,
      }));
    }

    return [{
      folio: this.resultFolio,
      name: this.resultParticipantName,
      email: this.resultParticipantEmail,
      qrUrl: this.resultQrUrl,
    }];
  }

  get resultParticipantsCount(): number {
    return this.registrationResult?.participants?.length ?? this.lastSubmissionCount;
  }

  get resultDate(): string {
    const raw = this.registrationResult?.created_at;
    const date = raw ? new Date(raw) : new Date();
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  get resultStatus(): string {
    return this.registrationResult?.status ?? 'Confirmado';
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }
}