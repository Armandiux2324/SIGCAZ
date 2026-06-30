import axios from 'axios';
import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment.prod';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  constructor() { }

  url = environment.backend;

  addRegister(
    originType: string,
    state: string,
    municipality: string,
    group: string,
    isFirstTime: boolean,
    participationCount: number,
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
    }[]) {
    return axios.post(this.url + '/registers', {
      origin_type: originType,
      state,
      municipality,
      group,
      is_first_time: isFirstTime,
      participation_count: participationCount,
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
      })),
    });
  }

  searchRegister(folio: string, email: string) {
    return axios.get(this.url + '/registers/search', { params: {folio, email} });
  }

  getReceiptUrl(folio: string, email: string): string {
    const params = new URLSearchParams({folio, email,});
    return `${this.url}/registers/receipt?${params.toString()}`;
  }
}
