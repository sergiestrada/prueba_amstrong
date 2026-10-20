import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideHttpClient } from '@angular/common/http';

import { LoginComponent } from './login';

describe('LoginComponent', () => {
  let component: LoginComponent;
  let fixture: ComponentFixture<LoginComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LoginComponent],
      providers: [
        provideRouter([]),
        provideHttpClient()
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('debe crearse', () => {
    expect(component).toBeTruthy();
  });

  it('el formulario debe ser inválido al inicio', () => {
    expect(component.form.valid).toBeFalse();
  });

  it('debe ser válido con email y password correctos', () => {
    component.form.patchValue({
      email: 'admin@test.com',
      password: 'password123'
    });
    expect(component.form.valid).toBeTrue();
  });

  it('no debe enviar si el formulario es inválido', () => {
    spyOn(component.form, 'markAllAsTouched').and.callThrough();
    component.submit();
    expect(component.form.markAllAsTouched).toHaveBeenCalled();
  });
});