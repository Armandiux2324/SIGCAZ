import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class SessionService {
  private nameSubject = new BehaviorSubject<string>(localStorage.getItem('name') ?? '');
  name$ = this.nameSubject.asObservable();

  setName(name: string): void {
    localStorage.setItem('name', name);
    this.nameSubject.next(name);
  }
}