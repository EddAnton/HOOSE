import { Component, Input, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';

@Component({
  selector: 'app-password-input',
  templateUrl: './password-input.component.html',
  styleUrls: ['./password-input.component.css']
})
export class PasswordInputComponent implements OnInit {
  @Input() control: FormControl;
  @Input() label: string = 'Contraseña';
  @Input() placeholder: string = 'Contraseña';
  @Input() confirmar: boolean = true;
  @Input() required: boolean = false;

  confirmValue: string = '';
  mostrar: boolean = false;
  mostrarConfirm: boolean = false;
  touched: boolean = false;

  ngOnInit() {
    if (this.control) {
      this.control.valueChanges.subscribe(() => {});
      if (!this.control.value) { this.generarContrasenia(); }
    }
  }

  get value(): string { return this.control?.value || ''; }

  get fortaleza(): { nivel: number; texto: string; color: string } {
    const v = this.value;
    if (!v) return { nivel: 0, texto: '', color: '' };
    let score = 0;
    if (v.length >= 7) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const niveles = [
      { nivel: 1, texto: 'Muy débil', color: '#ef4444' },
      { nivel: 2, texto: 'Débil', color: '#f97316' },
      { nivel: 3, texto: 'Regular', color: '#f59e0b' },
      { nivel: 4, texto: 'Fuerte', color: '#1BC99A' },
    ];
    return niveles[score - 1] || { nivel: 0, texto: '', color: '' };
  }

  get errores(): string[] {
    const v = this.value;
    const errs = [];
    if (v.length < 7) errs.push('Mínimo 7 caracteres');
    if (!/[A-Z]/.test(v)) errs.push('Al menos una mayúscula');
    if (!/[0-9]/.test(v)) errs.push('Al menos un número');
    if (!/[^A-Za-z0-9]/.test(v)) errs.push('Al menos un símbolo especial');
    return errs;
  }

  get coinciden(): boolean {
    return !this.confirmar || this.value === this.confirmValue;
  }

  onInput(v: string) {
    this.control?.setValue(v);
    this.touched = true;
  }

  generarContrasenia() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    const simbolos = '!@#$%&*';
    let pwd = '';
    pwd += 'ABCDEFGHJKLMNPQRSTUVWXYZ'[Math.floor(Math.random() * 24)];
    pwd += '23456789'[Math.floor(Math.random() * 8)];
    pwd += simbolos[Math.floor(Math.random() * 7)];
    for (let i = 0; i < 5; i++) {
      pwd += chars[Math.floor(Math.random() * chars.length)];
    }
    pwd = pwd.split('').sort(() => Math.random() - 0.5).join('');
    this.control.setValue(pwd);
    this.confirmValue = pwd;
    this.mostrar = true;
    this.mostrarConfirm = true;
    this.touched = true;
  }

  onBlur() { this.touched = true; }
}
