<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #24332B
        }

        h1 {
            font-size: 20px
        }

        h2 {
            font-size: 14px;
            margin-top: 24px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        td,
        th {
            padding: 7px;
            text-align: left;
            border-bottom: 1px solid #c5d6cc
        }

        th {
            background: #eaf3ee
        }

        tr {
            page-break-inside: avoid
        }
    </style>
</head>

<body>
    <h1>Transolutions · {{ ucfirst($period) }} report</h1>
    <p>Automatic Transmission Overhaul · Davao City</p>
    <p>{{ $start->format('F j, Y') }} – {{ $end->format('F j, Y') }} (Asia/Manila)</p>
    <p>Sales include recorded payments in this period. Low stock reflects current inventory.</p>
    @include('reports.tables')
</body>

</html>
