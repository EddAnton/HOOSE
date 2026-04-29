import * as hlpApp from './app-helper';

const backgroundColors = [
  '#1BC99A',
  '#e91e8c',
  '#3B82F6',
  '#F59E0B',
  '#8B5CF6',
  '#EF4444',
  '#06B6D4',
  '#84CC16',
];

let hoverBackgroundColors = [];

function InitGraphObject() {
  hoverBackgroundColors = backgroundColors.map(c => c + 'cc');

  return {
    type: null,
    title: null,
    labels: [],
    total: [],
    datasets: [{
      data: [],
      backgroundColor: backgroundColors,
      hoverBackgroundColor: hoverBackgroundColors,
      fill: true,
    }],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: { left: 0, right: 0, top: 10, bottom: 0 }
      },
      legend: {
        display: true,
        fullWidth: false,
        labels: {
          fontColor: '#8a8f9e',
          fontFamily: "'Segoe UI', system-ui, sans-serif",
          fontSize: 11,
          usePointStyle: true,
          padding: 16,
        }
      },
      scales: {
        xAxes: [{
          ticks: {
            fontColor: '#555a6a',
            fontFamily: "'Segoe UI', system-ui, sans-serif",
            fontSize: 10,
            maxRotation: 45,
          },
          gridLines: {
            color: 'rgba(255,255,255,0.04)',
            zeroLineColor: 'rgba(255,255,255,0.08)',
          }
        }],
        yAxes: [{
          ticks: {
            fontColor: '#555a6a',
            fontFamily: "'Segoe UI', system-ui, sans-serif",
            fontSize: 10,
            callback: function(value) {
              if (value >= 1000) return '$' + (value/1000).toFixed(0) + 'k';
              return value;
            }
          },
          gridLines: {
            color: 'rgba(255,255,255,0.04)',
            zeroLineColor: 'rgba(255,255,255,0.08)',
          }
        }]
      }
    },
  };
}

function SetStackedOptions(graph: any) {
  graph.options.tooltips = {
    mode: 'index',
    intersect: false,
    backgroundColor: 'rgba(17,19,24,0.95)',
    titleFontColor: '#f0f0f0',
    bodyFontColor: '#8a8f9e',
    borderColor: 'rgba(255,255,255,0.07)',
    borderWidth: 1,
    titleFontFamily: "'Segoe UI', system-ui, sans-serif",
    bodyFontFamily: "'Segoe UI', system-ui, sans-serif",
    titleFontSize: 12,
    bodyFontSize: 11,
    cornerRadius: 8,
    xPadding: 12,
    yPadding: 10,
  };
  graph.options.scales = {
    xAxes: [{
      stacked: true,
      ticks: {
        fontColor: '#555a6a',
        fontFamily: "'Segoe UI', system-ui, sans-serif",
        fontSize: 10,
        maxRotation: 45,
      },
      gridLines: {
        color: 'rgba(255,255,255,0.04)',
        zeroLineColor: 'rgba(255,255,255,0.08)',
      }
    }],
    yAxes: [{
      stacked: true,
      ticks: {
        fontColor: '#555a6a',
        fontFamily: "'Segoe UI', system-ui, sans-serif",
        fontSize: 10,
        callback: function(value) {
          if (value >= 1000) return '$' + (value/1000).toFixed(0) + 'k';
          return value;
        }
      },
      gridLines: {
        color: 'rgba(255,255,255,0.04)',
        zeroLineColor: 'rgba(255,255,255,0.08)',
      }
    }]
  };
}

function AddLabels(graph: any, labels: string[]) {
  graph.labels = labels;
}

function AddValues(graph: any, values: any[], labels: string[] = []) {
  switch (graph.type) {
    case 'pie':
      graph.datasets[0].data = Object.values(values[0]);
      break;
    case 'stacked':
    case 'bar':
    case 'line':
      graph.datasets = [];
      values.forEach((value, i) => {
        const color = backgroundColors[i % backgroundColors.length];
        const item = {
          type: graph.type == 'line' ? 'line' : 'bar',
          label: labels[i] || null,
          backgroundColor: graph.type == 'line' ? color + '33' : color,
          hoverBackgroundColor: color + 'cc',
          borderColor: color,
          borderWidth: graph.type == 'line' ? 2 : 0,
          pointBackgroundColor: color,
          pointBorderColor: '#1a1d24',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          data: value,
          fill: graph.type == 'line',
          lineTension: 0.4,
        };
        graph.datasets.push(item);
      });
      if (graph.datasets.length > 0) {
        graph.datasets.total = 0;
      }
      break;
    default:
      break;
  }
}

function SetTitle(graph: any, title: string = '') {
  graph.options.title = {
    display: title != '',
    text: title,
    fontColor: '#f0f0f0',
    fontFamily: "'Segoe UI', system-ui, sans-serif",
    fontSize: 13,
  }
}

function TooltipFormatDecimal(graph: any, decimals: boolean = true) {
  graph.options.tooltips = {
    ...graph.options.tooltips,
    backgroundColor: 'rgba(17,19,24,0.95)',
    titleFontColor: '#f0f0f0',
    bodyFontColor: '#8a8f9e',
    borderColor: 'rgba(255,255,255,0.07)',
    borderWidth: 1,
    titleFontFamily: "'Segoe UI', system-ui, sans-serif",
    bodyFontFamily: "'Segoe UI', system-ui, sans-serif",
    titleFontSize: 12,
    bodyFontSize: 11,
    cornerRadius: 8,
    xPadding: 12,
    yPadding: 10,
    callbacks: {
      title: function (tooltipItem, data) {
        if (data.datasets.length > 1) {
          return data.datasets[tooltipItem[0].datasetIndex].label || '';
        } else {
          return data.labels[tooltipItem[0].index] || '';
        }
      },
      label: function (tooltipItem, data) {
        let value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index] || 0;
        return ' ' + (decimals ? hlpApp.formatDecimal2PlacesComma(value) : value);
      },
      footer: function (tooltipItem, data) {
        const total = (data.datasets.length == 1 ? data.total[0] : data.total[tooltipItem[0].index]) || null;
        let value = data.datasets[tooltipItem[0].datasetIndex].data[tooltipItem[0].index] || null;
        return total && value ? hlpApp.formatDecimal2PlacesComma(Number(value) * 100 / total) + '%' : null;
      },
    }
  };
}

function LegendAddValues(graph: any, dataType: string = '') {
  graph.options.legend.labels = {
    fontColor: '#8a8f9e',
    fontFamily: "'Segoe UI', system-ui, sans-serif",
    fontSize: 11,
    usePointStyle: true,
    padding: 16,
    filter: function (legendItem, data) {
      let labels = null;
      let datasets = null;
      if (data.datasets.length > 1) {
        labels = data.datasets.map((d) => d.label);
        datasets = data.datasets.map((d) => d.data[legendItem.datasetIndex]);
      } else {
        labels = data.labels;
        datasets = data.datasets[0].data;
      }
      for (let i = 0; i < labels.length; i++) {
        if (labels[i] && labels[i].indexOf(legendItem.text) != -1) {
          let label = legendItem.text;
          let value = datasets[i];
          if (dataType === 'D') value = hlpApp.formatDecimal2PlacesComma(value);
          legendItem.text = ' ' + label + ': ' + value;
          break;
        }
      }
      return legendItem;
    }
  };
  graph.options.legend.display = !['bar', 'stacked'].includes(graph.type);
}

export function GenerateGraph(data, calculateTotal: Boolean = true) {
  if (!data?._type || !data?.datasets)
    return;

  let graph = InitGraphObject();
  graph.title = data?._title || null;
  if (data?.datasets.length < 1) {
    return graph;
  }
  graph.type = data._type || null;

  const datasets = data?.datasets || null;
  const legends = data?._legends || null;
  const dataGraph = Object.keys(datasets).
    filter((key) => !key.startsWith('_')).
    reduce((cur, key) => { return Object.assign(cur, { [key]: datasets[key] }) }, {});
  let values = [];
  values = Object.values(dataGraph);
  const labels = Object.keys(values[0]);

  if (calculateTotal) {
    values.forEach((value, index) => {
      graph.total.push(
        Object.keys(value).reduce((sum, key) => sum + parseFloat(value[key] || 0), 0));
    });
  }

  let valuesPivoted = [];

  switch (graph.type) {
    case 'pie':
      AddLabels(graph, labels);
      AddValues(graph, values);
      TooltipFormatDecimal(graph);
      LegendAddValues(graph, 'D');
      break;
    case 'line':
    case 'bar':
    case 'stacked':
      const stacked = graph.type == 'stacked';
      for (let j = 0; j < labels.length; j++) {
        let item = [];
        for (let i = 0; i < values.length; i++) {
          item.push(Math.trunc(Number(values[i][labels[j]])));
        }
        valuesPivoted.push(item);
      }

      AddLabels(graph, legends);
      AddValues(graph, valuesPivoted, labels);

      if (stacked) {
        SetStackedOptions(graph);
        graph.type = 'bar';
      } else if (graph.type == 'bar') {
        LegendAddValues(graph);
      }
      TooltipFormatDecimal(graph, stacked);
      break;
    default:
      break;
  }

  return graph;
}
