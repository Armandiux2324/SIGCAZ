import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RegistersRoutingModule } from './registers-routing.module';
import { RegistersComponent } from './registers.component';


@NgModule({
  declarations: [
    RegistersComponent
  ],
  imports: [
    CommonModule,
    RegistersRoutingModule
  ]
})
export class RegistersModule { }
