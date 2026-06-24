import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { SearchRegisterComponent } from './search-register.component';

const routes: Routes = [{ path: '', component: SearchRegisterComponent }];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SearchRegisterRoutingModule { }
