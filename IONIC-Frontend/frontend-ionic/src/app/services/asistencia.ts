// src/app/services/asistencia.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface Participante {
  folio:      string;
  first_name: string;
  last_name:  string;
  shirt_size: string;
  gender:     string;
}

// Respuesta del POST /api/v1/scans
export interface ScanRegistradoResponse {
  message: string;
  status:  'valid' | 'invalid' | string;
  data:    Participante;
}

// Cada fila del GET /api/v1/scans
export interface ScanItem {
  id:          number;
  scanned_at:  string;       // ISO 8601 → "2026-07-03T06:24:59.000000Z"
  status:      string;
  participant: Participante;
}

// Paginación Laravel (LengthAwarePaginator)
export interface PaginacionLaravel {
  current_page:   number;
  data:           ScanItem[];
  first_page_url: string;
  from:           number;
  last_page:      number;
  last_page_url:  string;
  next_page_url:  string | null;
  prev_page_url:  string | null;
  path:           string;
  per_page:       number;
  to:             number;
  total:          number;
  links: {
    url:    string | null;
    label:  string;
    page:   number | null;
    active: boolean;
  }[];
}

export interface HistorialResponse {
  message: string;
  data:    PaginacionLaravel;
}

// Parámetros opcionales del GET historial
export interface HistorialParams {
  page?:   number;
  search?: string;
  fecha?:  string;   // YYYY-MM-DD
}

// Modelo que usan los components internamente (construido desde ScanItem)
export interface RegistroAsistencia {
  id:        string;
  nombre:    string;
  matricula: string;   // folio
  talla:     string;
  fecha:     string;   // formateada "dd/mm/yyyy"
  hora:      string;   // formateada "HH:MM"
}

// ─────────────────────────────────────────────────────────────────────────────
// Service
// ─────────────────────────────────────────────────────────────────────────────

@Injectable({ providedIn: 'root' })
export class AsistenciaService {

  private readonly base = environment.base_url;

  constructor(private http: HttpClient) {}

  // ── POST /api/v1/scans ────────────────────────────────────────────────────
  // Envía el folio leído del QR y recibe los datos del participante
  registrarScan(folio: string): Observable<ScanRegistradoResponse> {
    return this.http
      .post<ScanRegistradoResponse>(`${this.base}/scans`, { folio })
      .pipe(catchError(err => throwError(() => err)));
  }

  // ── GET /api/v1/scans ─────────────────────────────────────────────────────
  // Devuelve el historial paginado con filtros opcionales
  getHistorial(params: HistorialParams = {}): Observable<HistorialResponse> {
    let httpParams = new HttpParams();

    if (params.page)   httpParams = httpParams.set('page',   params.page.toString());
    if (params.search) httpParams = httpParams.set('search', params.search);
    if (params.fecha)  httpParams = httpParams.set('fecha',  params.fecha);

    return this.http
      .get<HistorialResponse>(`${this.base}/scans`, { params: httpParams })
      .pipe(catchError(err => throwError(() => err)));
  }

  // ── Helper: solo el array de ScanItem ────────────────────────────────────
  getScanList(params: HistorialParams = {}): Observable<ScanItem[]> {
    return this.getHistorial(params).pipe(
      map(res => res.data.data)
    );
  }

  // ── Convierte ScanItem → RegistroAsistencia (formato para los components) ─
  // Úsalo cuando necesites mostrar datos de la API en el mismo formato
  // que usa asistencia.page y historial.page internamente.
  mapearRegistro(item: ScanItem): RegistroAsistencia {
    const fecha = new Date(item.scanned_at);
    return {
      id:        item.id.toString(),
      nombre:    `${item.participant.first_name} ${item.participant.last_name}`,
      matricula: item.participant.folio,
      talla:     item.participant.shirt_size,
      fecha:     fecha.toLocaleDateString('es-MX'),
      hora:      fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }),
    };
  }

  // ── Construye un RegistroAsistencia desde la respuesta del POST ───────────
  // Útil en asistencia.page para agregar el nuevo registro al preview local
  // sin tener que volver a llamar al GET.
  mapearDesdePost(res: ScanRegistradoResponse): RegistroAsistencia {
    const ahora = new Date();
    return {
      id:        `local-${Date.now()}`,
      nombre:    `${res.data.first_name} ${res.data.last_name}`,
      matricula: res.data.folio,
      talla:     res.data.shirt_size,
      fecha:     ahora.toLocaleDateString('es-MX'),
      hora:      ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }),
    };
  }
}