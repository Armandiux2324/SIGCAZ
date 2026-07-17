// src/app/services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { environment } from '../../environments/environment';

export interface LoginResponse {
  access_token: string;
  token_type:   string;
  user:         { id: number; name: string; email: string; };
}

@Injectable({ providedIn: 'root' })
export class AuthService {

  private readonly base = environment.base_url;
  private readonly TOKEN_KEY = 'access_token';

  constructor(private http: HttpClient) {}

  // ── POST /api/v1/login ────────────────────────────────────────────────────
  login(email: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.base}/login`, { email, password }).pipe(
        tap(res => this.guardarToken(res.access_token))
      );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.base}/logout`, {}).pipe(
      tap(() => this.eliminarToken())
    );
  }

  guardarToken(token: string): void {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  eliminarToken(): void {
    localStorage.removeItem(this.TOKEN_KEY);
  }

  estaAutenticado(): boolean {
    return !!this.getToken();
  }
}
