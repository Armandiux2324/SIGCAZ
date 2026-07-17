import axios from 'axios';
import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment.prod';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  url = environment.backend;

  getToken(token: string) {
    return { headers: { Authorization: `Bearer ${token}` } };
  }

  // Funciones públicas
  addRegister(
    originType: string,
    state: string,
    municipality: string,
    group: string,
    attendanceType: string,
    participantCount: number,
    accommodationType: string,
    lodging: string,
    stayDays: number,
    transportMethod: string,
    folioDeliveryMethod: string,
    participants: {
      firstName: string;
      lastName: string;
      phone: string;
      email: string;
      gender: string;
      shirtSize: string;
      isFirstTime: boolean;
      participationCount: number;
    }[]
  ) {
    return axios.post(this.url + '/registers', {
      origin_type: originType,
      state,
      municipality,
      group,
      attendance_type: attendanceType,
      participant_count: participantCount,
      accommodation_type: accommodationType,
      lodging,
      stay_days: stayDays,
      transport_method: transportMethod,
      folio_delivery_method: folioDeliveryMethod,
      participants: participants.map((p) => ({
        first_name: p.firstName,
        last_name: p.lastName,
        phone: p.phone,
        email: p.email,
        gender: p.gender,
        shirt_size: p.shirtSize,
        is_first_time: p.isFirstTime,
        participation_count: p.isFirstTime ? 0 : p.participationCount,
      })),
    });
  }

  searchRegister(folio: string, email: string) {
    return axios.get(this.url + '/registers/search', { params: { folio, email } });
  }

  getReceiptUrl(folio: string, email: string): string {
    const params = new URLSearchParams({ folio, email });
    return `${this.url}/registers/receipt?${params.toString()}`;
  }

  // Funciones administrativas
  login(email: string, password: string){
    return axios.post(this.url + '/login', { email, password })
  }

  getUser(token: string){
    return axios.get(this.url + '/me', this.getToken(token))
  }

  // Funciones de administración de usuarios
  getUsers(token: string) {
    return axios.get(this.url + '/users', this.getToken(token));
  }

  getUserById(id: string, token: string) {
    return axios.get(this.url + `/users/${id}`, this.getToken(token));
  }

  searchUsers(email: string, token: string) {
    return axios.get(this.url + '/users/search', { params: { email }, ...this.getToken(token) });
  }

  addUser(name: string, email: string, password: string, phone: string, role: string, token: string) {
    return axios.post(this.url + '/users', { name, email, password, phone, role }, this.getToken(token));
  }

  updateUser(id: string, name: string, email: string, phone: string, password: string | undefined, token: string) {
    return axios.put(this.url + `/users/${id}`, { name, email, phone, password }, this.getToken(token));
  }

  deleteUser(id: string, token: string) {
    return axios.delete(this.url + `/users/${id}`, this.getToken(token));
  }
  
  // Funciones de configuración del evento
  getSettings(token: string) {
    return axios.get(this.url + '/settings', this.getToken(token));
  }

  updateSettings(eventAddress: string, eventDate: string, eventTime: string, eventImage: File | null, token: string) {
    const formData = new FormData();
    formData.append('event_address', eventAddress);
    formData.append('event_date', `${eventDate} ${eventTime}`);
    if (eventImage) {
      formData.append('event_image', eventImage);
    }
    formData.append('_method', 'PUT');

    return axios.post(this.url + '/settings', formData, {
      headers: {
        ...this.getToken(token).headers,
        'Content-Type': 'multipart/form-data',
      },
    });
  }

  // Funciones del dashboard / estadísticas
  getStatsSummary(token: string, year?: string) {
    return axios.get(this.url + '/stats/summary', { params: { year }, ...this.getToken(token) });
  }

  getStatsChart(filter: string, token: string, year?: string) {
    return axios.get(this.url + '/stats/chart', { params: { filter, year }, ...this.getToken(token) });
  }

  getStatsByYear(token: string) {
    return axios.get(this.url + '/stats/by-year', this.getToken(token));
  }

  getRecentRegisters(perPage: number, token: string) {
    return axios.get(this.url + '/registers', { params: { per_page: perPage }, ...this.getToken(token) });
  }

  // Funciones de administración de registros
  getRegisters(page: number, perPage: number, token: string) {
    return axios.get(this.url + '/registers', { params: { page, per_page: perPage }, ...this.getToken(token) });
  }

  getRegisterById(id: number | string, token: string) {
    return axios.get(this.url + `/registers/${id}`, this.getToken(token));
  }

  searchRegistersByFilter(q: string, token: string) {
    return axios.get(this.url + '/registers/search-filter', { params: { q }, ...this.getToken(token) });
  }

  addRegisterAdmin(payload: any) {
    return axios.post(this.url + '/registers', payload);
  }

  updateRegister(id: number | string, payload: any, token: string) {
    return axios.put(this.url + `/registers/${id}`, payload, this.getToken(token));
  }

  deleteRegister(id: number | string, token: string) {
    return axios.delete(this.url + `/registers/${id}`, this.getToken(token));
  }

  downloadReport(reportPath: string, token: string, year?: string) {
    return axios.get(this.url + `/reports/${reportPath}`, {
      responseType: 'blob',
      params: { year },
      ...this.getToken(token),
    });
  }
}