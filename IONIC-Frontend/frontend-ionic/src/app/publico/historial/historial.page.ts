import { Component, OnInit } from '@angular/core';
import { AsistenciaService, RegistroAsistencia } from '../../services/asistencia';

@Component({
  selector: 'app-historial',
  templateUrl: './historial.page.html',
  styleUrls: ['./historial.page.scss'],
  standalone: false
})
export class HistorialPage implements OnInit {

  // ── Datos ─────────────────────────────────────
  todosLosRegistros: RegistroAsistencia[] = [];
  registrosFiltrados: RegistroAsistencia[] = [];
  registrosPagina:    RegistroAsistencia[] = [];

  // ── Filtros ───────────────────────────────────
  terminoBusqueda = '';
  fechaFiltro: string | null = null;
  hoy: string = new Date().toISOString();

  // ── Paginación ────────────────────────────────
  paginaActual = 1;
  pageSize     = 5;
  totalPaginas = 1;

  // ── Stats ─────────────────────────────────────
  get totalRegistros(): number { return this.todosLosRegistros.length; }
  get registrosHoy(): number {
    const hoyStr = new Date().toLocaleDateString('es-MX');
    return this.todosLosRegistros.filter(r => r.fecha === hoyStr).length;
  }

  // ── Rango visible ─────────────────────────────
  get rangoDesde(): number { return (this.paginaActual - 1) * this.pageSize + 1; }
  get rangoHasta(): number {
    return Math.min(this.paginaActual * this.pageSize, this.registrosFiltrados.length);
  }

  constructor(private asistenciaService: AsistenciaService) {}

  ngOnInit(): void {
    this.cargarDatos();
  }

  // ── Carga todos los registros desde el servicio ──
  cargarDatos(): void {
    this.todosLosRegistros = this.asistenciaService.getTodos();
    this.aplicarFiltros();
  }

  // ── Disparado al escribir en la búsqueda ──────
  onBusqueda(): void {
    this.paginaActual = 1;
    this.aplicarFiltros();
  }

  // ── Disparado al cambiar la fecha ─────────────
  onFechaChange(): void {
    this.paginaActual = 1;
    this.aplicarFiltros();
  }

  // ── Limpia el filtro de fecha ─────────────────
  limpiarFecha(): void {
    this.fechaFiltro = null;
    this.paginaActual = 1;
    this.aplicarFiltros();
  }

  // ── Aplica búsqueda + filtro de fecha ─────────
  aplicarFiltros(): void {
    let resultado = [...this.todosLosRegistros];

    // Filtro texto
    const termino = this.terminoBusqueda.trim().toLowerCase();
    if (termino) {
      resultado = resultado.filter(r =>
        r.nombre.toLowerCase().includes(termino) ||
        r.matricula.toLowerCase().includes(termino)
      );
    }

    // Filtro fecha
    if (this.fechaFiltro) {
      const fechaSeleccionada = new Date(this.fechaFiltro)
        .toLocaleDateString('es-MX');
      resultado = resultado.filter(r => r.fecha === fechaSeleccionada);
    }

    this.registrosFiltrados = resultado;
    this.totalPaginas = Math.max(1, Math.ceil(resultado.length / this.pageSize));
    this.paginaActual  = Math.min(this.paginaActual, this.totalPaginas);
    this.actualizarPagina();
  }

  // ── Actualiza los registros visibles en la página ──
  actualizarPagina(): void {
    const inicio = (this.paginaActual - 1) * this.pageSize;
    const fin    = inicio + this.pageSize;
    this.registrosPagina = this.registrosFiltrados.slice(inicio, fin);
  }

  // ── Cambia de página ──────────────────────────
  cambiarPagina(pagina: number): void {
    if (pagina < 1 || pagina > this.totalPaginas) return;
    this.paginaActual = pagina;
    this.actualizarPagina();
  }

  // ── Números de página visibles (con elipsis) ──
  get paginasVisibles(): number[] {
    const total = this.totalPaginas;
    const actual = this.paginaActual;
    const pages: number[] = [];

    if (total <= 5) {
      for (let i = 1; i <= total; i++) pages.push(i);
      return pages;
    }

    pages.push(1);
    if (actual > 3) pages.push(-1);  // elipsis
    const inicio = Math.max(2, actual - 1);
    const fin    = Math.min(total - 1, actual + 1);
    for (let i = inicio; i <= fin; i++) pages.push(i);
    if (actual < total - 2) pages.push(-1);  // elipsis
    pages.push(total);
    return pages;
  }

  // ── trackBy para performance ──────────────────
  trackById(_index: number, item: RegistroAsistencia): string {
    return item.id;
  }

  // ── Exportar CSV (stub para conectar luego) ───
  exportarDatos(): void {
    const cabecera = 'Nombre,Matrícula,Talla,Fecha,Hora\n';
    const filas = this.registrosFiltrados
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

