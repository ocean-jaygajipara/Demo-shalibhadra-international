<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>SalesOrder Report - Print</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 5px;
            color: #333;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
            color: #7f8c8d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        th.text-center,
        td.text-center {
            text-align: center;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <h1>SalesOrder Report</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>


    @php
        $chartLabels = $chartData->pluck('label');
        $chartValues = $chartData->pluck('value');

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $chartLabels,
                'datasets' => [[
                    'label' => 'Total Sales',
                    'data' => $chartValues,
                    'backgroundColor' => '#4e73df',
                    'barThickness' => 20
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => false]
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'grid' => ['display' => false]
                    ],
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]
        ];

        $chartUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig));
    @endphp

    @if ($display_format === 'graph' || $display_format === 'both')
        <div style="text-align:center; margin-bottom:30px;">
            <img src="{{ $chartUrl }}" alt="Sales Chart" style="max-width: 100%; height: auto;">
        </div>
    @endif

    @if ($display_format === 'table' || $display_format === 'both')
        <table id="printArea">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th class="text-center">{{ strtoupper(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td class="text-center">{{ $row[$column] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif


    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function printAndRedirect() {
            window.print();

            window.onafterprint = function() {
                setTimeout(function() {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }

        window.onload = function () {
            const element = document.getElementById('printArea');
            element.style.fontSize = '30px';

            html2canvas(element).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                document.body.innerHTML = `<img src="${imgData}" style="max-width:100%; display:block; margin:auto;" />`;
                setTimeout(() => {
                    window.print();
                    window.onafterprint = () => window.close();
                }, 500);
            });
        };
    </script>
</body>

</html>
