// src/app/pages/historial/historial.page.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import {
  AsistenciaService,
  RegistroAsistencia,
  PaginacionLaravel
} from '../../services/asistencia';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';

@Component({
  selector: 'app-historial',
  templateUrl: './historial.page.html',
  styleUrls: ['./historial.page.scss'],
  standalone: false
})
export class HistorialPage implements OnInit, OnDestroy {

  // ── Datos ─────────────────────────────────────────────────────────────────
  // registrosPagina: los que muestra la página actual (mapeados desde ScanItem)
  registrosPagina: RegistroAsistencia[] = [];

  // Meta de paginación que devuelve Laravel
  paginacion: PaginacionLaravel | null = null;

  // ── Filtros ───────────────────────────────────────────────────────────────
  terminoBusqueda = '';
  fechaFiltro: string | null = null;
  hoy: string = new Date().toISOString();

  // ── Paginación ────────────────────────────────────────────────────────────
  paginaActual = 1;
  pageSize     = 5;   // debe coincidir con per_page en Laravel (o ajusta aquí)

  // ── Estado UI ─────────────────────────────────────────────────────────────
  cargando = false;
  error    = '';

  // Debounce para la búsqueda: no llama a la API en cada tecla
  private busqueda$ = new Subject<string>();
  private busquedaSub: any;

  // ── Stats calculados desde la paginación de Laravel ───────────────────────
  get totalRegistros(): number { return this.paginacion?.total      ?? 0; }
  get totalPaginas():   number { return this.paginacion?.last_page  ?? 1; }
  get rangoDesde():     number { return this.paginacion?.from       ?? 0; }
  get rangoHasta():     number { return this.paginacion?.to         ?? 0; }
  get hayAnterior():   boolean { return !!this.paginacion?.prev_page_url; }
  get haySiguiente():  boolean { return !!this.paginacion?.next_page_url; }

  // Registros de hoy: cuenta los que están en la página actual con fecha de hoy
  // (para un conteo exacto necesitarías un endpoint dedicado en Laravel)
  get registrosHoy(): number {
    const hoyStr = new Date().toLocaleDateString('es-MX');
    return this.registrosPagina.filter(r => r.fecha === hoyStr).length;
  }

  constructor(private asistenciaService: AsistenciaService) {}

  ngOnInit(): void {
    // Espera 400 ms tras la última tecla antes de buscar
    this.busquedaSub = this.busqueda$.pipe(
      debounceTime(400),
      distinctUntilChanged()
    ).subscribe(() => {
      this.paginaActual = 1;
      this.cargarHistorial();
    });

    this.cargarHistorial();
  }

  ngOnDestroy(): void {
    this.busquedaSub?.unsubscribe();
  }

  // ── GET /api/v1/scans con los filtros y página actuales ───────────────────
  cargarHistorial(): void {
    this.cargando = true;
    this.error    = '';

    const params: any = { page: this.paginaActual };
    if (this.terminoBusqueda.trim()) params.search = this.terminoBusqueda.trim();
    if (this.fechaFiltro)            params.fecha  = this.fechaFiltro.slice(0, 10);

    this.asistenciaService.getHistorial(params).subscribe({
      next: (res) => {
        this.cargando = false;
        this.paginacion = res.data;

        // Mapear cada ScanItem al RegistroAsistencia que usa el template
        this.registrosPagina = res.data.data
          .map(item => this.asistenciaService.mapearRegistro(item));
      },
      error: (err) => {
        this.cargando = false;
        if (err.status === 0) {
          this.error = 'Sin conexión al servidor.';
        } else {
          this.error = err.error?.message ?? 'Error al cargar el historial.';
        }
      }
    });
  }

  // ── Búsqueda con debounce ─────────────────────────────────────────────────
  onBusqueda(): void {
    this.busqueda$.next(this.terminoBusqueda);
  }

  // ── Filtro por fecha ──────────────────────────────────────────────────────
  onFechaChange(): void {
    this.paginaActual = 1;
    this.cargarHistorial();
  }

  limpiarFecha(): void {
    this.fechaFiltro  = null;
    this.paginaActual = 1;
    this.cargarHistorial();
  }

  // ── Paginación — usa prev/next de Laravel, no cálculo local ──────────────
  cambiarPagina(pagina: number): void {
    if (pagina < 1 || pagina > this.totalPaginas) return;
    this.paginaActual = pagina;
    this.cargarHistorial();
  }

  // Botones de número de página con elipsis
  get paginasVisibles(): (number | null)[] {
    const total  = this.totalPaginas;
    const actual = this.paginaActual;
    const pages: (number | null)[] = [];

    if (total <= 5) {
      for (let i = 1; i <= total; i++) pages.push(i);
      return pages;
    }

    pages.push(1);
    if (actual > 3)        pages.push(null);   // elipsis
    const inicio = Math.max(2, actual - 1);
    const fin    = Math.min(total - 1, actual + 1);
    for (let i = inicio; i <= fin; i++) pages.push(i);
    if (actual < total - 2) pages.push(null);  // elipsis
    pages.push(total);
    return pages;
  }

  // ── trackBy para performance ──────────────────────────────────────────────
  trackById(_index: number, item: RegistroAsistencia): string {
    return item.id;
  }

  // ── Exportar CSV con los registros de la página/filtro actual ─────────────
  exportarDatos(): void {
    const cabecera = 'Nombre,Folio,Talla,Fecha,Hora\n';
    const filas = this.registrosPagina
      .map(r => `${r.nombre},${r.matricula},${r.talla},${r.fecha},${r.hora}`)
      .join('\n');
    const blob = new Blob([cabecera + filas], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `asistencia_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }
}