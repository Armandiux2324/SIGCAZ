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

  // ── Recientes ──
  cargarRecientes(): void {
    this.registrosRecientes = this.asistenciaService.getRecientes(4);
  }
async startScan(): Promise<void> {
  if (this.scanning) return;
  this.scanning = true;

  this.estado = 'escaneando';
  this.justRegistered = false;

  try {
    const qrRegionId = "qr-reader";

    this.qrScanner = new Html5Qrcode(qrRegionId);

    await this.qrScanner.start(
      { facingMode: "environment" },
      {
        fps: 10,
        qrbox: 250
      },
      (decodedText) => {
        this.registrarAsistencia(decodedText);
        this.stopScan(false); // auto-stop al leer QR
      },
      () => {}
    );

  } catch (err) {
    console.error("Error cámara PWA:", err);
    this.estado = 'error';
    this.scanning = false;
  }
}

async stopScan(resetEstado = true): Promise<void> {
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

  // ── Registrar asistencia ──
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

    this.resetTimeout = setTimeout(() => {
      this.resetScan();
    }, 3000);
  }

  // ── Reset UI ──
  resetScan(): void {
    clearTimeout(this.resetTimeout);
    this.estado = 'inactivo';
    this.justRegistered = false;
  }

}