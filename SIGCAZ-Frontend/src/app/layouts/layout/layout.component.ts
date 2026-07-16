import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-layout',
  standalone: false,
  templateUrl: './layout.component.html',
  styleUrl: './layout.component.scss'
})
export class LayoutComponent implements OnInit {
  name: string = '';
  userId: string = '';
  token: string = '';
  role: string = '';

  constructor(private router: Router) {}

  ngOnInit(): void {
    this.name = localStorage.getItem('name') ?? '';
    this.userId = localStorage.getItem('userId') ?? '';
    this.token = localStorage.getItem('accessToken') ?? '';
    this.role = localStorage.getItem('role') ?? '';
  }

  logout(): void {
    localStorage.clear();
    this.router.navigate(['/login']);
  }

  redirectToProfile(): void {
    this.router.navigate(['/admin/profile']);
  }
}