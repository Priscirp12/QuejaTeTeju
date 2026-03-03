import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { IonicModule, AlertController, ToastController } from '@ionic/angular';
import { AuthService, User } from '../../services/auth.service';
import { QuejasService, Queja, Estadisticas } from '../../services/quejas.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss'],
  standalone: true,
  imports: [CommonModule, IonicModule, FormsModule, ReactiveFormsModule, RouterModule]
})
export class DashboardComponent implements OnInit {
  currentUser: User | null = null;
  quejas: Queja[] = [];
  estadisticas: Estadisticas = {
    total_quejas: 0,
    pendientes: 0,
    en_proceso: 0,
    resueltas: 0,
    rechazadas: 0
  };

  // Filtros
  filterStatus = '';
  filterCategory = '';

  // Formulario de nueva queja
  showNewQuejaForm = false;
  quejaForm!: FormGroup;
  archivosSeleccionados: File[] = [];
  creatingQueja = false;

  // Admin
  isAdmin = false;
  allQuejas: any[] = [];
  showAdminView = false;

  // Estado de carga
  loading = true;

  // Actualización de estatus (admin)
  updatingQuejaId: number | null = null;

  constructor(
    private authService: AuthService,
    private quejasService: QuejasService,
    private router: Router,
    private fb: FormBuilder,
    private alertController: AlertController,
    private toastController: ToastController
  ) { }

  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.isAdmin = this.authService.isAdmin();

    this.quejaForm = this.fb.group({
      titulo: ['', [Validators.required, Validators.minLength(5)]],
      descripcion: ['', [Validators.required, Validators.minLength(10)]],
      categoria: ['', Validators.required],
      tipo: ['', Validators.required],
      prioridad: ['Media'],
      ubicacion_direccion: ['', Validators.required]
    });

    this.loadData();
  }

  loadData() {
    this.loading = true;

    // Cargar estadísticas
    this.quejasService.getEstadisticas().subscribe({
      next: (res: any) => {
        if (res.success && res.estadisticas) {
          this.estadisticas = res.estadisticas;
        }
      },
      error: () => { }
    });

    // Cargar quejas del usuario
    this.quejasService.getMisQuejas().subscribe({
      next: (res: any) => {
        if (res.success) {
          this.quejas = res.quejas;
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });

    // Si es admin, cargar todas las quejas
    if (this.isAdmin) {
      this.quejasService.getTodasQuejas().subscribe({
        next: (res: any) => {
          if (res.success) {
            this.allQuejas = res.quejas;
          }
        },
        error: () => { }
      });
    }
  }

  filterQuejas() {
    this.quejasService.getMisQuejas(
      this.filterStatus || undefined,
      this.filterCategory || undefined
    ).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.quejas = res.quejas;
        }
      }
    });
  }

  filterAllQuejas() {
    this.quejasService.getTodasQuejas(
      this.filterStatus || undefined,
      this.filterCategory || undefined
    ).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.allQuejas = res.quejas;
        }
      }
    });
  }

  toggleNewQuejaForm() {
    this.showNewQuejaForm = !this.showNewQuejaForm;
    if (!this.showNewQuejaForm) {
      this.quejaForm.reset({ prioridad: 'Media' });
      this.archivosSeleccionados = [];
    }
  }

  onFileSelected(event: any) {
    const files: FileList = event.target.files;
    // Append selected files to existing selection instead of replacing it
    for (let i = 0; i < files.length; i++) {
      const f = files[i];
      // avoid adding duplicates by name+size
      const exists = this.archivosSeleccionados.some(a => a.name === f.name && a.size === f.size);
      if (!exists) this.archivosSeleccionados.push(f);
    }
  }

  removeFile(index: number) {
    this.archivosSeleccionados.splice(index, 1);
  }

  async submitQueja() {
    if (this.quejaForm.invalid) {
      return;
    }

    this.creatingQueja = true;

    this.quejasService.crearQueja(this.quejaForm.value, this.archivosSeleccionados).subscribe({
      next: async (res: any) => {
        this.creatingQueja = false;
        if (res.success) {
          this.showNewQuejaForm = false;
          this.quejaForm.reset({ prioridad: 'Media' });
          this.archivosSeleccionados = [];
          // reset native file input element so the same files can be re-selected later
          try {
            if (this.fileInput && this.fileInput.nativeElement) {
              this.fileInput.nativeElement.value = '';
            }
          } catch (e) {
            // ignore if ViewChild not available in some test scenarios
          }
          this.loadData();
          const toast = await this.toastController.create({
            message: '✅ Queja creada exitosamente',
            duration: 3000,
            color: 'success',
            position: 'top'
          });
          toast.present();
        }
      },
      error: async (err) => {
        this.creatingQueja = false;
        // show user-friendly message and log full error to console for debugging
        console.error('Error creating queja:', err);
        const backendMsg = err?.error?.error || err?.message || JSON.stringify(err);
        const toast = await this.toastController.create({
          message: `❌ Error al crear la queja: ${backendMsg}`,
          duration: 5000,
          color: 'danger',
          position: 'top'
        });
        toast.present();
      }
    });
  }

  async actualizarEstatus(quejaId: number, nuevoEstatus: string) {
    const alert = await this.alertController.create({
      header: 'Actualizar Estatus',
      message: `¿Cambiar estatus a "${nuevoEstatus}"?`,
      inputs: [
        {
          name: 'comentario',
          type: 'textarea',
          placeholder: 'Comentario (opcional)'
        }
      ],
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Confirmar',
          handler: (data) => {
            this.updatingQuejaId = quejaId;
            this.quejasService.actualizarEstatus(quejaId, nuevoEstatus, data.comentario).subscribe({
              next: async (res: any) => {
                this.updatingQuejaId = null;
                if (res.success) {
                  this.loadData();
                  const toast = await this.toastController.create({
                    message: '✅ Estatus actualizado',
                    duration: 2000,
                    color: 'success',
                    position: 'top'
                  });
                  toast.present();
                }
              },
              error: async () => {
                this.updatingQuejaId = null;
                const toast = await this.toastController.create({
                  message: '❌ Error al actualizar estatus',
                  duration: 2000,
                  color: 'danger',
                  position: 'top'
                });
                toast.present();
              }
            });
          }
        }
      ]
    });
    await alert.present();
  }

  toggleAdminView() {
    this.showAdminView = !this.showAdminView;
  }

  getStatusClass(estatus: string): string {
    const statusMap: { [key: string]: string } = {
      'Pendiente': 'status-pendiente',
      'En Proceso': 'status-enproceso',
      'Resuelta': 'status-resuelta',
      'Rechazada': 'status-rechazada'
    };
    return statusMap[estatus] || '';
  }

  async logout() {
    const alert = await this.alertController.create({
      header: 'Cerrar Sesión',
      message: '¿Estás seguro de que quieres cerrar sesión?',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Sí, salir',
          handler: () => {
            this.authService.logout();
            this.router.navigate(['/landing']);
          }
        }
      ]
    });
    await alert.present();
  }
}
