import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'app-dashboard-chart',
  templateUrl: './dashboard-chart.component.html',
  styleUrls: ['./dashboard-chart.component.css']
})
export class DashboardChartComponent implements OnInit {
  @Input() data: any = null;

  constructor() { }

  ngOnInit(): void {
  }

}
