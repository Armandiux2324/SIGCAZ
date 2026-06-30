import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-search-register',
  standalone: false,
  templateUrl: './search-register.component.html',
  styleUrl: './search-register.component.scss'
})
export class SearchRegisterComponent {
  loading = false;
  notFound = false;
  toastMessage = '';
  toastType: 'success' | 'error' = 'success';
  showToastFlag = false;

  searchData = {
    folio: '',
    email: '',
  };

  register: any = null;

  constructor(private api: ApiService) { }

  searchRegister() {
    this.loading = true;
    this.notFound = false;
    this.register = null;

    this.api.searchRegister(this.searchData.folio, this.searchData.email).then(res => {
      this.register = res.data;
      this.loading = false;
    }).catch(() => {
      this.notFound = true;
      this.loading = false;
      this.toastMessage = 'No se encontró ningún registro con ese folio y correo.';
      this.showToast('error');
    })
  }

  downloadReceipt() {
    const url = this.api.getReceiptUrl(this.searchData.folio, this.searchData.email);
    window.open(url, '_blank');
  }

  showToast(type: 'success' | 'error') {
    this.toastType = type;
    this.showToastFlag = true;

    setTimeout(() => {
      this.showToastFlag = false;
    }, 3000);
  }
}
