import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SearchRegisterComponent } from './search-register.component';

describe('SearchRegisterComponent', () => {
  let component: SearchRegisterComponent;
  let fixture: ComponentFixture<SearchRegisterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [SearchRegisterComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SearchRegisterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
