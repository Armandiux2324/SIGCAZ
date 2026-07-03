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
}