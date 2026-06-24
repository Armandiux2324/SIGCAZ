import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { AsistenciaService, RegistroAsistencia } from '../../services/asistencia';
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

  // ── Estado del flash ──
  flashActivo = false;

  private scanTimeout: any;
  private resetTimeout: any;
  private qrScanner: Html5Qrcode | null = null;
  private scanning = false;

  constructor(
    private router: Router,
    private asistenciaService: AsistenciaService
  ) {}

  ngOnInit(): void {
    this.cargarRecientes();
  }

  ngOnDestroy(): void {
    clearTimeout(this.scanTimeout);
    clearTimeout(this.resetTimeout);
    this.stopScan();
  }

  // ── Recientes ──────────────────────────────────────────────────────────
  cargarRecientes(): void {
    this.registrosRecientes = this.asistenciaService.getRecientes(4);
  }

  // ── Iniciar escáner ────────────────────────────────────────────────────
  async startScan(): Promise<void> {
    if (this.scanning) return;
    this.scanning = true;
    this.estado = 'escaneando';
    this.justRegistered = false;

    try {
      this.qrScanner = new Html5Qrcode('qr-reader');

    await this.qrScanner.start(
  { facingMode: 'environment' },
  {
    fps: 10,
    qrbox: {
      width: 430,
      height: 430
    }
  },
  (decodedText) => {
    this.registrarAsistencia(decodedText);
    this.stopScan(false);
  },
  () => {}
);

      // Aplicar flash si ya estaba encendido antes de iniciar
      if (this.flashActivo) {
        await this.aplicarFlash(true);
      }

    } catch (err) {
      console.error('Error cámara PWA:', err);
      this.estado = 'error';
      this.scanning = false;
    }
  }

  // ── Detener escáner ────────────────────────────────────────────────────
  async stopScan(resetEstado = true): Promise<void> {
    // Apagar flash antes de cerrar la cámara
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

    if (resetEstado) {
      this.estado = 'inactivo';
    }

    this.scanning = false;
  }

  // ── Toggle flash ───────────────────────────────────────────────────────
  async toggleFlash(): Promise<void> {
    this.flashActivo = !this.flashActivo;

    // Si la cámara está activa, aplicar cambio en tiempo real
    if (this.scanning && this.qrScanner) {
      await this.aplicarFlash(this.flashActivo);
    }
    // Si la cámara está inactiva, el estado queda guardado en flashActivo
    // y se aplicará en el próximo startScan()
  }

  // ── Aplicar flash al track de la cámara vía MediaStream ───────────────
  private async aplicarFlash(encender: boolean): Promise<void> {
    try {
      // Html5Qrcode expone el stream interno como propiedad privada;
      // accedemos con cast para no depender de una API pública inestable.
      const stream: MediaStream | undefined =
        (this.qrScanner as any)?.['mediaStream'] ??
        (this.qrScanner as any)?.['localMediaStream'];

      if (!stream) return;

      const [track] = stream.getVideoTracks();
      if (!track) return;

      // La API de torch está bajo ImageCapture o directamente en el track
      const capabilities = track.getCapabilities?.() as any;
      if (!capabilities?.torch) {
        console.warn('Este dispositivo no soporta flash/torch.');
        return;
      }

      await track.applyConstraints({ advanced: [{ torch: encender } as any] });
    } catch (err) {
      console.warn('Error al aplicar flash:', err);
    }
  }

  // ── Recargar cámara (detener y volver a iniciar) ───────────────────────
  async recargarCamara(): Promise<void> {
    if (this.scanning) {
      // Si estaba escaneando: detener sin resetear UI y relanzar
      await this.stopScan(false);
      this.scanning = false;
      await this.startScan();
    } else {
      // Si estaba inactiva: solo limpiar el contenedor por si quedó basura
      if (this.qrScanner) {
        try {
          this.qrScanner.clear();
        } catch {
          // ignore
        }
        this.qrScanner = null;
      }
      this.estado = 'inactivo';
    }
  }

  // ── Registrar asistencia ───────────────────────────────────────────────
  registrarAsistencia(qrData: string): void {
    const esValido = this.asistenciaService.validarQR(qrData);

    if (!esValido) {
      this.estado = 'error';
      return;
    }

    const registro = this.asistenciaService.registrar(qrData);

    if (!registro) {
      this.estado = 'error';
      return;
    }

    this.estado = 'exito';
    this.justRegistered = true;
    this.cargarRecientes();

    this.resetTimeout = setTimeout(() => this.resetScan(), 3000);
  }

  // ── Reset UI ───────────────────────────────────────────────────────────
  resetScan(): void {
    clearTimeout(this.resetTimeout);
    this.estado = 'inactivo';
    this.justRegistered = false;
  }
}