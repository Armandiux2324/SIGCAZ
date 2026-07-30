import { AfterViewInit, Component, ElementRef, HostListener, Inject, OnDestroy, OnInit, PLATFORM_ID, ViewChild } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { NavigationEnd, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { filter } from 'rxjs/operators';

@Component({
  selector: 'app-public-layout',
  standalone: false,
  templateUrl: './public-layout.component.html',
  styleUrl: './public-layout.component.scss'
})
export class PublicLayoutComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChild('footerRef', { static: false }) footerRef!: ElementRef;

  menuOpen = false;
  scrolled = false;
  footerVisible = false;

  private footerObserver?: IntersectionObserver;
  private routerSub?: Subscription;

  constructor(
    private router: Router,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {}

  ngOnInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;

    // Al cambiar entre home/register/search-register, sube la página al inicio;
    // si la navegación trae fragmento (p. ej. /home#galeria), HomeComponent
    // se encarga de hacer scroll suave hasta esa sección justo después.
    this.routerSub = this.router.events
      .pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd))
      .subscribe(() => {
        window.scrollTo(0, 0);
      });
  }

  ngAfterViewInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    this.initFooterObserver();
  }

  ngOnDestroy(): void {
    this.footerObserver?.disconnect();
    this.routerSub?.unsubscribe();
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    this.scrolled = window.scrollY > 24;
  }

  private initFooterObserver(): void {
    if (!this.footerRef?.nativeElement) return;

    const footerEl = this.footerRef.nativeElement as HTMLElement;
    // Estado inicial oculto: sin esta clase, la transición de la animación nunca se dispara.
    footerEl.classList.add('animate');

    this.footerObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          this.footerVisible = entry.isIntersecting;
          entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
      },
      { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
    );

    this.footerObserver.observe(footerEl);
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