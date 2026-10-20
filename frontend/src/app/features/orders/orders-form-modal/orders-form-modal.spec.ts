import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OrdersFormModal } from './orders-form-modal';

describe('OrdersFormModal', () => {
  let component: OrdersFormModal;
  let fixture: ComponentFixture<OrdersFormModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OrdersFormModal],
    }).compileComponents();

    fixture = TestBed.createComponent(OrdersFormModal);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
