import { isDevMode } from '@angular/core';

export function selectAllContent($e: any) {
  $e.target.select();
}

export function roundFloat(number: number) {
  return Math.round((number + Number.EPSILON) * 100) / 100;
}

export function getFirstLetters(str: string) {
  const firstLetters = str
    .split(' ')
    .map((word) => word[0])
    .join('');

  return firstLetters;
}

export function readFile(file) {
  return new Promise((resolve, reject) => {
    var fr = new FileReader();
    fr.onload = () => {
      resolve(fr.result);
    };
    fr.onerror = reject;
    fr.readAsDataURL(file);
  });
}

export function formatDate(input) {
  const datePart = input.match(/\d+/g),
    year = datePart[0],
    month = datePart[1],
    day = datePart[2];

  return day + '/' + month + '/' + year;
}

export function formatDateFromMySQL(input) {
  const [year, month, day] = input.split('-');
  return [day, month, year].join('/');
}

export function formatDateToMySQL(input: Date = null) {
  if (!input) {
    return null;
  }
  var d = new Date(input),
    month = '' + (d.getMonth() + 1),
    day = '' + d.getDate(),
    year = d.getFullYear(),
    hours = '' + d.getHours(),
    minutes = '' + d.getMinutes(),
    seconds = '' + d.getSeconds();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;
  if (hours.length < 2) hours = '0' + hours;
  if (minutes.length < 2) minutes = '0' + minutes;
  if (seconds.length < 2) seconds = '0' + seconds;

  return [year, month, day].join('-') + ' ' + [hours, minutes, seconds].join(':');
}

export function getRandomInt(max) {
  return Math.floor(Math.random() * max);
}

export function formatDecimal2PlacesComma(n) {
  const val = Math.round(Number(n) * 100) / 100;
  const parts = val.toString().split(".");
  if (parts.length < 2) {
    parts[1] = '0';
  }
  if (parts[1].length < 2) {
    parts[1] = '0' + parts[1];
  }
  const num = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",") + (parts[1] ? "." + parts[1] : "");
  return num;
}

export function imprimirElemento(elemento) {
  const element = document.getElementById(elemento);
  if (!element) {
    return false;
  }

  var printWindow = window.open('', 'Imprimir', 'top=10,left=10,height=600,width=800');

  const estilosBase = `<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
    body { font-family: 'Montserrat', sans-serif; font-size: 11pt; color: #000; line-height: 1.6; margin: 0; padding: 0; }
    strong, b { font-weight: 700; }
    em, i { font-style: italic; }
    u { text-decoration: underline; }
    ol, ul { margin-left: 24px; }
    p { margin: 0 0 8px 0; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #ccc; padding: 4px 8px; }
    @page { margin: 20mm; }
  </style>`;

  printWindow.document.write('<html><head><title>' + document.title + '</title>' + estilosBase + '</head><body>');
  printWindow.document.write(element.innerHTML);
  printWindow.document.write('</body></html>');

  printWindow.onhashchange = function () {
    printWindow.close();
  };

  printWindow.document.close(); // necessary for IE >= 10
  printWindow.focus(); // necessary for IE >= 10*/

  /* if (!isDevMode()) {
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 3000);
  } */

  return true;
}
