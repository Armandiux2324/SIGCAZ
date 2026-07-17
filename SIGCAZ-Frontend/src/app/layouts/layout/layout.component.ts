import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { SessionService } from '../../services/session.service';

@Component({
  selector: 'app-layout',
  standalone: false,
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.scss'
})
export class LayoutComponent implements OnInit, OnDestroy {
  name: string = '';
  userId: string = '';
  token: string = '';
  role: string = '';
  menuAbierto: boolean = true;

  private nameSub?: Subscription;

  constructor(private router: Router, private session: SessionService) {}

  ngOnInit(): void {
    this.userId = localStorage.getItem('userId') ?? '';
    this.token = localStorage.getItem('accessToken') ?? '';
    this.role = localStorage.getItem('role') ?? '';
    this.nameSub = this.session.name$.subscribe(name => this.name = name);

    if (window.innerWidth <= 768) {
      this.menuAbierto = false;
    }
  }

 ngOnDestroy(): void {
    this.nameSub?.unsubscribe();
  }

  toggleMenu(): void {
    this.menuAbierto = !this.menuAbierto;

    setTimeout(() => {
      window.dispatchEvent(new Event('resize'));
    }, 420);
  }

  logout(): void {
    localStorage.clear();
    this.router.navigate(['/login']);
  }

  redirectToProfile(): void {
    this.router.navigate(['/admin/profile']);
  }
}