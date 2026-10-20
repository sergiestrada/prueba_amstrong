import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OrdersStatusModal } from './orders-status-modal';

describe('OrdersStatusModal', () => {
  let component: OrdersStatusModal;
  let fixture: ComponentFixture<OrdersStatusModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OrdersStatusModal],
    }).compileComponents();

    fixture = TestBed.createComponent(OrdersStatusModal);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
