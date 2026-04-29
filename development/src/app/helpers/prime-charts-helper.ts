import * as hlpApp from './app-helper';

const backgroundColors = [
  '#003c5e',
  '#d62829',
  '#f67f00',
  // '#fdbe4a',
  // '#e9e2b8',
  '#00476f',
  '#ff2f30',
  '#ffa500'
];
let hoverBackgroundColors = [];

function InitGraphObject() {
  hoverBackgroundColors = [];
  for (let i = 0; i < backgroundColors.length; i++) {
    hoverBackgroundColors.push(backgroundColors[i] + 'e1');
  }

  return {
    type: null,
    title: null,
    labels: [],
    total: [],
    datasets: [{
      data: [],
      backgroundColor: backgroundColors,
      hoverBackgroundColor: hoverBackgroundColors,
      /* hoverBackgroundColor: [
        '#002f49',
        '#d62829',
        '#f67f00'
      ] */
      fill: true,
    }],
    options: {
      responsive: true,
      layout: {
        padding: {
          left: 0,
          right: 0,
          top: 10,
          bottom: 0
        }
      },
      legend: {
        display: true,
        fullWidth: false,
        // align: 'end',
        labels: {}
      }
    },
  };
}

function SetStackedOptions(graph: any) {
  // graph.options.legend.display = false;
  // SetBarOptions(graph);
  graph.options.tooltips = {
    mode: 'index',
    intersect: false
  };
  graph.options.scales = {
    xAxes: [{
      stacked: true,
    }],
    yAxes: [{
      stacked: true
    }]
  };
}

function AddLabels(graph: any, labels: string[]) {
  /* switch (graph.type) {
    case 'pie':
    case 'bar':
      graph.labels = labels;
      break;
    case 'stacked':
      graph.labels = labels;
      break;
    default:
      break;
  } */
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
        const iColor = graph.type == 'line' ? hlpApp.getRandomInt(backgroundColors.length - 1) : i;
        const iColorBorder = graph.type == 'line' ? hlpApp.getRandomInt(backgroundColors.length - 1) : i;
        const item = {
          type: graph.type == 'line' ? 'line' : 'bar',
          label: labels[i] || null,
          backgroundColor: backgroundColors[iColor],
          hoverBackgroundColor: hoverBackgroundColors[iColor],
          borderColor: backgroundColors[iColorBorder],
          data: value,
          fill: !(graph.type == 'line')
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
  }
}

function TooltipFormatDecimal(graph: any, decimals: boolean = true) {
  graph.options.tooltips = {
    titleAlign: 'center',
    titleMarginBottom: 12,
    bodyAlign: 'right',
    footerAlign: 'right',
    footerMarginTop: 12,
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

        // return value ? ' ' + (decimals ? hlpApp.formatDecimal2PlacesComma(value) : value) : null;
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
    usePointStyle: true,
    padding: 16,
    filter: function (legendItem, data) {
      let labels = null;
      let datasets = null;
      if (data.datasets.length > 1) {
        labels = data.datasets.map((d) => d.label)
        datasets = data.datasets.map((d) => d.data[legendItem.datasetIndex])
      } else {
        labels = data.labels;
        datasets = data.datasets[0].data;
      }
      for (let i = 0; i < labels.length; i++) {
        if (labels[i].indexOf(legendItem.text) != -1) {
          let label = legendItem.text;
          let value = datasets[i];
          // let percent = data.total && data.total > 0 ? hlpApp.formatDecimal2PlacesComma(Number(value) * 100 / data.total) : null;

          switch (dataType) {
            case 'D':
              value = hlpApp.formatDecimal2PlacesComma(value);
              break;
            default:
              break;
          }

          // legendItem.text = ' ' + label + ': ' + value + (percent ? ' (' + percent + '%)' : '') + '\r\n';
          legendItem.text = ' ' + label + ': ' + value;
          break;
        }
      }
      return legendItem;
    }
  };
  graph.options.legend.display = !['bar', 'stacked'].includes(graph.type);
}

function AddOutlabels(graph: any, dataType: string = '') {
  graph.options.plugins = {
    outlabels: {
      text: (l, p) => {
        let v = l.dataset.data[l.dataIndex];
        switch (dataType) {
          case 'D':
            v = hlpApp.formatDecimal2PlacesComma(v);
            break;
          default:
            break;
        }
        return '%l: ' + v + ' (%p)';
      },
      backgroundColor: 'rgba(0, 0, 0, .6)',
      borderColor: 'gray',
      borderRadius: 5,
      padding: 4,
      stretch: 14,
      font: {
        resizable: true,
        minSize: 8,
        maxSize: 12,
      },
    }
  }
}

/* export function SetOptionsPie(graph: any, title: string) {
  // SetTitle(graph, title);
  TooltipFormatDecimal(graph);
  LegendAddValues(graph, 'D');
  AddOutlabels(graph, 'D');
} */

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

  // SetTitle(graph, title);
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
      // TooltipFormatDecimal(graph, stacked);

      if (stacked) {
        SetStackedOptions(graph);
        graph.type = 'bar';
      } else if (graph.type == 'bar') {
        // SetBarOptions(graph);
        LegendAddValues(graph);
      }
      TooltipFormatDecimal(graph, stacked);
      // graph.options.legend.display = stacked;
      break;
    /*
    case 'stacked':
      for (let j = 0; j < labels.length; j++) {
        let item = [];
        for (let i = 0; i < values.length; i++) {
          item.push(Number(values[i][labels[j]]));
        }
        valuesPivoted.push(item);
      }

      AddLabels(graph, legends);
      AddValues(graph, valuesPivoted, labels);

      SetStackedOptions(graph);
      TooltipFormatDecimal(graph);
      graph.type = 'bar';
      break;
    */

    default:
      break;
  }

  console.log(graph);
  return graph;
}