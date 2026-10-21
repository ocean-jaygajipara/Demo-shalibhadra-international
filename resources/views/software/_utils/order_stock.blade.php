<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management Table</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/1.119.0/tabler-icons.min.css">
    <style>
        body {
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-fluid {
            max-width: 1400px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border: none;
        }

        .table-container {
            overflow-x: auto;
            background: white;
        }

        .data-table {
            margin: 0;
            font-size: 13px;
        }

        .data-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            padding: 12px 10px;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .data-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tbody tr:hover {
            background-color: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            cursor: pointer;
        }

        .data-table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border: none;
        }

        .row-highlight {
            background-color: #e3f2fd !important;
        }

        .row-highlight:hover {
            background-color: #bbdefb !important;
        }

        .row-warning {
            background-color: #fff3e0 !important;
        }

        .row-warning:hover {
            background-color: #ffe0b2 !important;
        }

        .row-danger {
            background-color: #ffebee !important;
        }

        .row-danger:hover {
            background-color: #ffcdd2 !important;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-stock {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .qty-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .party-code {
            font-weight: 600;
            color: #667eea;
        }

        .order-ref {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-info h6 {
            margin: 0;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }

        .stat-info h3 {
            margin: 5px 0 0 0;
            font-weight: 700;
            color: #333;
        }

        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Stats Section -->
        <div class="row">


        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="ti ti-list-details"></i> Order Management System</h4>
            </div>
            <div class="table-container">
                <table class="table data-table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>QA No</th>
                            <th>Date</th>
                            <th>Order Ref</th>
                            <th>Party Code</th>
                            <th>Item Name</th>
                            <th>Pending</th>
                            <th>FG Stock</th>
                            <th>Del. Date</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const orderData = [
            { id: 1, qaNo: '121', date: '16-06-2025', orderRef: '21262025', partyCode: 'SPL-CUS-165', itemName: 'HI MOLY X12MM OD X10.80MM L WITH O...', pending: '10050', stock: '0 PCS', delDate: '31-12-2025', rowClass: 'row-highlight' },
            { id: 2, qaNo: '242', date: '29-07-2025', orderRef: 'on whatsapp', partyCode: 'SPL-CUS-157', itemName: 'M8 BRASS ADAPTOR', pending: '20011', stock: '5 PCS', delDate: '30-07-2025', rowClass: '' },
            { id: 3, qaNo: '243', date: '27-08-2025', orderRef: 'on whatsapp', partyCode: 'SPL-CUS-157', itemName: '9MM SQUARE X 31.00MM L BRACKET HAR...', pending: '200,000', stock: '16598,209 KG', delDate: '30-09-2025', rowClass: '' },
            { id: 4, qaNo: '243', date: '27-08-2025', orderRef: 'on whatsapp', partyCode: 'SPL-CUS-157', itemName: 'DHHELM M6 3K X 16 X 39.75MM L X 8.5...', pending: '300,000', stock: '0 PCS', delDate: '30-09-2025', rowClass: '' },
            { id: 5, qaNo: '243', date: '15-09-2025', orderRef: 'on WHATSAPP', partyCode: 'SPL-CUS-180', itemName: 'M5 x 6MM L x 7.50MM HEX FH STUD PCS', pending: '12,000', stock: '14000 PCS', delDate: '25-09-2025', rowClass: '' },
            { id: 6, qaNo: '314', date: '28-09-2025', orderRef: 'on whatsapp', partyCode: 'SPL-CUS-139', itemName: '3/8 x M9 Adaptor', pending: '6739', stock: '0 Pcs', delDate: '10-10-2025', rowClass: '' },
            { id: 7, qaNo: '318', date: '29-09-2025', orderRef: 'on whatsapp', partyCode: 'SPL-CUS-161', itemName: '5/8GM L 21.0MM HEX CLOSED', pending: '5900', stock: '21954(61.402...', delDate: '10-10-2025', rowClass: '' },
            { id: 8, qaNo: '325', date: '07-10-2025', orderRef: 'R2MP0/59S5...', partyCode: 'SPL-CUS-113', itemName: 'M5 x 4MM L x 10.00MM OD Brass Insert P...', pending: '80000', stock: '52504(80.012...', delDate: '05-11-2025', rowClass: '' },
            { id: 9, qaNo: '325', date: '07-10-2025', orderRef: 'R2MP0/59S5...', partyCode: 'SPL-CUS-113', itemName: 'M5 x 1.5MM L x 8.1MM OD Brass Insert', pending: '54000', stock: '54908(217.83...', delDate: '05-11-2025', rowClass: '' },
            { id: 10, qaNo: '325', date: '07-10-2025', orderRef: 'R2MP0/59S5...', partyCode: 'SPL-CUS-113', itemName: 'M6 x 5MM L x 10MM OD Round Brass Insert', pending: '54000', stock: '12899(8.174 I...', delDate: '05-11-2025', rowClass: '' },
            { id: 11, qaNo: '326', date: '14-10-2025', orderRef: 'R1AMPO/003...', partyCode: 'SPL-CUS-114', itemName: 'M14x 81 MM x 1.25MM OD WITH 9.2 3MM L', pending: '10000', stock: '7945(67.918...', delDate: '30-10-2025', rowClass: 'row-danger' },
            { id: 12, qaNo: '326', date: '14-10-2025', orderRef: 'R2MP0/59S5...', partyCode: 'SPL-CUS-114', itemName: 'M5 x 4MM L x 10.00MM OD Brass Insert P...', pending: '32000', stock: '52504(80.012...', delDate: '30-10-2025', rowClass: 'row-danger' },
            { id: 13, qaNo: '339', date: '16-10-2025', orderRef: '106525', partyCode: 'SPL-CUS-030', itemName: 'M5X19MM THRU HOLE', pending: '15000', stock: '29952(53.705...', delDate: '31-12-2025', rowClass: '' },
            { id: 14, qaNo: '339', date: '16-10-2025', orderRef: '106525', partyCode: 'SPL-CUS-030', itemName: 'M6X19MM BLIND', pending: '10000', stock: '14696.97 KG', delDate: '31-12-2025', rowClass: '' },
            { id: 15, qaNo: '339', date: '16-10-2025', orderRef: '106525', partyCode: 'SPL-CUS-030', itemName: 'M8X19MM BLIND', pending: '15000', stock: '56446(52.318...', delDate: '31-12-2025', rowClass: '' },
            { id: 16, qaNo: '339', date: '16-10-2025', orderRef: '106525', partyCode: 'SPL-CUS-030', itemName: 'M6 x 15 MM L x 10 MM HEX', pending: '2000', stock: '1275(7.578 Kg)', delDate: '31-12-2025', rowClass: '' }
        ];

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            data.forEach(order => {
                const row = document.createElement('tr');
                row.className = order.rowClass;
                row.innerHTML = `
                    <td><strong>${order.id}</strong></td>
                    <td><span class="badge qty-badge">${order.qaNo}</span></td>
                    <td>${order.date}</td>
                    <td><span class="order-ref">${order.orderRef}</span></td>
                    <td><span class="party-code">${order.partyCode}</span></td>
                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${order.itemName}">${order.itemName}</td>
                    <td><span class="badge status-pending">${order.pending}</span></td>
                    <td><span class="badge status-stock">${order.stock}</span></td>
                    <td>${order.delDate}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredData = orderData.filter(order =>
                order.qaNo.toLowerCase().includes(searchTerm) ||
                order.partyCode.toLowerCase().includes(searchTerm) ||
                order.itemName.toLowerCase().includes(searchTerm) ||
                order.orderRef.toLowerCase().includes(searchTerm)
            );
            renderTable(filteredData);
        });

        // Initial render
        renderTable(orderData);
    </script>
</body>
</html>
