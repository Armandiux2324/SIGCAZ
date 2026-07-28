// home.component.ts
import { AfterViewInit, Component, ElementRef, Inject, OnDestroy, PLATFORM_ID, ViewChild } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { Router } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

@Component({
  selector: 'app-home',
  standalone: false,
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent implements AfterViewInit, OnDestroy {
  @ViewChild('heroCarousel', { static: false }) carouselRef!: ElementRef;
  @ViewChild('footerRef', { static: false }) footerRef!: ElementRef;

  menuOpen = false;
  footerVisible = false;
  videoUrl: SafeResourceUrl;

  private footerObserver?: IntersectionObserver;

  constructor(
    private router: Router,
    private sanitizer: DomSanitizer,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {
    this.videoUrl = this.sanitizer.bypassSecurityTrustResourceUrl(
      'https://www.youtube.com/embed/u4LdLalV-As?autoplay=1&mute=1&rel=0&controls=1'
    );
  }

  ngAfterViewInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;

    this.initCarousel();
    this.initFooterObserver();
  }

  ngOnDestroy(): void {
    this.footerObserver?.disconnect();
  }

  private async initCarousel(): Promise<void> {
    try {
      const { Carousel } = await import('bootstrap');
      new Carousel(this.carouselRef.nativeElement, {
        interval: 3000,
        ride: 'carousel',
        wrap: true
      });
    } catch (err) {
      console.error('Error al inicializar el carrusel:', err);
    }
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