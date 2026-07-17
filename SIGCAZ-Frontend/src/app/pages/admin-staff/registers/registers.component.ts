import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../../services/api.service';

type ParticipantForm = {
  id?: number;
  first_name: string;
  last_name: string;
  phone: string;
  email: string;
  gender: string;
  shirt_size: string;
  is_first_time: boolean;
  participation_count: number;
};

function emptyParticipant(): ParticipantForm {
  return {
    first_name: '', last_name: '', phone: '', email: '',
    gender: 'male', shirt_size: 'M', is_first_time: true, participation_count: 0,
  };
}

function emptyRegisterForm() {
  return {
    origin_type: 'national',
    state: '',
    municipality: '',
    group: '',
    accommodation_type: 'hotel',
    lodging: '',
    stay_days: 1,
    transport_method: 'car',
    folio_delivery_method: 'email',
    participants: [emptyParticipant()],
  };
}

@Component({
  selector: 'app-registers',
  standalone: false,
  templateUrl: './registers.component.html',
  styleUrl: './registers.component.scss'
})
export class RegistersComponent implements OnInit {
  token = localStorage.getItem('accessToken') ?? '';

  registers: any[] = [];
  loading = false;

  searchQuery = '';
  searchMode = false;

  currentPage = 1;
  lastPage = 1;
  perPage = 10;

  showAddModal = false;
  showEditModal = false;
  showDeleteModal = false;
  saving = false;

  selectedRegister: any = null;

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  dataToAdd = emptyRegisterForm();
  dataToEdit: any = emptyRegisterForm();

  originOptions = [
    { value: 'national', label: 'Nacional' },
    { value: 'state', label: 'Estatal' },
  ];
  accommodationOptions = [
    { value: 'airbnb', label: 'Airbnb' },
    { value: 'hotel', label: 'Hotel' },
    { value: 'own_home', label: 'Hostal' },
    { value: 'family_or_friends', label: 'Casa propia / Familiar' },
  ];
  transportOptions = [
    { value: 'car', label: 'Automóvil' },
    { value: 'bus', label: 'Autobús' },
    { value: 'airplane', label: 'Avión' },
  ];
  folioDeliveryOptions = [
    { value: 'email', label: 'Correo electrónico' },
    { value: 'phone', label: 'Teléfono' },
  ];
  genderOptions = [
    { value: 'male', label: 'Masculino' },
    { value: 'female', label: 'Femenino' },
  ];
  shirtSizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.loadRegisters();
  }

  originLabel(value: string): string {
    return this.originOptions.find(o => o.value === value)?.label ?? value;
  }
  accommodationLabel(value: string): string {
    return this.accommodationOptions.find(o => o.value === value)?.label ?? value;
  }
  transportLabel(value: string): string {
    return this.transportOptions.find(o => o.value === value)?.label ?? value;
  }

  loadRegisters(page: number = 1): void {
    this.loading = true;
    this.searchMode = false;
    this.api.getRegisters(page, this.perPage, this.token).then((res: any) => {
      const paginator = res.data.data;
      this.registers = paginator.data ?? [];
      this.currentPage = paginator.current_page ?? 1;
      this.lastPage = paginator.last_page ?? 1;
      this.loading = false;
    }).catch(() => {
      this.registers = [];
      this.loading = false;
    });
  }

  get pageNumbers(): number[] {
    const pages: number[] = [];
    const start = Math.max(1, this.currentPage - 2);
    const end = Math.min(this.lastPage, start + 4);
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage || page === this.currentPage) return;
    this.loadRegisters(page);
  }

  previousPage(): void { this.goToPage(this.currentPage - 1); }
  nextPage(): void { this.goToPage(this.currentPage + 1); }

  searchRegisters(): void {
    if (!this.searchQuery.trim()) {
      this.loadRegisters();
      return;
    }
    this.loading = true;
    this.searchMode = true;
    this.api.searchRegistersByFilter(this.searchQuery, this.token).then((res: any) => {
      this.registers = res.data.data ? [res.data.data] : [];
      this.loading = false;
    }).catch(() => {
      this.registers = [];
      this.loading = false;
    });
  }

  onSearchInput(): void {
    if (!this.searchQuery.trim()) {
      this.loadRegisters();
    }
  }

  openAddModal(): void {
    this.dataToAdd = emptyRegisterForm();
    this.showAddModal = true;
  }

  closeAddModal(): void {
    this.showAddModal = false;
  }

  addParticipant(target: 'add' | 'edit'): void {
    const form = target === 'add' ? this.dataToAdd : this.dataToEdit;
    form.participants.push(emptyParticipant());
  }

  removeParticipant(target: 'add' | 'edit', index: number): void {
    const form = target === 'add' ? this.dataToAdd : this.dataToEdit;
    if (form.participants.length <= 1) return;
    form.participants.splice(index, 1);
  }

  private buildPayload(form: any) {
    return {
      ...form,
      attendance_type: form.participants.length > 1 ? 'accompanied' : 'alone',
      participant_count: form.participants.length,
    };
  }

  addRegister(): void {
    this.saving = true;
    this.api.addRegisterAdmin(this.buildPayload(this.dataToAdd)).then(() => {
      this.toastMessage = 'Registro creado correctamente.';
      this.showToast('success');
      this.closeAddModal();
      this.loadRegisters(this.currentPage);
      this.saving = false;
    }).catch((err) => {
      this.toastMessage = err.response?.data?.message ?? 'Error al crear el registro.';
      this.showToast('error');
      this.saving = false;
    });
  }

  openEditModal(register: any): void {
    this.dataToEdit = {
      id: register.id,
      origin_type: register.origin_type,
      state: register.state,
      municipality: register.municipality,
      group: register.group,
      accommodation_type: register.accommodation_type,
      lodging: register.lodging ?? '',
      stay_days: register.stay_days,
      transport_method: register.transport_method,
      folio_delivery_method: register.folio_delivery_method,
      participants: (register.participants ?? []).map((p: any) => ({
        id: p.id,
        first_name: p.first_name,
        last_name: p.last_name,
        phone: p.phone,
        email: p.email,
        gender: p.gender,
        shirt_size: p.shirt_size,
        is_first_time: p.is_first_time,
        participation_count: p.participation_count ?? 0,
      })),
    };
    this.showEditModal = true;
  }

  closeEditModal(): void {
    this.showEditModal = false;
  }

  editRegister(): void {
    this.saving = true;
    const id = this.dataToEdit.id;
    const payload = this.buildPayload(this.dataToEdit);
    delete payload.id;

    this.api.updateRegister(id, payload, this.token).then(() => {
      this.toastMessage = 'Registro actualizado correctamente.';
      this.showToast('success');
      this.closeEditModal();
      this.loadRegisters(this.currentPage);
      this.saving = false;
    }).catch((err) => {
      this.toastMessage = err.response?.data?.message ?? 'Error al actualizar el registro.';
      this.showToast('error');
      this.saving = false;
    });
  }

  openDeleteModal(register: any): void {
    this.selectedRegister = register;
    this.showDeleteModal = true;
  }

  closeDeleteModal(): void {
    this.showDeleteModal = false;
    this.selectedRegister = null;
  }

  deleteRegister(): void {
    this.api.deleteRegister(this.selectedRegister.id, this.token).then(() => {
      this.toastMessage = 'Registro eliminado correctamente.';
      this.showToast('success');
      this.closeDeleteModal();
      this.loadRegisters(this.currentPage);
    }).catch(() => {
      this.toastMessage = 'Error al eliminar el registro.';
      this.showToast('error');
    });
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }
}