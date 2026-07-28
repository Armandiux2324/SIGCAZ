import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-home',
  standalone: false,
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent {
  menuOpen = false;

  constructor(private router: Router) {}

  redirectToRegister(){
    this.router.navigate(['/register']);
  }

  redirectToSearchRegister(){
    this.router.navigate(['/search-register']);
  }

  redirectToLogin(){
    this.router.navigate(['/login']);
  }

  toggleMenu(){
    this.menuOpen = !this.menuOpen;
  }

  scrollToTop(){
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}