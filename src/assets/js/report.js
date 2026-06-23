// ============================================
// FacilityOps Report Page JavaScript
// Consolidated - No Duplicates
// ============================================

// Toggle submenu
document.querySelectorAll('.submenu-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const parent = this.parentElement;
        parent.classList.toggle('active');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    console.log('📊 Analytics Report JavaScript loaded');

    // ==============================
    // 📌 Mobile Menu & Overlay
    // ==============================
    const menuToggle = document.getElementById('menuToggle');
    const sidePanel = document.getElementById('sidePanel');
    const overlay = document.getElementById('overlay');

    if (menuToggle && sidePanel && overlay) {
        menuToggle.addEventListener('click', function () {
            sidePanel.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidePanel.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // ==============================
    // 📊 Chart.js Configurations
    // ==============================
    
    Chart.defaults.font.family = "'Segoe UI', 'Roboto', 'Arial', sans-serif";
    Chart.defaults.font.size = 13;
    Chart.defaults.color = '#2d3748';

    // ===== 1. STATUS PIE CHART WITH PERCENTAGES =====
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && typeof statusData !== 'undefined') {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.labels,
                datasets: [{
                    data: statusData.values,
                    backgroundColor: [
                        '#38a169', // Approved
                        '#f6ad55', // Pending
                        '#e53e3e'  // Rejected
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 13,
                                weight: '600'
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    // ADD THIS: Display percentages on the pie slices
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: (value, ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            // Only show label if percentage is greater than 5%
                            return percentage > 5 ? percentage + '%' : '';
                        },
                        anchor: 'center',
                        align: 'center'
                    }
                }
            },
            // ADD THIS: Register the datalabels plugin
            plugins: [ChartDataLabels]
        });
    }

    // ===== 2. DEPARTMENT BAR CHART =====
    const facilityCtx = document.getElementById('facilityChart');
    if (facilityCtx && typeof departmentData !== 'undefined') {
        new Chart(facilityCtx, {
            type: 'bar',
            data: {
                labels: departmentData.labels,
                datasets: [{
                    label: 'Total Bookings',
                    data: departmentData.values,
                    backgroundColor: 'rgba(66, 153, 225, 0.8)',
                    borderColor: 'rgba(44, 82, 130, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: 'rgba(44, 82, 130, 0.9)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // ===== 3. TREND LINE CHART =====
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx && typeof trendData !== 'undefined') {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: 'Monthly Bookings',
                    data: trendData.values,
                    borderColor: 'rgba(66, 153, 225, 1)',
                    backgroundColor: 'rgba(66, 153, 225, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(66, 153, 225, 1)',
                    pointBorderWidth: 3,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: 'rgba(44, 82, 130, 1)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return `Bookings: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // ==============================
    // ANIMATION SECTION REMOVED
    // Stats now display immediately without counting up
    // ==============================

    // ==============================
    // 📱 Window Resize Handler
    // ==============================
    window.addEventListener('resize', () => {
        if (window.innerWidth > 968) {
            sidePanel.classList.remove('active');
            overlay.classList.remove('active');
        }
    });

    console.log('✅ All analytics features initialized');
});

// ============================================
// 🖨️ PRINT OPTIONS DIALOG
// ============================================
function showPrintOptions() {
    const existingModal = document.getElementById('printModal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'printModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeIn 0.2s ease;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h3 style="margin-top: 0; color: #1a365d; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                🖨️ Print Options
            </h3>
            
            <div style="margin: 25px 0; padding: 20px; background: #f7fafc; border-radius: 8px;">
                <label style="font-weight: 600; color: #2d3748; display: block; margin-bottom: 12px;">
                    Page Orientation:
                </label>
                <label style="display: block; margin-bottom: 12px; cursor: pointer; padding: 10px; background: white; border-radius: 6px; border: 2px solid #e2e8f0; transition: all 0.2s;">
                    <input type="radio" name="orientation" value="portrait" checked style="margin-right: 8px;">
                    <strong>Portrait</strong> (Vertical) - Recommended for reports
                </label>
                <label style="display: block; cursor: pointer; padding: 10px; background: white; border-radius: 6px; border: 2px solid #e2e8f0; transition: all 0.2s;">
                    <input type="radio" name="orientation" value="landscape" style="margin-right: 8px;">
                    <strong>Landscape</strong> (Horizontal) - For wide tables
                </label>
            </div>

            <div style="margin: 20px 0; padding: 20px; background: #f7fafc; border-radius: 8px;">
                <label style="font-weight: 600; color: #2d3748; display: block; margin-bottom: 12px;">
                    Include in Print:
                </label>
                <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                    <input type="checkbox" id="includeStats" checked style="margin-right: 8px;">
                    <strong>Summary Statistics</strong> (Total bookings, status breakdown)
                </label>
                <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                    <input type="checkbox" id="includeCharts" style="margin-right: 8px;">
                    <strong>Charts & Graphs</strong> (Visual analytics)
                </label>
                <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                    <input type="checkbox" id="includeTimestamp" checked style="margin-right: 8px;">
                    <strong>Timestamp</strong> (Date and time of print)
                </label>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button onclick="executePrint()" style="flex: 1; padding: 12px; background: #2c5282; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; transition: all 0.2s;">
                    🖨️ Print Now
                </button>
                <button onclick="closePrintModal()" style="flex: 1; padding: 12px; background: #718096; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; transition: all 0.2s;">
                    Cancel
                </button>
            </div>
            
            <p style="margin: 15px 0 0 0; font-size: 12px; color: #718096; text-align: center;">
                💡 Tip: Use "Save as PDF" in print dialog to create a digital copy
            </p>
        </div>
    `;

    modal.querySelectorAll('label').forEach(label => {
        label.addEventListener('mouseenter', () => {
            if (label.querySelector('input[type="radio"]')) {
                label.style.borderColor = '#2c5282';
                label.style.background = '#edf2f7';
            }
        });
        label.addEventListener('mouseleave', () => {
            if (label.querySelector('input[type="radio"]')) {
                label.style.borderColor = '#e2e8f0';
                label.style.background = 'white';
            }
        });
    });

    document.body.appendChild(modal);
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closePrintModal();
    });
}

function closePrintModal() {
    const modal = document.getElementById('printModal');
    if (modal) {
        modal.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => modal.remove(), 200);
    }
}

// ============================================
// 🖨️ EXECUTE PRINT WITH OPTIONS
// ============================================
async function executePrint() {
    const orientation = document.querySelector('input[name="orientation"]:checked').value;
    const includeCharts = document.getElementById('includeCharts').checked;
    const includeStats = document.getElementById('includeStats').checked;
    const includeTimestamp = document.getElementById('includeTimestamp').checked;

    closePrintModal();
    console.log('Fetching all records for print...');

    // ===== FETCH ALL RECORDS =====
    const allRecords = await fetchAllRecords();

    const facilityMap = {
        'dewan-kuliah-utama': 'Dewan Kuliah Utama',
        'bilik-makan-bauk-inn': 'Bilik Makan Bauk Inn',
        'bilik-seminar': 'Bilik Seminar',
        'bilik-kuliah-2': 'Bilik Kuliah 2',
        'puspanita': 'Puspanita'
    };

    // ===== INJECT ALL ROWS INTO TABLE =====
    const tbody = document.querySelector('.booking-table tbody');
    const originalHTML = tbody.innerHTML; // save original

    let newRows = '';
    allRecords.forEach((record, index) => {
        const facility = facilityMap[record.facilityName] || record.facilityName;
        const date = new Date(record.select_date).toLocaleDateString('en-MY', {
            day: '2-digit', month: 'short', year: 'numeric'
        });

        let statusClass = '';
        if (record.status === 'Approved') statusClass = 'status-approved';
        else if (record.status === 'Rejected') statusClass = 'status-rejected';
        else if (record.status === 'Pending') statusClass = 'status-pending';

        newRows += `
            <tr>
                <td class="actions-cell"></td>
                <td class="row-number">${index + 1}</td>
                <td><strong>${record.name || ''}</strong></td>
                <td class="facility-name">${facility}</td>
                <td class="programe-name">${record.purpose || ''}</td>
                <td class="depart">${record.depart || ''}</td>
                <td>${date}</td>
                <td><span class="${statusClass}"></span></td>
            </tr>
        `;
    });

    tbody.innerHTML = newRows;

    // ===== APPLY PRINT CLASSES =====
    document.body.classList.remove('print-landscape', 'print-portrait', 'print-with-charts', 'print-with-stats');

    if (orientation === 'landscape') {
        document.body.classList.add('print-landscape');
    } else {
        document.body.classList.add('print-portrait');
    }

    if (includeCharts) document.body.classList.add('print-with-charts');
    if (includeStats) document.body.classList.add('print-with-stats');

    const printStyle = document.createElement('style');
    printStyle.id = 'dynamic-print-style';
    printStyle.textContent = `
        @media print {
            @page { 
                size: A4 ${orientation};
                margin: ${orientation === 'landscape' ? '1cm 1.5cm' : '1.5cm 1cm'};
            }
            ${!includeCharts ? '.chart-container { display: none !important; }' : ''}
            ${!includeStats ? '.stats-grid { display: none !important; }' : ''}
        }
    `;
    document.head.appendChild(printStyle);

    if (includeTimestamp) addPrintMetadata();

    // ===== PRINT =====
    setTimeout(() => {
        window.print();

        // ===== RESTORE ORIGINAL TABLE =====
        setTimeout(() => {
            tbody.innerHTML = originalHTML;
            printStyle.remove();
            document.body.classList.remove('print-landscape', 'print-portrait', 'print-with-charts', 'print-with-stats');
            const metadata = document.getElementById('print-metadata');
            if (metadata) metadata.remove();
        }, 1000);
    }, 400);
}

// ============================================
// 📄 ADD PRINT METADATA
// ============================================
function addPrintMetadata() {
    const existing = document.getElementById('print-metadata');
    if (existing) existing.remove();

    const metadata = document.createElement('div');
    metadata.id = 'print-metadata';
    metadata.style.display = 'none';
    
    const dateFrom = document.getElementById('date_from')?.value || 'N/A';
    const dateTo = document.getElementById('date_to')?.value || 'N/A';
    const now = new Date();
    const printTime = now.toLocaleString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    
    metadata.innerHTML = `
        <div class="print-header-meta">
            <div style="text-align: center; padding: 15px; border: 2px solid #1a365d; margin-bottom: 20px;">
                <h1 style="font-size: 18pt; margin: 0; color: #1a365d;">
                    FacilityOps - Booking Records Report
                </h1>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; margin-bottom: 15px; font-size: 9pt;">
                <div>
                    <strong>Date Range:</strong> ${formatPrintDate(dateFrom)} to ${formatPrintDate(dateTo)}
                </div>
                <div>
                    <strong>Generated:</strong> ${printTime}
                </div>
            </div>
        </div>
    `;

    const printStyles = document.createElement('style');
    printStyles.textContent = `
        @media print {
            #print-metadata {
                display: block !important;
                page-break-after: avoid;
            }
            .print-header-meta {
                margin-bottom: 20px;
            }
        }
    `;
    
    metadata.appendChild(printStyles);
    
    const mainContent = document.querySelector('main') || document.querySelector('.container');
    if (mainContent) {
        mainContent.insertBefore(metadata, mainContent.firstChild);
    }
}

function formatPrintDate(dateStr) {
    if (!dateStr || dateStr === 'N/A') return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

// ============================================
// 📄 SAVE AS PDF (Direct Download - Proper Layout)
// ============================================
async function saveAsPDF() {
    showNotification('⏳ Generating PDF... Please wait', 'info');
    
    try {
        // Load jsPDF with autoTable plugin
        if (typeof window.jspdf === 'undefined') {
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
        }
        if (typeof window.jspdf.jsPDF.API.autoTable === 'undefined') {
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js');
        }
        
        await new Promise(resolve => setTimeout(resolve, 300));
        
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        
        let yPosition = 20;
        
        // ===== HEADER =====
        pdf.setFillColor(26, 54, 93);
        pdf.rect(0, 0, 210, 30, 'F');
        pdf.setTextColor(255, 255, 255);
        pdf.setFontSize(20);
        pdf.setFont(undefined, 'bold');
        pdf.text('FacilityOps - Booking Records Report', 105, 18, { align: 'center' });
        
        yPosition = 35;
        
        // ===== DATE RANGE & GENERATED INFO =====
        const dateFrom = document.getElementById('date_from')?.value || 'N/A';
        const dateTo = document.getElementById('date_to')?.value || 'N/A';
        const now = new Date();
        const generated = now.toLocaleDateString('en-MY', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        pdf.setFillColor(248, 249, 250);
        pdf.rect(10, yPosition, 190, 12, 'F');
        pdf.setTextColor(0, 0, 0);
        pdf.setFontSize(9);
        pdf.setFont(undefined, 'normal');
        pdf.text(`Date Range: ${formatPrintDate(dateFrom)} to ${formatPrintDate(dateTo)}`, 12, yPosition + 5);
        pdf.text(`Generated: ${generated}`, 198, yPosition + 5, { align: 'right' });
        
        yPosition += 15;
        
        // ===== DETAILED RECORDS TABLE =====
        pdf.setFontSize(12);
        pdf.setFont(undefined, 'bold');
        pdf.setTextColor(26, 54, 93);
        pdf.text('Detailed Booking Records', 15, yPosition);
        
        yPosition += 10;
        
        // Add pagination info with more spacing
        const paginationInfo = document.querySelector('.pagination-info')?.textContent || '';
        if (paginationInfo) {
            pdf.setFontSize(9);
            pdf.setFont(undefined, 'normal');
            pdf.setTextColor(100, 100, 100);
            pdf.text(paginationInfo, 15, yPosition);
            yPosition += 10; // Increased spacing before table
        }
        
        // Get ALL records from server (not just current page)
        showNotification('⏳ Fetching all records...', 'info');
        const allRecords = await fetchAllRecords();
        
        const facilityMap = {
            'dewan-kuliah-utama': 'Dewan Kuliah Utama',
            'bilik-makan-bauk-inn': 'Bilik Makan Bauk Inn',
            'bilik-seminar': 'Bilik Seminar',
            'bilik-kuliah-2': 'Bilik Kuliah 2',
            'puspanita': 'Puspanita'
        };

        const headers = ['No', 'Name', 'Facility', 'Programme', 'Department', 'Date', 'Status'];
        const rows = [];

        allRecords.forEach((record, index) => {
            const facility = facilityMap[record.facilityName] || record.facilityName;
            const date = new Date(record.select_date).toLocaleDateString('en-MY', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
            rows.push([index + 1, record.name, facility, record.purpose, record.depart, date, record.status]);
        });
        
        // Generate table
        pdf.autoTable({
            startY: yPosition,
            head: [headers],
            body: rows,
            theme: 'grid',
            styles: {
                fontSize: 8,
                cellPadding: 3,
                overflow: 'linebreak',
                halign: 'left'
            },
            headStyles: {
                fillColor: [44, 82, 130],
                textColor: 255,
                fontStyle: 'bold',
                halign: 'center'
            },
            alternateRowStyles: {
                fillColor: [245, 247, 250]
            },
            columnStyles: {
                0: { cellWidth: 10, halign: 'center' }, // No
                1: { cellWidth: 35 }, // Name
                2: { cellWidth: 30 }, // Facility
                3: { cellWidth: 40 }, // Programme
                4: { cellWidth: 25 }, // Department
                5: { cellWidth: 22, halign: 'center' }, // Date
                6: { cellWidth: 19, halign: 'center' } // Status
            },
            didParseCell: function(data) {
                // Color status cells (now in column 6 instead of 5)
                if (data.column.index === 6 && data.section === 'body') {
                    const status = data.cell.text[0];
                    if (status === 'Approved') {
                        data.cell.styles.textColor = [40, 161, 105];
                        data.cell.styles.fontStyle = 'bold';
                    } else if (status === 'Rejected') {
                        data.cell.styles.textColor = [229, 62, 62];
                        data.cell.styles.fontStyle = 'bold';
                    } else if (status === 'Pending') {
                        data.cell.styles.textColor = [246, 173, 85];
                        data.cell.styles.fontStyle = 'bold';
                    }
                }
            },
            margin: { left: 15, right: 15 }
        });
        
        // ===== FOOTER =====
        const pageCount = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            pdf.setPage(i);
            pdf.setFontSize(8);
            pdf.setTextColor(150, 150, 150);
            pdf.text(`Page ${i} of ${pageCount}`, 105, 287, { align: 'center' });
            pdf.text('FacilityOps © 2025', 15, 287);
        }
        
        // ===== SAVE PDF =====
        const today = new Date().toISOString().split('T')[0];
        const filename = `FacilityOps_Report_${today}.pdf`;
        pdf.save(filename);
        
        showNotification('✅ PDF downloaded successfully!');
        
    } catch (error) {
        console.error('PDF Error:', error);
        showNotification('❌ Failed to generate PDF: ' + error.message, 'error');
        
        setTimeout(() => {
            if (confirm('PDF generation failed. Try Print instead?')) {
                showPrintOptions();
            }
        }, 2000);
    }
}

// ============================================
// 📥 EXPORT TO CSV (ALL RECORDS)
// ============================================
async function exportToCSV() {
    const includeStats = confirm('Include summary statistics in CSV?');
    showNotification('⏳ Fetching all records...', 'info');

    const allRecords = await fetchAllRecords();

    const facilityMap = {
        'dewan-kuliah-utama': 'Dewan Kuliah Utama',
        'bilik-makan-bauk-inn': 'Bilik Makan Bauk Inn',
        'bilik-seminar': 'Bilik Seminar',
        'bilik-kuliah-2': 'Bilik Kuliah 2',
        'puspanita': 'Puspanita'
    };

    let csv = [];
    csv.push(`"FacilityOps Booking Report"`);
    csv.push(`"Generated on: ${new Date().toLocaleString('en-MY')}"`);
    csv.push(`"Date Range: ${document.getElementById('date_from')?.value} to ${document.getElementById('date_to')?.value}"`);
    csv.push('');

    if (includeStats) {
        csv.push(`"SUMMARY STATISTICS"`);
        document.querySelectorAll('.stat-card').forEach(card => {
            const title = card.querySelector('h3')?.textContent || '';
            const value = card.querySelector('.stat-value')?.textContent || '';
            csv.push(`"${title}","${value}"`);
        });
        csv.push('');
    }

    csv.push(`"DETAILED RECORDS"`);
    csv.push('"No","Name","Facility","Programme","Department","Date","Status"');

    allRecords.forEach((row, index) => {
        const facility = facilityMap[row.facilityName] || row.facilityName;
        const date = new Date(row.select_date).toLocaleDateString('en-MY', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
        const escape = (str) => String(str || '').replace(/"/g, '""');
        csv.push(`"${index+1}","${escape(row.name)}","${escape(facility)}","${escape(row.purpose)}","${escape(row.depart)}","${date}","${row.status}"`);
    });

    const BOM = '\uFEFF';
    const blob = new Blob([BOM + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `FacilityOps_Booking_Report_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();

    showNotification(`✅ CSV exported successfully! (${allRecords.length} records)`);
}

// ============================================
// 🌐 FETCH ALL RECORDS FROM SERVER
// ============================================
async function fetchAllRecords() {
    const params = new URLSearchParams(window.location.search);
    params.set('export_all', '1');
    params.delete('page');
    const response = await fetch('report.php?' + params.toString());
    return await response.json();
}

// Helper to load external scripts
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }
            existing.addEventListener('load', resolve);
            existing.addEventListener('error', reject);
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        });
        script.addEventListener('error', () => {
            reject(new Error(`Failed to load: ${src}`));
        });
        document.head.appendChild(script);
    });
}

// ============================================
// 🔔 NOTIFICATION HELPER
// ============================================
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#48bb78' : '#4299e1'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        font-weight: 600;
        animation: slideIn 0.3s ease-out;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// 🎨 ADD ANIMATIONS
// ============================================
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(animationStyles);