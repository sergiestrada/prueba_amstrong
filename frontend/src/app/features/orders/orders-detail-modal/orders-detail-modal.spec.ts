import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OrdersDetailModal } from './orders-detail-modal';

describe('OrdersDetailModal', () => {
  let component: OrdersDetailModal;
  let fixture: ComponentFixture<OrdersDetailModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OrdersDetailModal],
    }).compileComponents();

    fixture = TestBed.createComponent(OrdersDetailModal);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
