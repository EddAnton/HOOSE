import { Injectable } from '@angular/core';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

@Injectable({ providedIn: 'root' })
export class PdfService {

  async generarPdfDesdeHTML(elementId: string, nombreArchivo: string) {
    const elemento = document.getElementById(elementId);
    if (!elemento) return;

    const canvas = await html2canvas(elemento, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
    });

    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('p', 'mm', 'letter');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    const imgWidth = canvas.width;
    const imgHeight = canvas.height;
    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight) * 25.4 / 0.264583;
    const imgX = (pdfWidth - imgWidth * ratio / (25.4 / 0.264583)) / 2;

    const totalPages = Math.ceil(imgHeight / (imgWidth / pdfWidth * imgHeight));
    const pageHeightPx = imgWidth / pdfWidth * pdfHeight * (25.4 / 0.264583);

    let position = 0;
    for (let page = 0; page < totalPages; page++) {
      if (page > 0) pdf.addPage();
      pdf.addImage(imgData, 'PNG', 0, -position * (pdfHeight), pdfWidth, imgHeight * pdfWidth / imgWidth);
      position += pdfHeight;
    }

    pdf.save(nombreArchivo + '.pdf');
  }
}
