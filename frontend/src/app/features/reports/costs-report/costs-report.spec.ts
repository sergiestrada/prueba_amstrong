import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CostsReport } from './costs-report';

describe('CostsReport', () => {
  let component: CostsReport;
  let fixture: ComponentFixture<CostsReport>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CostsReport],
    }).compileComponents();

    fixture = TestBed.createComponent(CostsReport);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
