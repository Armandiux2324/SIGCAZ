import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../../services/api.service';

@Component({
  selector: 'app-users',
  standalone: false,
  templateUrl: './users.component.html',
  styleUrl: './users.component.scss'
})
export class UsersComponent implements OnInit {
  token = localStorage.getItem('accessToken') ?? '';

  users: any[] = [];
  loading = false;

  searchQuery = '';
  searching = false;

  showAddModal = false;
  showEditModal = false;
  showDeleteModal = false;

  selectedUser: any = null;

  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  dataToAdd = { name: '', email: '', password: '', phone: '', role: 'staff' };
  dataToEdit: any = {};

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.loadUsers();
  }

  loadUsers(): void {
    this.loading = true;
    this.api.getUsers(this.token).then((res: any) => {
      this.users = res.data.data.data ?? res.data.data;
      this.loading = false;
    }).catch(() => {
      this.loading = false;
    });
  }

  searchUsers(): void {
    if (!this.searchQuery.trim()) {
      this.loadUsers();
      return;
    }
    this.searching = true;
    this.api.searchUsers(this.searchQuery, this.token).then((res: any) => {
      this.users = res.data.data;
      this.searching = false;
    }).catch(() => {
      this.users = [];
      this.searching = false;
    });
  }

  onSearchInput(): void {
    if (!this.searchQuery.trim()) {
      this.loadUsers();
    }
  }

  openAddModal(): void {
    this.dataToAdd = { name: '', email: '', password: '', phone: '', role: 'staff' };
    this.showAddModal = true;
  }

  closeAddModal(): void {
    this.showAddModal = false;
  }

  addUser(): void {
    const { name, email, password, phone, role } = this.dataToAdd;
    this.api.addUser(name, email, password, phone, role, this.token).then(() => {
      this.toastMessage = 'Usuario creado correctamente.';
      this.showToast('success');
      this.closeAddModal();
      this.loadUsers();
    }).catch(() => {
      this.toastMessage = 'Error al crear el usuario.';
      this.showToast('error');
    });
  }

  openEditModal(user: any): void {
    this.dataToEdit = { ...user, password: '' };
    this.showEditModal = true;
  }

  closeEditModal(): void {
    this.showEditModal = false;
  }

  editUser(): void {
    const { id, name, email, phone, password } = this.dataToEdit;
    this.api.updateUser(id, name, email, phone, password || undefined, this.token).then(() => {
      this.toastMessage = 'Usuario actualizado correctamente.';
      this.showToast('success');
      this.closeEditModal();
      this.loadUsers();
    }).catch(() => {
      this.toastMessage = 'Error al actualizar el usuario.';
      this.showToast('error');
    });
  }

  openDeleteModal(user: any): void {
    this.selectedUser = user;
    this.showDeleteModal = true;
  }

  closeDeleteModal(): void {
    this.showDeleteModal = false;
    this.selectedUser = null;
  }

  deleteUser(): void {
    this.api.deleteUser(this.selectedUser.id, this.token).then(() => {
      this.toastMessage = 'Usuario eliminado correctamente.';
      this.showToast('success');
      this.closeDeleteModal();
      this.loadUsers();
    }).catch(() => {
      this.toastMessage = 'Error al eliminar el usuario.';
      this.showToast('error');
    });
  }

  showToast(type: 'success' | 'error'): void {
    this.toastType = type;
    this.showToastFlag = true;
    setTimeout(() => { this.showToastFlag = false; }, 4000);
  }
}