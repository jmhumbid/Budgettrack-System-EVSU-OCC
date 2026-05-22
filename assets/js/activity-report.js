document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('downloadBudgetReport');
    if (!button || !window.jspdf) {
        return;
    }

    const reportPayload = button.dataset.report;
    const reportData = reportPayload ? JSON.parse(reportPayload) : {};
    const sections = reportData.sections ?? reportData;
    const users = reportData.users ?? [];
    const activityLog = reportData.activities ?? [];

    button.addEventListener('click', function () {
        if (!window.jspdf) {
            alert('Unable to generate PDF at the moment.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'pt' });
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(20);
        doc.text('Automated Activity Report', 40, 50);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);
        doc.setTextColor(120);
        doc.text(`Generated on ${new Date().toLocaleString()}`, 40, 65);

        let y = 90;
        const cardHeight = 94;

        Object.entries(sections).forEach(([label, stats]) => {
            if (y + cardHeight > 780) {
                doc.addPage();
                y = 60;
            }
            const totalActions = stats.total_actions ?? 0;
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(220, 220, 220);
            doc.roundedRect(40, y, 240, 80, 12, 12, 'FD');
            doc.setLineWidth(0.5);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.setTextColor(65);
            doc.text(label.toUpperCase(), 48, y + 16);
            doc.setFontSize(18);
            doc.text(`${totalActions}`, 48, y + 38);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text(`Uploads: ${stats.uploads ?? 0}`, 48, y + 54);
            doc.text(`Updates: ${stats.updates ?? 0}`, 48, y + 68);
            doc.text(`Removals: ${stats.removals ?? 0}`, 48, y + 82);
            y += cardHeight + 10;
        });

        if (users.length > 0) {
            if (y + 30 > 780) {
                doc.addPage();
                y = 60;
            }
            y += 10;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.setTextColor(30);
            doc.text('Top Contributors', 40, y);
            y += 18;

            const columnWidths = [140, 80, 70, 70, 70];
            const headers = ['Name', 'Department', 'Uploads', 'Updates', 'Removals'];
            let x = 40;
            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(100);
            doc.rect(x - 6, y - 14, columnWidths.reduce((a, b) => a + b, 0), 18, 'F');
            headers.forEach((header, index) => {
                doc.text(header, x, y);
                x += columnWidths[index];
            });
            y += 12;
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(40);

            users.forEach(row => {
                if (y + 16 > 780) {
                    doc.addPage();
                    y = 60;
                }
                x = 40;
                doc.text(row.name || '—', x, y);
                x += columnWidths[0];
                doc.text(row.department || '—', x, y);
                x += columnWidths[1];
                doc.text(`${row.uploads}`, x, y);
                x += columnWidths[2];
                doc.text(`${row.updates}`, x, y);
                x += columnWidths[3];
                doc.text(`${row.removals}`, x, y);
                y += 16;
            });
        }

        if (activityLog.length > 0) {
            if (y + 30 > 780) {
                doc.addPage();
                y = 60;
            }
            y += 18;
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text('Recent Activity Log', 40, y);
            y += 20;

            const columns = [
                { label: 'Activity', width: 150 },
                { label: 'Details', width: 260 },
                { label: 'Timestamp', width: 120 },
            ];

            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(80);
            let xHeader = 40;
            columns.forEach(col => {
                doc.text(col.label, xHeader, y);
                xHeader += col.width;
            });
            y += 12;
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(40);

            activityLog.forEach(entry => {
                if (y + 20 > 780) {
                    doc.addPage();
                    y = 60;
                }
                let xEntry = 40;
                doc.text(entry.activity_type || '—', xEntry, y);
                xEntry += columns[0].width;
                const detailLines = doc.splitTextToSize(entry.activity_details || '—', columns[1].width - 5);
                doc.text(detailLines, xEntry, y);
                doc.setFont('helvetica', 'italic');
                doc.setFontSize(8);
                doc.text(entry.created_at || '—', xEntry + 5, y + (detailLines.length - 1) * 11 + 12);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                y += Math.max(detailLines.length * 11, 12) + 6;
            });
            y += 10;
        }

        doc.setFont('helvetica', 'italic');
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('BudgetTrack • EVSU', 40, 800);
        doc.save(`ActivityReport_${new Date().toISOString().split('T')[0]}.pdf`);
    });
});
