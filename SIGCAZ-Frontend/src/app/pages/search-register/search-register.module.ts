import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SearchRegisterRoutingModule } from './search-register-routing.module';
import { SearchRegisterComponent } from './search-register.component';


@NgModule({
  declarations: [
    SearchRegisterComponent
  ],
  imports: [
    CommonModule,
    SearchRegisterRoutingModule
  ]
})
export class SearchRegisterModule { }
