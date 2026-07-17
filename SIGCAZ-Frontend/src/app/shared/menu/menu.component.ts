import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { SessionService } from '../../services/session.service';

@Component({
  selector: 'app-menu',
  standalone: false,
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.scss'
})
export class MenuComponent implements OnInit, OnDestroy {
  @Input() menuAbierto: boolean = true;

  token: any = '';
  userId: any = '';
  role: any = '';
  eventImageUrl: string | null = null;

  private eventImageSub?: Subscription;

  constructor(private router: Router, private session: SessionService) {}

  ngOnInit(){
    this.token = localStorage.getItem('accessToken');
    this.userId = localStorage.getItem('userId');
    this.role = localStorage.getItem('role');

    this.eventImageSub = this.session.eventImage$.subscribe(url => this.eventImageUrl = url);
    this.session.loadEventImage(this.token);
  }

  ngOnDestroy(): void {
    this.eventImageSub?.unsubscribe();
  }

  logout() {
    localStorage.clear();
    this.router.navigate(['/login']);
  }
}