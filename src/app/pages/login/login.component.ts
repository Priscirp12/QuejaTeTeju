import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterModule, ActivatedRoute } from '@angular/router';
import { IonicModule } from '@ionic/angular';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
  standalone: true,
  imports: [CommonModule, IonicModule, ReactiveFormsModule, RouterModule]
})
export class LoginComponent implements OnInit {
  activeTab: 'login' | 'register' = 'login';
  loginForm!: FormGroup;
  registerForm!: FormGroup;
  loginLoading = false;
  registerLoading = false;
  loginError = '';
  registerError = '';
  successMessage = '';
  showLoginPassword = false;
  showRegisterPassword = false;
  showRegisterPasswordConfirm = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit() {
    // Si ya está autenticado, redirigir
    if (this.authService.isAuthenticated()) {
      this.router.navigate(['/dashboard']);
      return;
    }

    // Verificar si debe abrir tab de registro
    this.route.queryParams.subscribe(params => {
      if (params['tab'] === 'register') {
        this.activeTab = 'register';
      }
    });

    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });

    this.registerForm = this.fb.group({
      nombre: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      telefono: [''],
      password: ['', [Validators.required, Validators.minLength(6)]],
      passwordConfirm: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  switchTab(tab: 'login' | 'register') {
    this.activeTab = tab;
    this.loginError = '';
    this.registerError = '';
    this.successMessage = '';
  }

  togglePassword(field: string) {
    if (field === 'login') this.showLoginPassword = !this.showLoginPassword;
    if (field === 'register') this.showRegisterPassword = !this.showRegisterPassword;
    if (field === 'registerConfirm') this.showRegisterPasswordConfirm = !this.showRegisterPasswordConfirm;
  }

  handleLogin() {
    if (this.loginForm.invalid) {
      this.loginError = 'Por favor completa todos los campos correctamente';
      return;
    }

    this.loginLoading = true;
    this.loginError = '';

    const { email, password } = this.loginForm.value;

    this.authService.login(email, password).subscribe({
      next: (response) => {
        this.loginLoading = false;
        if (response.success) {
          this.router.navigate(['/dashboard']);
        } else {
          this.loginError = response.message || 'Error al iniciar sesión';
        }
      },
      error: (err) => {
        this.loginLoading = false;
        this.loginError = err.error?.error || 'Email o contraseña incorrectos';
      }
    });
  }

  handleRegister() {
    if (this.registerForm.invalid) {
      this.registerError = 'Por favor completa todos los campos correctamente';
      return;
    }

    const { password, passwordConfirm } = this.registerForm.value;
    if (password !== passwordConfirm) {
      this.registerError = 'Las contraseñas no coinciden';
      return;
    }

    this.registerLoading = true;
    this.registerError = '';

    const data = {
      nombre: this.registerForm.value.nombre,
      email: this.registerForm.value.email,
      password: this.registerForm.value.password,
      telefono: this.registerForm.value.telefono || undefined
    };

    this.authService.register(data).subscribe({
      next: (response: any) => {
        this.registerLoading = false;
        if (response.success) {
          this.successMessage = '✓ Registro exitoso. Ahora puedes iniciar sesión.';
          this.registerForm.reset();
          setTimeout(() => {
            this.switchTab('login');
          }, 2000);
        } else {
          this.registerError = response.error || 'Error al registrar';
        }
      },
      error: (err) => {
        this.registerLoading = false;
        this.registerError = err.error?.error || 'Error al registrar el usuario';
      }
    });
  }

  goToLanding() {
    this.router.navigate(['/landing']);
  }
}
