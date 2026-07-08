// src/app/pages/asistencia/asistencia.page.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import {
  AsistenciaService,
  RegistroAsistencia,
  ScanRegistradoResponse
} from '../../services/asistencia';
import { Html5Qrcode } from 'html5-qrcode';

export type EstadoEscaner = 'inactivo' | 'escaneando' | 'exito' | 'error';

@Component({
  selector: 'app-asistencia',
  templateUrl: './asistencia.page.html',
  styleUrls: ['./asistencia.page.scss'],
  standalone: false
})
export class AsistenciaPage implements OnInit, OnDestroy {

  estado: EstadoEscaner = 'inactivo';
  registrosRecientes: RegistroAsistencia[] = [];
  justRegistered = false;

  // Datos del último participante registrado (para mostrar la card verde)
  ultimoRegistro: ScanRegistradoResponse['data'] | null = null;

  // Mensajes de error provenientes de Laravel
  mensajeError = '';
  cargando     = false;

  // Flash
  flashActivo = false;

  private resetTimeout: any;
  private qrScanner: Html5Qrcode | null = null;
  private scanning = false;

  constructor(
    private router: Router,
    private asistenciaService: AsistenciaService
  ) {}

  ngOnInit(): void {
    // Carga el historial reciente desde la API al abrir la pantalla
    this.cargarRecientes();
  }

  ngOnDestroy(): void {
    clearTimeout(this.resetTimeout);
    this.stopScan();
  }

  // ── Carga los 4 registros más recientes desde Laravel ─────────────────────
  cargarRecientes(): void {
    this.asistenciaService.getScanList({ page: 1 }).subscribe({
      next: (items) => {
        // Tomamos solo los primeros 4 y los mapeamos al formato del template
        this.registrosRecientes = items
          .slice(0, 4)
          .map(item => this.asistenciaService.mapearRegistro(item));
      },
      error: () => {
        // Si falla (sin conexión al iniciar), dejamos la lista vacía sin romper
        this.registrosRecientes = [];
      }
    });
  }

  // ── Iniciar escáner ────────────────────────────────────────────────────────
  async startScan(): Promise<void> {
    if (this.scanning) return;
    this.scanning        = true;
    this.estado          = 'escaneando';
    this.justRegistered  = false;
    this.mensajeError    = '';
    this.ultimoRegistro  = null;

    try {
      this.qrScanner = new Html5Qrcode('qr-reader');

      await this.qrScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 430, height: 430 } },
        (decodedText) => {
          // Detener antes de llamar a la API para no leer el mismo QR dos veces
          this.stopScan(false);
          this.registrarAsistencia(decodedText);
        },
        () => {}
      );

      if (this.flashActivo) await this.aplicarFlash(true);

    } catch (err) {
      console.error('Error cámara PWA:', err);
      this.estado   = 'error';
      this.mensajeError = 'No se pudo acceder a la cámara.';
      this.scanning = false;
    }
  }

  // ── Detener escáner ────────────────────────────────────────────────────────
  async stopScan(resetEstado = true): Promise<void> {
    if (this.flashActivo) {
      await this.aplicarFlash(false).catch(() => {});
      this.flashActivo = false;
    }

    try {
      if (this.qrScanner) {
        await this.qrScanner.stop();
        await this.qrScanner.clear();
        this.qrScanner = null;
      }
    } catch (err) {
      console.warn(err);
    }

    if (resetEstado) this.estado = 'inactivo';
    this.scanning = false;
  }

  // ── Toggle flash ───────────────────────────────────────────────────────────
  async toggleFlash(): Promise<void> {
    this.flashActivo = !this.flashActivo;
    if (this.scanning && this.qrScanner) {
      await this.aplicarFlash(this.flashActivo);
    }
  }

  private async aplicarFlash(encender: boolean): Promise<void> {
    try {
      const stream: MediaStream | undefined =
        (this.qrScanner as any)?.['mediaStream'] ??
        (this.qrScanner as any)?.['localMediaStream'];
      if (!stream) return;

      const [track] = stream.getVideoTracks();
      const caps = track?.getCapabilities?.() as any;
      if (!caps?.torch) return;

      await track.applyConstraints({ advanced: [{ torch: encender } as any] });
    } catch (err) {
      console.warn('Flash no disponible:', err);
    }
  }

  // ── Recargar cámara ────────────────────────────────────────────────────────
  async recargarCamara(): Promise<void> {
    if (this.scanning) {
      await this.stopScan(false);
      this.scanning = false;
      await this.startScan();
    } else {
      if (this.qrScanner) {
        try { await this.qrScanner.clear(); } catch { }
        this.qrScanner = null;
      }
      this.estado = 'inactivo';
    }
  }

  // ── Llama POST /api/v1/scans con el folio leído del QR ────────────────────
  registrarAsistencia(folio: string): void {
    this.cargando     = true;
    this.mensajeError = '';

    this.asistenciaService.registrarScan(folio).subscribe({
      next: (res) => {
        this.cargando = false;

        if (res.status === 'valid') {
          this.estado         = 'exito';
          this.justRegistered = true;
          this.ultimoRegistro = res.data;

          // Agrega al preview local sin llamar de nuevo a la API
          const nuevo = this.asistenciaService.mapearDesdePost(res);
          this.registrosRecientes = [nuevo, ...this.registrosRecientes].slice(0, 4);

          // Auto-reset a los 3 s
          this.resetTimeout = setTimeout(() => this.resetScan(), 3000);

        } else {
          // La API respondió 200 pero con status != valid (ya registrado, etc.)
          this.estado       = 'error';
          this.mensajeError = res.message;
        }
      },
      error: (err) => {
        this.cargando = false;
        this.estado   = 'error';

        if (err.status === 422) {
          this.mensajeError = err.error?.message ?? 'QR inválido.';
        } else if (err.status === 409) {
          this.mensajeError = err.error?.message ?? 'Asistencia ya registrada.';
        } else if (err.status === 0) {
          this.mensajeError = 'Sin conexión al servidor.';
        } else {
          this.mensajeError = err.error?.message ?? 'Error inesperado.';
        }
      }
    });
  }

  // ── Reset UI ───────────────────────────────────────────────────────────────
  resetScan(): void {
    clearTimeout(this.resetTimeout);
    this.estado         = 'inactivo';
    this.justRegistered = false;
    this.ultimoRegistro = null;
    this.mensajeError   = '';
  }
}