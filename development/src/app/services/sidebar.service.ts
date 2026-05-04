import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class SidebarService {
  private _visible = new BehaviorSubject<boolean>(true);
  visible$ = this._visible.asObservable();

  setVisible(v: boolean) { this._visible.next(v); }
  get visible() { return this._visible.value; }
}
