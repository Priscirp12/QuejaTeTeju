import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { IonicModule, IonContent } from '@ionic/angular';

@Component({
  selector: 'app-landing',
  templateUrl: './landing.component.html',
  styleUrls: ['./landing.component.scss'],
  standalone: true,
  imports: [CommonModule, IonicModule, RouterModule]
})
export class LandingComponent implements OnInit {
  @ViewChild(IonContent) content!: IonContent;

  constructor(private router: Router) { }

  ngOnInit() { }

  scrollTo(elementId: string) {
    const el = document.getElementById(elementId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }

  goToRegister() {
    this.router.navigate(['/login'], { queryParams: { tab: 'register' } });
  }
}