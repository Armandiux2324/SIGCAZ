import { AfterViewInit, Component, ElementRef, HostListener, Inject, OnDestroy, PLATFORM_ID, ViewChild } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { Router } from '@angular/router';

@Component({
  selector: 'app-public-layout',
  standalone: false,
  templateUrl: './public-layout.component.html',
  styleUrl: './public-layout.component.scss'
})
export class PublicLayoutComponent implements AfterViewInit, OnDestroy {
  @ViewChild('footerRef', { static: false }) footerRef!: ElementRef;

  menuOpen = false;
  scrolled = false;
  footerVisible = false;

  private footerObserver?: IntersectionObserver;

  constructor(
    private router: Router,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {}

  ngAfterViewInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    this.initFooterObserver();
  }

  ngOnDestroy(): void {
    this.footerObserver?.disconnect();
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    this.scrolled = window.scrollY > 24;
  }

  private initFooterObserver(): void {
    if (!this.footerRef?.nativeElement) return;

    this.footerObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          this.footerVisible = entry.isIntersecting;
          entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    this.footerObserver.observe(this.footerRef.nativeElement);
  }

  toggleMenu(): void {
    this.menuOpen = !this.menuOpen;
  }

  redirectToRegister(): void {
    this.menuOpen = false;
    this.router.navigate(['/register']);
  }

  redirectToSearchRegister(): void {
    this.menuOpen = false;
    this.router.navigate(['/search-register']);
  }

  redirectToLogin(): void {
    this.router.navigate(['/login']);
  }
}