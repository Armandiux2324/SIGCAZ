import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class SessionService {
  private nameSubject = new BehaviorSubject<string>(localStorage.getItem('name') ?? '');
  name$ = this.nameSubject.asObservable();

  private eventImageSubject = new BehaviorSubject<string | null>(null);
  eventImage$ = this.eventImageSubject.asObservable();

  constructor(private api: ApiService) {}

  setName(name: string): void {
    localStorage.setItem('name', name);
    this.nameSubject.next(name);
  }

  setEventImage(url: string | null): void {
    this.eventImageSubject.next(url);
  }

  loadEventImage(token: string): void {
    this.api.getSettings(token).then((res: any) => {
      const url = res.data?.data?.event_image_url ?? null;
      this.eventImageSubject.next(url);
    }).catch(() => {});
  }
}