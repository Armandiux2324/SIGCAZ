import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

import { RegistersRoutingModule } from './registers-routing.module';
import { RegistersComponent } from './registers.component';


@NgModule({
  declarations: [
    RegistersComponent
  ],
  imports: [
    CommonModule,
    FormsModule,
    RegistersRoutingModule
  ]
})
export class RegistersModule { }