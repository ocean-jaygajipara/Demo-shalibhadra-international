<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Job Work Issue {{ $jobIssue->job_work_issue_no ?? '-' }} - Print</title>
    <style>
        @page {
            size: A4;
            /* margin-top: 20mm;
            margin-bottom: 5mm;
            margin-left: 0mm;
            margin-right: 0mm; */
            margin:10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            /* Scrollbar fix: Ensure the body doesn't overflow */
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* Set default border for cells for structural tables only */
        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* Remove border from the Movement Rule table cells, keeping only the top/bottom of the whole table */
        .movement-rule td {
            border-top: none;
            border-bottom: none;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* Helper to span borders cleanly within padded divs */
        .full-width-line {
            border-top: 1px solid #000;
            margin: 8px -8px;
            /* Use -8px margin to span across the 8px padding of the parent div */
        }
        .page-break {
            page-break-before: always;
            break-before: page;
            display: block;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    @php
    function formatWeight($val)
    {
        if ($val === null || $val === "" || $val === ".") {
            return $val;
        }

        $num = floatval($val);
        if (!is_numeric($num)) {
            return "";
        }

        $intPart = floor($num);
        $decimal = round(($num - $intPart) * 1000);
        $decStr = str_pad($decimal, 3, "0", STR_PAD_LEFT);

        $firstDigit = intval($decStr[0]);
        $lastTwo    = intval(substr($decStr, 1));

        $newFirstDigit = $firstDigit;
        $finalDecimal = 0;

        if ($lastTwo <= 29) {
            $finalDecimal = $newFirstDigit * 100;
        } elseif ($lastTwo <= 79) {
            $finalDecimal = $newFirstDigit * 100 + 50;
        } else {
            $newFirstDigit = $firstDigit + 1;
            $finalDecimal = $newFirstDigit * 100;
        }

        $result = $intPart + ($finalDecimal / 1000);
        return number_format($result, 3, '.', '');
    }
    @endphp
        {{-- ORIGINAL COPY --}}
        @include($modules['folder_path'] . '.' .'job_work_challan_print', ['copyType' => 'Original'])

        {{-- DUPLICATE COPY (NEW PAGE) --}}
        <div class="page-break"></div>
        @include($modules['folder_path'] . '.' .'job_work_challan_print', ['copyType' => 'Duplicate'])

        <script>
            function printAndRedirect() {
                window.print();
                window.onafterprint = function() {
                    setTimeout(function() {
                        window.location.href = "{{ route($modules['route'] . '.index') }}";
                    }, 1);
                };
            }
        </script>


</body>

</html>
