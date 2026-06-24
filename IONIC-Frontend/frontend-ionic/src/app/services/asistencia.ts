import { Injectable } from '@angular/core';

// ── Modelo de datos ──────────────────────────────────────────────────
export interface RegistroAsistencia {
  id: string;
  nombre: string;
  matricula: string;
  talla: string;
  fecha: string;
  hora: string;
  qrRaw: string;
  timestamp: number;
}

// ── Datos de muestra (seed) ──────────────────────────────────────────
const NOMBRES_MOCK = [
  'Juan Pérez', 'María García', 'Carlos López', 'Ana Martínez',
  'Luis Rodríguez', 'Sofía Hernández', 'Miguel Torres', 'Valeria Díaz',
  'Jorge Sánchez', 'Fernanda Reyes', 'Pedro Gómez', 'Daniela Cruz',
  'Alejandro Ruiz', 'Mariana Flores', 'Rodrigo Moreno', 'Isabella Vargas',
];

const TALLAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

// ── Prefijo para QR válidos ──────────────────────────────────────────
const QR_PREFIX = 'ASISTENCIA:';

@Injectable({ providedIn: 'root' })
export class AsistenciaService {

  private registros: RegistroAsistencia[] = [];

  constructor() {
    this.seedDatos();
  }

  // ── Genera registros de demostración ──────────────────────────────
  private seedDatos(): void {
    const hoy = new Date();

    for (let i = 0; i < 18; i++) {
      const daysAgo = Math.floor(i / 4);
      const fecha = new Date(hoy);
      fecha.setDate(hoy.getDate() - daysAgo);

      const hora = new Date(fecha);
      hora.setMinutes(hora.getMinutes() - (i * 15));

      const nombre = NOMBRES_MOCK[i % NOMBRES_MOCK.length];
      const matricula = `${2648 + i}U`;

      this.registros.unshift({
        id: `seed-${i}`,
        nombre,
        matricula,
        talla: TALLAS[i % TALLAS.length],
        fecha: fecha.toLocaleDateString('es-MX'),
        hora: hora.toLocaleTimeString('es-MX', {
          hour: '2-digit',
          minute: '2-digit'
        }),
        qrRaw: `${QR_PREFIX}${matricula}:${nombre.replace(/ /g, '_')}:${TALLAS[i % TALLAS.length]}`,
        timestamp: hora.getTime()
      });
    }
  }

  // ── Retorna todos los registros (más recientes primero) ───────────
  getTodos(): RegistroAsistencia[] {
    return [...this.registros]
      .sort((a, b) => b.timestamp - a.timestamp);
  }

  // ── Retorna los N más recientes para el preview ───────────────────
  getRecientes(n: number): RegistroAsistencia[] {
    return this.getTodos().slice(0, n);
  }

  // ── Valida si el string tiene el prefijo correcto ─────────────────
  validarQR(qrData: string): boolean {
    return qrData.startsWith(QR_PREFIX) && qrData.split(':').length >= 4;
  }

  // ── Registra una nueva asistencia desde el QR escaneado ──────────
  registrar(qrData: string): RegistroAsistencia | null {
    if (!this.validarQR(qrData)) return null;

    // Formato: ASISTENCIA:MATRICULA:NOMBRE_CON_GUIONES:TALLA
    const partes = qrData.replace(QR_PREFIX, '').split(':');
    const matricula = partes[0] ?? 'N/A';
    const nombre = (partes[1] ?? 'Desconocido').replace(/_/g, ' ');
    const talla = partes[2] ?? 'M';

    const ahora = new Date();
    const registro: RegistroAsistencia = {
      id: `r-${Date.now()}`,
      nombre,
      matricula,
      talla,
      fecha: ahora.toLocaleDateString('es-MX'),
      hora: ahora.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit'
      }),
      qrRaw: qrData,
      timestamp: ahora.getTime()
    };

    this.registros.unshift(registro);
    return registro;
  }

  // ── Genera un QR mock válido para pruebas ─────────────────────────
  generarQRMock(): string {
    const idx = Math.floor(Math.random() * NOMBRES_MOCK.length);
    const nombre = NOMBRES_MOCK[idx].replace(/ /g, '_');
    const matricula = `${2648 + Math.floor(Math.random() * 50)}U`;
    const talla = TALLAS[Math.floor(Math.random() * TALLAS.length)];
    // 80% de probabilidad de QR válido, 20% de QR inválido
    return Math.random() < 0.8
      ? `${QR_PREFIX}${matricula}:${nombre}:${talla}`
      : 'QR_INVALIDO_DEMO';
  }

}
