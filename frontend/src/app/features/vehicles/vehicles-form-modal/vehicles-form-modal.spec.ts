import { ComponentFixture, TestBed } from '@angular/core/testing';

import { VehiclesFormModal } from './vehicles-form-modal';

describe('VehiclesFormModal', () => {
  let component: VehiclesFormModal;
  let fixture: ComponentFixture<VehiclesFormModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [VehiclesFormModal],
    }).compileComponents();

    fixture = TestBed.createComponent(VehiclesFormModal);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
