<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
    }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10.5px;
        color: #111;
        line-height: 1.35;
    }

    .header {
        background: #000;
        color: #fff;
        padding: 12px 36px 12px 24px;
        width: 100%;
        margin: 0;
    }
    .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .header-table td { vertical-align: middle; color: #fff; padding: 0; }
    .header-logo-cell { width: 34%; padding-right: 8px; }
    .header-meta-cell { width: 66%; padding-left: 8px; padding-right: 8px; }
    .logo {
        height: 44px;
        width: auto;
        max-width: 100%;
        display: block;
    }
    .header-fallback-name {
        font-size: 14px;
        font-weight: bold;
    }
    .biz-meta {
        font-size: 9px;
        color: #f0f0f0;
        line-height: 1.45;
        text-align: left;
        width: 58%;
        margin-left: auto;
        margin-right: 4px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .biz-meta .line {
        margin: 0 0 2px;
        white-space: normal;
    }

    .page-body {
        padding: 16px 28px 220px;
    }

    .doc-title {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 1px;
        margin: 0 0 10px;
        text-transform: uppercase;
    }

    .meta-strip {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
        background: #f5f5f5;
        border: 1px solid #e5e5e5;
    }
    .meta-strip td {
        padding: 7px 10px;
        font-size: 10px;
        border-right: 1px solid #e5e5e5;
        width: 25%;
        vertical-align: top;
    }
    .meta-strip td:last-child { border-right: none; }
    .meta-strip .label {
        display: block;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #666;
        margin-bottom: 2px;
    }
    .meta-strip .value { font-weight: bold; color: #111; }

    .section-label {
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 4px;
        color: #333;
    }

    .bill-box {
        border: 1px solid #e5e5e5;
        padding: 8px 10px;
        background: #fafafa;
        margin-bottom: 10px;
    }
    .bill-name { font-size: 12px; font-weight: bold; margin-bottom: 3px; }
    .bill-line { color: #444; margin-bottom: 1px; font-size: 10px; }

    .items-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
    .items-table th {
        background: #000;
        color: #fff;
        text-align: left;
        padding: 7px 8px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .items-table td {
        padding: 7px 8px;
        border-bottom: 1px solid #e8e8e8;
        vertical-align: top;
        font-size: 10.5px;
    }
    .items-table tr:nth-child(even) td { background: #fafafa; }

    .text-right { text-align: right; }

    .totals-wrap { width: 240px; margin-left: auto; margin-top: 8px; }
    .totals-table { width: 100%; border-collapse: collapse; }
    .totals-table td { padding: 3px 0; font-size: 10.5px; }
    .totals-table .total-row td {
        font-weight: bold;
        font-size: 12px;
        border-top: 1.5px solid #000;
        padding-top: 6px;
        margin-top: 2px;
    }

    .notes-block {
        margin-top: 8px;
        padding: 7px 10px;
        border: 1px solid #e5e5e5;
        background: #fafafa;
        font-size: 9.5px;
    }
    .notes-block + .notes-block { margin-top: 6px; }
    .notes-title { font-weight: bold; margin-bottom: 2px; }

    .page-end {
        position: absolute;
        left: 28px;
        right: 28px;
        bottom: 18px;
        width: auto;
    }

    .bottom-block { margin-top: 14px; }
    .sign-row { width: 100%; border-collapse: collapse; }
    .sign-row td { width: 46%; vertical-align: top; padding: 0; }
    .sign-row td.gap { width: 8%; }
    .sign-line {
        border-top: 1px solid #333;
        margin-top: 24px;
        padding-top: 4px;
        font-size: 9px;
        color: #555;
    }

    .footer {
        margin-top: 10px;
        text-align: center;
        font-size: 8px;
        color: #777;
        border-top: 1px solid #ddd;
        padding-top: 6px;
        clear: both;
    }

    .receipt-amount { font-size: 16px; font-weight: bold; margin: 4px 0 2px; }
    .muted { color: #777; font-size: 9px; }
</style>
