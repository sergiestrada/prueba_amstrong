import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';

import { CostsReportComponent } from './costs-report';

describe('CostsReportComponent', () => {
  let component: CostsReportComponent;
  let fixture: ComponentFixture<CostsReportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CostsReportComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(CostsReportComponent);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});