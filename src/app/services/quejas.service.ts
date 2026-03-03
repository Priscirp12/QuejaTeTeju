// src/app/services/quejas.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';
import { environment } from '../../environments/environment';

export interface Queja {
  id: number;
  usuario_id: number;
  titulo: string;
  descripcion: string;
  categoria: string;
  tipo: string;
  estatus: 'Pendiente' | 'En Proceso' | 'Resuelta' | 'Rechazada';
  prioridad: 'Baja' | 'Media' | 'Alta';
  ubicacion_direccion: string;
  ubicacion_latitud?: number;
  ubicacion_longitud?: number;
  fecha_creacion: string;
  fecha_actualizacion: string;
  total_archivos: number;
}

export interface Estadisticas {
  total_quejas: number;
  pendientes: number;
  en_proceso: number;
  resueltas: number;
  rechazadas: number;
}

@Injectable({
  providedIn: 'root'
})
export class QuejasService {
  private apiUrl = environment.apiUrl; // '/api' via proxy or same origin

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) { }

  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken();
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  /**
   * Obtener quejas del usuario actual
   */
  getMisQuejas(estatus?: string, categoria?: string): Observable<any> {
    let params = new HttpParams();

    if (estatus) {
      params = params.set('estatus', estatus);
    }
    if (categoria) {
      params = params.set('categoria', categoria);
    }

    return this.http.get(`${this.apiUrl}/quejas/mis-quejas.php`, {
      headers: this.getHeaders(),
      params: params
    });
  }

  /**
   * Crear nueva queja con archivos
   */
  crearQueja(quejaData: any, archivos: File[]): Observable<any> {
    const formData = new FormData();

    // Agregar datos de la queja
    formData.append('titulo', quejaData.titulo);
    formData.append('descripcion', quejaData.descripcion);
    formData.append('categoria', quejaData.categoria);
    formData.append('tipo', quejaData.tipo);
    formData.append('prioridad', quejaData.prioridad || 'Media');
    formData.append('ubicacion_direccion', quejaData.ubicacion_direccion);

    if (quejaData.ubicacion_latitud) {
      formData.append('ubicacion_latitud', quejaData.ubicacion_latitud.toString());
    }
    if (quejaData.ubicacion_longitud) {
      formData.append('ubicacion_longitud', quejaData.ubicacion_longitud.toString());
    }

    // Agregar archivos
    archivos.forEach((archivo, index) => {
      formData.append('archivos[]', archivo, archivo.name);
    });

    // Headers sin Content-Type (el navegador lo establece automáticamente con boundary)
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${this.authService.getToken()}`
    });

    return this.http.post(`${this.apiUrl}/quejas/crear.php`, formData, {
      headers: headers
    });
  }

  /**
   * Obtener detalle de una queja
   */
  getQuejaDetalle(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/quejas/detalle.php?id=${id}`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Obtener estadísticas del usuario
   */
  getEstadisticas(): Observable<any> {
    return this.http.get(`${this.apiUrl}/estadisticas/usuario.php`, {
      headers: this.getHeaders()
    });
  }

  /**
   * Obtener todas las quejas (solo admin)
   */
  getTodasQuejas(estatus?: string, categoria?: string): Observable<any> {
    let params = new HttpParams();

    if (estatus) {
      params = params.set('estatus', estatus);
    }
    if (categoria) {
      params = params.set('categoria', categoria);
    }

    return this.http.get(`${this.apiUrl}/quejas/todas.php`, {
      headers: this.getHeaders(),
      params: params
    });
  }

  /**
   * Actualizar estatus de una queja (solo admin)
   */
  actualizarEstatus(quejaId: number, estatus: string, comentario?: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/quejas/actualizar-estatus.php`, {
      queja_id: quejaId,
      estatus: estatus,
      comentario: comentario || ''
    }, {
      headers: this.getHeaders()
    });
  }

  /**
   * Filtrar quejas
   */
  filtrarQuejas(filtros: {
    estatus?: string;
    categoria?: string;
    fecha_desde?: string;
    fecha_hasta?: string;
  }): Observable<any> {
    let params = new HttpParams();

    if (filtros.estatus) params = params.set('estatus', filtros.estatus);
    if (filtros.categoria) params = params.set('categoria', filtros.categoria);
    if (filtros.fecha_desde) params = params.set('fecha_desde', filtros.fecha_desde);
    if (filtros.fecha_hasta) params = params.set('fecha_hasta', filtros.fecha_hasta);

    return this.http.get(`${this.apiUrl}/quejas/mis-quejas.php`, {
      headers: this.getHeaders(),
      params: params
    });
  }
}