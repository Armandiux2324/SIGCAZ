import { AfterViewInit, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { Chart, ChartConfiguration, ChartType, registerables } from 'chart.js';
import { ApiService } from '../../../services/api.service';

Chart.register(...registerables);

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

  // Gráfica principal
  filterOptions: FilterOption[] = [
    { value: 'registered', label: 'Participantes registrados', reportPath: 'participants' },
    { value: 'gender', label: 'Género', reportPath: 'gender' },
    { value: 'shirt_size', label: 'Talla', reportPath: 'shirt-size' },
    { value: 'origin_type', label: 'Origen (Nacional/Estatal)' },
    { value: 'state', label: 'Estado', reportPath: 'state' },
    { value: 'municipality', label: 'Municipio', reportPath: 'municipality' },
    { value: 'group', label: 'Cuadrilla', reportPath: 'group' },
    { value: 'accommodation_type', label: 'Tipo de hospedaje', reportPath: 'accommodation' },
    { value: 'participation_count', label: 'Veces que han participado', reportPath: 'participation-count' },
  ];

  selectedFilter = 'gender';
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

  private renderChart(canvas: HTMLCanvasElement, labels: string[], values: number[]): void {
    this.mainChart?.destroy();

    const palette = ['#6B1B1B', '#1a1a1a', '#a83232', '#4a4a4a', '#c96a6a', '#7d7d7d', '#8f2323', '#b5b5b5'];

    const config: ChartConfiguration = {
      type: this.chartType,
      data: {
        labels,
        datasets: [{
          label: 'Participantes',
          data: values,
          backgroundColor: this.chartType === 'line' ? 'rgba(107,27,27,0.15)' : palette,
          borderColor: '#6B1B1B',
          borderWidth: this.chartType === 'line' ? 2 : 1,
          tension: 0.35,
          fill: this.chartType === 'line',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: this.chartType === 'pie' },
        },
        scales: this.chartType === 'pie' ? {} : {
          y: { beginAtZero: true },
        },
      },
    };

    this.mainChart = new Chart(canvas, config);
  }

  private renderYearChart(canvas: HTMLCanvasElement, labels: string[], values: number[]): void {
    this.yearChart?.destroy();

    this.yearChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Registros',
          data: values,
          borderColor: '#6B1B1B',
          backgroundColor: 'rgba(107,27,27,0.15)',
          tension: 0.35,
          fill: true,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
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