import { AfterViewInit, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { Chart, ChartConfiguration, ChartType, registerables } from 'chart.js';
import { ApiService } from '../../../services/api.service';

Chart.register(...registerables);

// Tipografía y comportamiento por defecto para todas las gráficas del dashboard.
Chart.defaults.font.family = "'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#8a8790';
Chart.defaults.interaction.mode = 'nearest';
Chart.defaults.interaction.intersect = false;

type FilterOption = {
  value: string;
  label: string;
  reportPath?: string;
};

@Component({
  selector: 'app-dashboard',
  standalone: false,
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent implements AfterViewInit, OnDestroy {
  @ViewChild('mainChartCanvas') mainChartCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('yearChartCanvas') yearChartCanvas!: ElementRef<HTMLCanvasElement>;

  token = localStorage.getItem('accessToken') ?? '';

  // Tarjetas resumen
  loadingSummary = false;
  totalUsers = 0;
  totalRegisters = 0;
  attended = 0;
  pending = 0;
  showTypeMenu = false;

  // Colores fijos por género
  private readonly genderColors: Record<string, string> = {
    mujer: '#e49da7', // rosa
    femenino: '#e49da7',
    f: '#e49da7',
    hombre: '#8ec0d2', // azul
    masculino: '#8ec0d2',
    m: '#8ec0d2',
  };

  // Gráfica principal
// Gráfica principal
filterOptions: FilterOption[] = [
  { value: 'registered', label: 'Registrados', reportPath: 'participants' },
  { value: 'gender', label: 'Género', reportPath: 'gender' },
  { value: 'shirt_size', label: 'Talla', reportPath: 'shirt-size' },
  { value: 'origin_type', label: 'Origen (Nacional/Estatal)' },
  { value: 'state', label: 'Estado', reportPath: 'state' },
  { value: 'municipality', label: 'Municipio', reportPath: 'municipality' },
  { value: 'group', label: 'Cuadrilla', reportPath: 'group' },
  { value: 'accommodation_type', label: 'Tipo de hospedaje', reportPath: 'accommodation' },
  { value: 'participation_count', label: 'Veces que han participado', reportPath: 'participation-count' },
];

selectedFilter = 'registered';
chartType: ChartType = 'bar';
loadingChart = false;

  // Filtro por año
  availableYears: string[] = [];
  selectedYear = ''; // '' = todos los años

  private mainChart: Chart | null = null;
  private yearChart: Chart | null = null;

  // Registros recientes
  loadingRecent = false;
  recentRegisters: { name: string; location: string; folio: string }[] = [];

  // Reportes descargables
  reports: { label: string; path: string; downloading: boolean }[] = [
    { label: 'Participantes registrados', path: 'participants', downloading: false },
    { label: 'Participantes por género', path: 'gender', downloading: false },
    { label: 'Participantes por talla', path: 'shirt-size', downloading: false },
    { label: 'Participantes por estado', path: 'state', downloading: false },
    { label: 'Participantes por municipio', path: 'municipality', downloading: false },
    { label: 'Participantes por cuadrilla a la que pertenecen', path: 'group', downloading: false },
    { label: 'Participantes por tipo de hospedaje (Airbnb, Hotel, Casa propia o de familiar y/o amigos)', path: 'accommodation', downloading: false },
    { label: 'Participantes por cantidad de veces que han participado antes', path: 'participation-count', downloading: false },
    { label: 'Asistencia/Inasistencia', path: 'attendance', downloading: false },
  ];

  constructor(private api: ApiService) {}

  ngAfterViewInit(): void {
    this.loadAvailableYears();
    this.loadSummary();
    this.loadRecentRegisters();
    this.loadMainChart();
    this.loadYearChart();
  }

  ngOnDestroy(): void {
    this.mainChart?.destroy();
    this.yearChart?.destroy();
  }

  loadAvailableYears(): void {
    this.api.getStatsByYear(this.token).then((res: any) => {
      const { labels } = res.data.data;
      this.availableYears = (labels ?? []).slice().sort((a: string, b: string) => Number(b) - Number(a));
    }).catch(() => {});
  }

  onYearChange(): void {
    this.loadSummary();
    this.loadMainChart();
  }

  loadSummary(): void {
    this.loadingSummary = true;
    this.api.getStatsSummary(this.token, this.selectedYear).then((res: any) => {
      const data = res.data.data;
      this.totalUsers = data.total_users;
      this.totalRegisters = data.total_registers;
      this.attended = data.attended;
      this.pending = data.pending;
      this.loadingSummary = false;
    }).catch(() => {
      this.loadingSummary = false;
    });
  }

  loadRecentRegisters(): void {
    this.loadingRecent = true;
    this.api.getRecentRegisters(5, this.token).then((res: any) => {
      const registers = res.data.data.data ?? [];
      this.recentRegisters = registers.flatMap((r: any) =>
        (r.participants ?? []).map((p: any) => ({
          name: `${p.first_name} ${p.last_name}`,
          location: `${r.municipality}, ${r.state}`,
          folio: p.folio,
        }))
      ).slice(0, 5);
      this.loadingRecent = false;
    }).catch(() => {
      this.recentRegisters = [];
      this.loadingRecent = false;
    });
  }

  onFilterChange(): void {
    this.loadMainChart();
  }

  onChartTypeChange(type: ChartType): void {
    this.chartType = type;
    this.loadMainChart();
  }

  loadMainChart(): void {
    this.loadingChart = true;
    this.api.getStatsChart(this.selectedFilter, this.token, this.selectedYear).then((res: any) => {
      const { labels, values } = res.data.data;
      this.renderChart(this.mainChartCanvas.nativeElement, labels, values);
      this.loadingChart = false;
    }).catch(() => {
      this.loadingChart = false;
    });
  }

  loadYearChart(): void {
    this.api.getStatsByYear(this.token).then((res: any) => {
      const { labels, values } = res.data.data;
      this.renderYearChart(this.yearChartCanvas.nativeElement, labels, values);
    }).catch(() => {});
  }

  /**
   * Devuelve el color correspondiente a una etiqueta de género (rosa/azul),
   * o null si la etiqueta no corresponde a un género reconocido.
   */
  private getGenderColor(label: string): string | null {
    const key = (label ?? '').toString().trim().toLowerCase();
    return this.genderColors[key] ?? null;
  }

  /**
   * Tooltip moderno y consistente para todas las gráficas
   * (fondo oscuro translúcido, esquinas redondeadas, tipografía cuidada).
   */
  private tooltipOptions() {
    return {
      enabled: true,
      backgroundColor: 'rgba(38, 36, 42, 0.92)',
      titleColor: '#fff',
      bodyColor: 'rgba(255,255,255,0.85)',
      titleFont: { weight: 'bold' as const, size: 12.5 },
      bodyFont: { size: 12 },
      padding: 12,
      cornerRadius: 10,
      boxPadding: 6,
      caretSize: 6,
      displayColors: true,
      usePointStyle: true,
      borderColor: 'rgba(255,255,255,0.08)',
      borderWidth: 1,
    };
  }

  /**
   * Delay progresivo por punto/barra para que la gráfica "crezca" en cascada
   * en vez de aparecer toda de golpe.
   */
  private staggerDelay(totalPoints: number, totalDurationMs = 900) {
    return (context: any) => {
      if (context.type !== 'data') return 0;
      const perItem = totalPoints > 0 ? totalDurationMs / totalPoints : 0;
      return context.dataIndex * perItem + context.datasetIndex * 60;
    };
  }

  private renderChart(canvas: HTMLCanvasElement, labels: string[], values: number[]): void {
    this.mainChart?.destroy();

    const defaultPalette = ['#eb815b', '#8ec0d2', '#efc255', '#9dbe9e', '#e49da7', '#d2b68e', '#913e3f', '#504f51'];
    const isGenderFilter = this.selectedFilter === 'gender';

    // Si el filtro actual es género, usamos rosa/azul según la etiqueta;
    // si alguna etiqueta no coincide con un género conocido, se usa el color por defecto.
    const backgroundColor = isGenderFilter
      ? labels.map((label, i) => this.getGenderColor(label) ?? defaultPalette[i % defaultPalette.length])
      : (this.chartType === 'line' ? 'rgba(235,129,91,0.15)' : defaultPalette);

    const borderColor = isGenderFilter
      ? labels.map((label, i) => this.getGenderColor(label) ?? defaultPalette[i % defaultPalette.length])
      : '#eb815b';

    const isPie = this.chartType === 'pie';
    const isLine = this.chartType === 'line';
    const isBar = this.chartType === 'bar';
    const pointCount = values.length;

    // Animación específica según el tipo de gráfica:
    // - Pastel: aparición radial (rotación + escala) vía options.animation.
    // - Línea: efecto de "dibujado" de izquierda a derecha, punto por punto,
    //   vía options.animations (config por propiedad x/y).
    // - Barra: crecimiento progresivo vía options.animation.delay (staggerDelay).
    const animationConfig: any = isPie
      ? {
          duration: 900,
          easing: 'easeOutQuart',
          animateRotate: true,
          animateScale: true,
        }
      : isLine
      ? { duration: 900, easing: 'easeOutQuart' }
      : {
          duration: 900,
          easing: 'easeOutQuart',
          delay: this.staggerDelay(pointCount),
        };

    const perPropertyAnimations: any = isLine
      ? {
          x: {
            type: 'number',
            easing: 'linear',
            duration: 900,
            from: NaN,
            delay: (ctx: any) => {
              if (ctx.type !== 'data' || ctx.xStarted) return 0;
              ctx.xStarted = true;
              return (ctx.index * 900) / Math.max(pointCount, 1);
            },
          },
          y: {
            type: 'number',
            easing: 'easeOutQuart',
            duration: 900,
            delay: (ctx: any) => {
              if (ctx.type !== 'data' || ctx.yStarted) return 0;
              ctx.yStarted = true;
              return (ctx.index * 900) / Math.max(pointCount, 1);
            },
          },
        }
      : undefined;

    const config: ChartConfiguration = {
      type: this.chartType,
      data: {
        labels,
        datasets: [{
          label: 'Participantes',
          data: values,
          backgroundColor,
          borderColor,
          borderWidth: isLine ? 3 : (isPie ? 2 : 1),
          borderRadius: isBar ? 8 : 0,
          borderSkipped: false,
          maxBarThickness: isBar ? 46 : undefined,
          tension: 0.4,
          fill: isLine,
          pointRadius: isLine ? 4 : 0,
          pointHoverRadius: isLine ? 7 : 0,
          pointBackgroundColor: '#fff',
          pointBorderColor: borderColor as any,
          pointBorderWidth: 2,
          pointHoverBorderWidth: 3,
          hoverBackgroundColor: isPie ? backgroundColor : undefined,
          hoverBorderWidth: isBar ? 2 : (isLine ? 3 : 3),
          hoverOffset: isPie ? 14 : 0,
          spacing: isPie ? 2 : 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: animationConfig,
        ...(perPropertyAnimations ? { animations: perPropertyAnimations } : {}),
        transitions: {
          active: { animation: { duration: 250 } },
        },
        interaction: { mode: 'nearest', intersect: isPie },
        plugins: {
          legend: {
            display: isPie,
            position: 'bottom',
            labels: {
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 16,
              font: { size: 12 },
            },
          },
          tooltip: this.tooltipOptions() as any,
        },
        scales: isPie ? {} : {
          x: {
            grid: { display: false },
            border: { display: false } as any,
            ticks: { padding: 8 },
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(80,79,81,0.08)' } as any,
            border: { display: false } as any,
            ticks: { padding: 8, precision: 0 } as any,
          },
        },
      },
    };

    this.mainChart = new Chart(canvas, config);
  }

  private renderYearChart(canvas: HTMLCanvasElement, labels: string[], values: number[]): void {
    this.yearChart?.destroy();

    const pointCount = values.length;

    this.yearChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Registros',
          data: values,
          borderColor: '#8ec0d2',
          backgroundColor: 'rgba(142,192,210,0.18)',
          borderWidth: 3,
          tension: 0.4,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#8ec0d2',
          pointBorderWidth: 2,
          pointHoverBorderWidth: 3,
          hoverBorderWidth: 3,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 900, easing: 'easeOutQuart' },
        animations: {
          x: {
            type: 'number',
            easing: 'linear',
            duration: 900,
            from: NaN,
            delay: (ctx: any) => {
              if (ctx.type !== 'data' || ctx.xStarted) return 0;
              ctx.xStarted = true;
              return (ctx.index * 900) / Math.max(pointCount, 1);
            },
          },
          y: {
            type: 'number',
            easing: 'easeOutQuart',
            duration: 900,
            delay: (ctx: any) => {
              if (ctx.type !== 'data' || ctx.yStarted) return 0;
              ctx.yStarted = true;
              return (ctx.index * 900) / Math.max(pointCount, 1);
            },
          },
        },
        transitions: {
          active: { animation: { duration: 250 } },
        },
        interaction: { mode: 'nearest', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: this.tooltipOptions() as any,
        },
        scales: {
          x: {
            grid: { display: false },
            border: { display: false } as any,
            ticks: { padding: 8 },
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(80,79,81,0.08)' } as any,
            border: { display: false } as any,
            ticks: { padding: 8, precision: 0 } as any,
          },
        },
      },
    });
  }

  downloadReport(report: { label: string; path: string; downloading: boolean }): void {
    report.downloading = true;
    this.api.downloadReport(report.path, this.token, this.selectedYear).then((res: any) => {
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      const suffix = this.selectedYear ? `_${this.selectedYear}` : '';
      link.download = `${report.path}${suffix}.xlsx`;
      link.click();
      window.URL.revokeObjectURL(url);
      report.downloading = false;
    }).catch(() => {
      report.downloading = false;
    });
  }

  get currentFilterHasReport(): boolean {
    const option = this.filterOptions.find(o => o.value === this.selectedFilter);
    return !!option?.reportPath;
  }

  get currentFilterLabel(): string {
    return this.filterOptions.find(o => o.value === this.selectedFilter)?.label ?? 'Participantes';
  }

  exportCurrentChart(): void {
    const option = this.filterOptions.find(o => o.value === this.selectedFilter);
    if (!option?.reportPath) {
      return;
    }
    const report = this.reports.find(r => r.path === option.reportPath);
    if (report) {
      this.downloadReport(report);
    }
  }
}