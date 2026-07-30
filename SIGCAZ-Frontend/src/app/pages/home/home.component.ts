import { AfterViewInit, Component, ElementRef, HostListener, Inject, OnDestroy, OnInit, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

interface HeroSlide {
  src: string;
  alt: string;
}

interface Benefit {
  icon: string;
  title: string;
  description: string;
}

interface GalleryPhoto {
  src: string;
  alt: string;
  caption: string;
  span: string;
}

interface FaqItem {
  question: string;
  answer: string;
}

@Component({
  selector: 'app-home',
  standalone: false,
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent implements OnInit, AfterViewInit, OnDestroy {
  videoUrl: SafeResourceUrl;

  // Hero / carrusel propio (sin Bootstrap Carousel)
  heroSlides: HeroSlide[] = [
    { src: 'imagen_5.png', alt: 'Pueblos mágicos de Zacatecas' },
    { src: 'imagen_4.png', alt: 'Actividades en Zacatecas' },
    { src: 'imagen_3.png', alt: 'Zacatecas de noche' },
    { src: 'imagen_1.png', alt: 'Información útil de Zacatecas' },
    { src: 'imagen_2.png', alt: 'Información útil de Zacatecas' }
  ];
  activeSlide = 0;
  private heroInterval?: ReturnType<typeof setInterval>;
  private readonly heroIntervalMs = 6000;

  // Navbar glassmorphism al hacer scroll
  scrolled = false;

  // Beneficios de asistir al evento
  benefits: Benefit[] = [
    { icon: 'bi-bank2', title: 'Patrimonio Histórico', description: 'Revive la historia recorriendo la emblemática ruta de la Cabalgata Toma de Zacatecas.' },
    { icon: 'bi-palette', title: 'Cultura Zacatecana', description: 'Disfruta de música, callejoneadas, ceremonias y expresiones tradicionales.' },
    { icon: 'bi-cup-hot', title: 'Convivencia', description: 'Comparte la experiencia con cabalgantes, familias y visitantes de todo el país.' },
    { icon: 'bi-signpost-2', title: 'Experiencias Únicas', description: 'Recorre paisajes y escenarios históricos ideales para crear recuerdos inolvidables.' },
    { icon: 'bi-people', title: 'Ambiente Familiar', description: 'Un evento pensado para disfrutarse en un entorno seguro y para todas las edades.' },
    { icon: 'bi-stars', title: 'Actividades Especiales', description: 'Participa en verbenas populares, espectáculos musicales y actividades culturales.' },
    { icon: 'bi-music-note-beamed', title: 'Tradición Viva', description: 'Vive el recorrido por el Centro Histórico y culmina en el emblemático Cerro de La Bufa.' },
    { icon: 'bi-award', title: 'Turismo y Tradición', description: 'Descubre la riqueza cultural, gastronómica y turística que distingue a Zacatecas.' }
  ];

  private revealObserver?: IntersectionObserver;
  private fragmentSub?: Subscription;

  // Galería
galleryPhotos: GalleryPhoto[] = [
  { src: 'imagen_1.png', alt: 'imagen_1', caption: 'Vive la Historia', span: 'span-tall' },
  { src: 'imagen_2.png', alt: 'imagen_2', caption: 'Comparte la Tradición', span: '' },
  { src: 'imagen_3.png', alt: 'imagen_3', caption: 'Recorre Zacatecas', span: '' },
  { src: 'imagen_4.png', alt: 'imagen_4', caption: 'Momentos Únicos', span: 'span-wide' },
  { src: 'imagen_5.png', alt: 'imagen_5', caption: 'Orgullo Zacatecano', span: '' },
  { src: 'imagen_6.png', alt: 'imagen_6', caption: 'Una Gran Experiencia', span: '' },
  { src: 'imagen_7.png', alt: 'imagen_7', caption: 'Un Camino con Historia', span: '' },
  { src: 'imagen_8.png', alt: 'imagen_8', caption: 'Más que una Cabalgata', span: '' }
];
  // Preguntas Frecuentes (accordion sin dependencias externas)
  faqs: FaqItem[] = [
    { question: '¿Cómo puedo registrarme?', answer: 'Da clic en el botón "Registrarse", completa el formulario con tus datos y recibirás tu folio de confirmación por correo electrónico.' },
    { question: '¿Tiene algún costo?', answer: 'El registro general al evento es gratuito.' },
    { question: '¿Puedo modificar mi registro?', answer: 'Si, pero únicamente contactando al equipo de organización: 492 925 1277.' },
    { question: '¿Cómo consulto mi folio?', answer: 'Ingresa a la sección "Consultar Registro" e introduce el correo electrónico y tu folio el cual se te mando a tu correo electrónico.' },
    { question: '¿Qué sucede después del registro?', answer: 'Recibirás un correo de confirmación con tu folio y los detalles del evento. Después puedes consultar el programa oficial en este mismo sitio.' },
    { question: '¿Cómo registrarán mi asistencia?', answer: 'Se escaneará el Qr que se te mando a tu correo electrónico. Así mismo se entregará tu playera de participación.' }
  ];
  activeFaq: number | null = 0;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private el: ElementRef,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {
    this.videoUrl = this.sanitizer.bypassSecurityTrustResourceUrl(
      'https://www.youtube.com/embed/u4LdLalV-As?autoplay=1&mute=1&rel=0&controls=1'
    );
  }

  ngOnInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    this.startHeroAutoplay();
  }

  ngAfterViewInit(): void {
    if (!isPlatformBrowser(this.platformId)) return;
    this.initRevealAnimations();

    // Navega a la sección correcta cuando se llega con un fragmento (p. ej. /home#galeria),
    // ya sea al entrar desde otra página o al hacer clic en el header estando ya en /home.
    this.fragmentSub = this.route.fragment.subscribe(fragment => {
      if (fragment) {
        this.scrollToFragment(fragment);
      }
    });
  }

  private scrollToFragment(fragment: string): void {
    // Se espera un tick para asegurar que la sección ya esté renderizada
    // (relevante sobre todo al cargar /home por primera vez desde otra ruta).
    setTimeout(() => {
      document.getElementById(fragment)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 0);
  }

  ngOnDestroy(): void {
    this.revealObserver?.disconnect();
    this.fragmentSub?.unsubscribe();
    if (this.heroInterval) {
      clearInterval(this.heroInterval);
    }
  }

  @HostListener('window:scroll')
  onWindowScroll(): void {
    this.scrolled = window.scrollY > 24;
  }

  private startHeroAutoplay(): void {
    this.heroInterval = setInterval(() => {
      this.goToSlide((this.activeSlide + 1) % this.heroSlides.length);
    }, this.heroIntervalMs);
  }

  goToSlide(index: number): void {
    this.activeSlide = index;
  }

  toggleFaq(index: number): void {
    this.activeFaq = this.activeFaq === index ? null : index;
  }

  private initRevealAnimations(): void {
    const revealEls: NodeListOf<HTMLElement> = this.el.nativeElement.querySelectorAll('.reveal');
    if (!revealEls.length) return;

    revealEls.forEach(el => el.classList.add('animate'));

    this.revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            this.revealObserver?.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: '0px 0px -60px 0px' }
    );

    revealEls.forEach(el => this.revealObserver!.observe(el));
  }

  redirectToRegister(){
    this.router.navigate(['/register']);
  }

  redirectToSearchRegister(){
    this.router.navigate(['/search-register']);
  }

  scrollToTop(){
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}