import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SearchRegister } from './search-register';

describe('SearchRegister', () => {
  let component: SearchRegister;
  let fixture: ComponentFixture<SearchRegister>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [SearchRegister],
    }).compileComponents();

    fixture = TestBed.createComponent(SearchRegister);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
