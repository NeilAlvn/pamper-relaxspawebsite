<?php
// modal_print.php
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
async function printModalServicePdf() {
    const modalContent = document.querySelector('#results-modal .relative.bg-white');
    if (!modalContent) return alert("Modal content not found!");

    // Clone modal content
    const clone = modalContent.cloneNode(true);

    // Hide buttons inside clone
    clone.querySelectorAll('button').forEach(btn => btn.style.display = 'none');

    // Force white background and black text
    clone.style.background = "white";
    clone.querySelectorAll('*').forEach(el => {
        el.style.background = "transparent";
        el.style.color = "black";
        el.style.boxShadow = "none";
        el.style.borderColor = "black";
    });

    // Center and style title
    const title = clone.querySelector('h3');
    if (title) {
        title.style.textAlign = "center";
        title.style.marginBottom = "30px";
        title.style.fontSize = "24px";
        title.style.background = "none";
        title.style.color = "black";
    }

    // HIDE Payment Proof, Status, Actions columns from table safely
    const table = clone.querySelector('#sales-table');
    if (table) {
        // Get all headers
        const headers = Array.from(table.querySelectorAll('thead th'));
        // Find indices of columns to hide by header text
        const hideCols = ['Payment Proof', 'Status', 'Actions'].map(col => 
            headers.findIndex(th => th.textContent.trim() === col)
        ).filter(i => i >= 0);

        // Hide those headers
        hideCols.forEach(i => {
            headers[i].style.display = 'none';
        });

        // Hide corresponding tbody cells
        table.querySelectorAll('tbody tr').forEach(row => {
            hideCols.forEach(i => {
                if (row.cells[i]) {
                    row.cells[i].style.display = 'none';
                }
            });
        });
    }

    // Append clone offscreen for rendering
    const container = document.createElement('div');
    container.style.position = 'fixed';
    container.style.top = '-9999px';
    container.appendChild(clone);
    document.body.appendChild(container);

    // Render canvas from clone
    const canvas = await html2canvas(clone, {
        scale: 2,
        useCORS: true,
        allowTaint: false,
    });

    const imgData = canvas.toDataURL('image/jpeg', 1.0);
    const pdf = new jspdf.jsPDF('portrait', 'pt', 'a4');
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    const imgHeight = (canvas.height * pdfWidth) / canvas.width;

    const yOffset = (pdfHeight - imgHeight) / 2;
    pdf.addImage(imgData, 'JPEG', 0, yOffset > 0 ? yOffset : 0, pdfWidth, imgHeight);

    pdf.save('sales-report-service.pdf');
    showToast();

    container.remove();
}
</script>