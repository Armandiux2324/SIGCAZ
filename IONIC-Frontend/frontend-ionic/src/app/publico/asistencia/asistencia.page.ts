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

  ultimoRegistro: ScanRegistradoResponse['data'] | null = null;


  mensajeError = '';
  cargando     = false;
  flashActivo = false;

  private resetTimeout: any;
  private qrScanner: Html5Qrcode | null = null;
  private scanning = false;
  private ultimoFolioMostrado: string | null = null;
  private ultimoScaneoTs = 0;

  constructor(
    private router: Router,
    private asistenciaService: AsistenciaService
  ) {}

  ngOnInit(): void {
    this.cargarRecientes();
  }

  ngOnDestroy(): void {
    clearTimeout(this.resetTimeout);
    this.stopScan();
  }

  cargarRecientes(): void {
    this.asistenciaService.getScanList({ page: 1 }).subscribe({
      next: (items) => {
        // Toma solo los primeros 4 
        this.registrosRecientes = items
          .slice(0, 4)
          .map(item => this.asistenciaService.mapearRegistro(item));
      },
      error: () => {
        this.registrosRecientes = [];
      }
    });
  }

  async startScan(): Promise<void> {
    if (this.scanning) return;
    this.scanning        = true;
    this.estado          = 'escaneando';
    this.justRegistered  = false;
    this.mensajeError    = '';
    this.ultimoRegistro  = null;
    this.ultimoFolioMostrado = null;
    clearTimeout(this.resetTimeout);

    try {
      this.qrScanner = new Html5Qrcode('qr-reader');

      await this.qrScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 430, height: 430 } },
        (decodedText) => {
          if (this.cargando) return;

          const ahora = Date.now();
          const mismoFolioReciente =
            decodedText === this.ultimoFolioMostrado &&
            (ahora - this.ultimoScaneoTs) < 60000;

          if (mismoFolioReciente) return;

          this.ultimoFolioMostrado = decodedText;
          this.ultimoScaneoTs = ahora;
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

  async stopScan(resetEstado = true): Promise<void> {
    clearTimeout(this.resetTimeout);
    this.ultimoFolioMostrado = null;

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

          const nuevo = this.asistenciaService.mapearDesdePost(res);
          this.registrosRecientes = [nuevo, ...this.registrosRecientes].slice(0, 4);

          clearTimeout(this.resetTimeout);
          this.resetTimeout = setTimeout(() => this.resetScan(), 60000);

        } else {
          this.estado = 'error';
          this.mensajeError = res.message;

          clearTimeout(this.resetTimeout);
          this.resetTimeout = setTimeout(() => this.resetScan(), 60000);
        }
      },
      error: (err) => {
        this.cargando = false;
        this.estado = 'error';

        if (err.status === 422) {
          this.mensajeError = err.error?.message ?? 'QR inválido.';
        } else if (err.status === 409) {
          this.mensajeError = err.error?.message ?? 'Asistencia ya registrada.';
        } else if (err.status === 0) {
          this.mensajeError = 'Sin conexión al servidor.';
        } else {
          this.mensajeError = err.error?.message ?? 'Error inesperado.';
        }

        clearTimeout(this.resetTimeout);
        this.resetTimeout = setTimeout(() => this.resetScan(), 60000);
      }
    });
  }

  resetScan(): void {
    clearTimeout(this.resetTimeout);
    this.estado             = this.scanning ? 'escaneando' : 'inactivo';
    this.justRegistered     = false;
    this.ultimoRegistro     = null;
    this.mensajeError       = '';
    this.ultimoFolioMostrado = null;
  }
}