import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-menu',
  standalone: false,
  templateUrl: './menu.component.html',
  styleUrl: './menu.component.scss'
})
export class MenuComponent implements OnInit {
  token: any = '';
  userId: any = '';
  role: any = '';

  constructor(private router: Router) {}
  
  ngOnInit(){
    this.token = localStorage.getItem('accessToken');
    this.userId = localStorage.getItem('userId');
    this.role = localStorage.getItem('role');
  }

  logout() {
    localStorage.clear();
    this.router.navigate(['/login']);
  }
}
