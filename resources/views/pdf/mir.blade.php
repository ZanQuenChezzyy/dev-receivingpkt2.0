<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak MIR</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 100%;
            height: 100%;
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .mir-container {
            height: 48%; /* Takes up roughly half the page */
            box-sizing: border-box;
            position: relative;
        }
        .mir-separator {
            height: 2%;
            border-bottom: 1px dashed #999;
            margin-bottom: 2%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: -1px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 3px 5px;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .align-top { vertical-align: top; }
        
        .header-table td { padding: 3px 5px; }
        .items-table th, .items-table td { padding: 4px 5px; }
        .footer-table td { padding: 3px 5px; }
    </style>
</head>
<body>
    @include('pdf.mir_content')
</body>
</html>
