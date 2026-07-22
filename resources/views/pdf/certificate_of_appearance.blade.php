<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@page { size: 297mm 210mm; margin: 0; }

html, body {
    font-family: "Times New Roman", Times, serif;
    background: #fff;
}

/* ── Page frame ── */
.page {
    width:    265mm;
    height:   186mm;
    margin:   1mm 1mm 1mm 1mm;
    border:   1.5px solid #444;
    overflow: hidden;
    position: relative;
    padding:  10mm 14mm 10mm 14mm;
}

/* ── Corner images ── */
.corner {
    position: absolute;
    width: 42mm;
    height: 42mm;
}
.corner-tl { top: 2mm;    left: 2mm; }
.corner-tr { top: 2mm;    right: 2mm; }
.corner-bl { bottom: 2mm; left: 2mm; }
.corner-br { bottom: 2mm; right: 2mm; }

/* ── CTRL # ── */
.ctrl-row {
    text-align:    right;
    font-size:     9.5pt;
    font-weight:   bold;
    letter-spacing: 0.5px;
    margin-bottom: 2mm;
    padding-right: 32mm;
}
.ctrl-blank {
    display:        inline-block;
    border-bottom:  1px solid #222;
    min-width:      35mm;
    vertical-align: bottom;
    line-height:    1;
}

/* ── Header section ── */
.main-section {
    padding: 0 6mm;
}

.htbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
.htbl td { height: 45mm; vertical-align: middle; }
.h-seal { width: 45mm; text-align: center; }
.h-mid  { text-align: center; padding: 0; }
.h-mid-inner { width: 100%; height: 45mm; border-collapse: collapse; }
.h-mid-inner td { vertical-align: middle; text-align: center; height: 45mm; padding-top: 6mm; }
.seal-img { width: 45mm; height: 45mm; display: block; margin: 0 auto; }

.rep  { font-size: 10.5pt;  line-height: 1.7; color: #222; }
.muni { font-size: 12pt;   font-weight: 900; text-transform: uppercase;
        letter-spacing: 3px; margin-top: 1.5mm; }
.sb   { font-size: 15pt;   font-weight: 900; text-transform: uppercase;
        letter-spacing: 3px; margin: 1.5mm 0 1mm; }
.title-section {
    text-align: center;
    margin-top: -3mm;
    margin-bottom: 6mm;
}
.coa  { font-size: 20pt;   font-weight: 900; text-transform: uppercase;
        letter-spacing: 1px; line-height: 1.15; white-space: nowrap; }

/* ── Body ── */
.content-inner {
    margin-top: 0;
    padding: 0 0 0 20mm;
}

.addressee {
    font-size: 14pt;
    font-weight: bold;
    margin-bottom: 3mm;
}

.form-body {
    font-size: 14pt;
}

.cert-line {
    font-size: 14pt;
    line-height: 1.65;
    text-align: left;
    word-spacing: 4px;
    white-space: nowrap;
}
.cert-indent { padding-left: 12mm; }
.cert-strong {
    font-weight: bold;
    letter-spacing: 1.4px;
}
.inline-fill {
    display: inline-block;
    border-bottom: 1px solid #222;
    vertical-align: baseline;
    line-height: 1;
    padding-bottom: 1px;
    text-align: center;
}
.fill-name    { min-width: 132mm; }
.fill-address { min-width: 100mm; }
.fill-date    { min-width: 72mm; }
.fill-purpose { min-width: 86mm; }
.fill-wide    { min-width: 146mm; }
.fill-day     { min-width: 13mm; }
.fill-month   { min-width: 36mm; }

/* ── Signature ── */
.stbl { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 35mm; }
.sig-right { text-align: center; width: 50%; vertical-align: bottom; }
.sig-name {
    font-size:      11.5pt;
    font-weight:    bold;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-top:     2px solid #222;
    display:        inline-block;
    padding-top:    2px;
    min-width:      60mm;
    text-align:     center;
}
.sig-title { font-size: 9.5pt; margin-top: 1px; }

/* ── QR verification ── */
.qr-wrap {
    position: absolute;
    bottom: 18mm;
    left: 20mm;
    text-align: center;
    z-index: 5;
}
.qr-code {
    width: 22mm;
    height: 22mm;
    display: block;
    background: #fff;
}
.qr-label {
    font-size: 6.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #222;
    margin-top: 1mm;
    background: #fff;
    padding: 0.5mm 1mm;
}
</style>
</head>
<body>
<div class="page">

    <!-- Corner images -->
    <img class="corner corner-tl" src="{{ public_path('images/upper-left.png') }}" alt=""/>
    <img class="corner corner-tr" src="{{ public_path('images/upper-right.png') }}" alt=""/>
    <img class="corner corner-bl" src="{{ public_path('images/lower-left.png') }}" alt=""/>
    <img class="corner corner-br" src="{{ public_path('images/lower-right.png') }}" alt=""/>

    @if(!empty($qr_code))
    <div class="qr-wrap">
        <img class="qr-code" src="data:image/svg+xml;base64,{{ $qr_code }}" alt="Verification QR Code"/>
        <div class="qr-label">Scan to Verify</div>
    </div>
    @endif

    <!-- CTRL # -->
    <div class="ctrl-row">
        CTRL&nbsp;#:&nbsp;<span class="ctrl-blank">{{ $certificate_no }}</span>
    </div>

    <!-- Header + Body -->
    <div class="main-section">
        <table class="htbl">
            <tr>
                <td class="h-seal">
                    @if(file_exists(public_path('images/certlogo.png')))
                    <img class="seal-img" src="{{ public_path('images/certlogo.png') }}" alt="Seal"/>
                    @endif
                </td>
                <td class="h-mid">
                    <table class="h-mid-inner">
                        <tr>
                            <td>
                                <div class="rep">Republic of the Philippines</div>
                                <div class="rep">Province of Albay</div>
                                <div class="muni">Municipality of Oas</div>
                                <div class="sb">Sangguniang Bayan</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="h-seal">
                    @if(file_exists(public_path('images/logo.png')))
                    <img class="seal-img" src="{{ public_path('images/logo.png') }}" alt="Seal"/>
                    @endif
                </td>
            </tr>
        </table>

        <div class="title-section">
            <div class="coa">Certificate of Appearance</div>
        </div>

        <div class="content-inner">
            <p class="addressee">TO WHOM IT MAY CONCERN:</p>

            <div class="form-body">
                <div class="cert-line cert-indent">
                    <span class="cert-strong">THIS&nbsp;&nbsp;IS&nbsp;&nbsp;TO&nbsp;&nbsp;CERTIFY</span>
                    that <span class="inline-fill fill-name">{{ $visitor_name }}</span>
                </div>
                <div class="cert-line">
                    of <span class="inline-fill fill-address">{{ $address ?? '&nbsp;' }}</span>,
                    personally appeared in the office on
                </div>
                <div class="cert-line">
                    <span class="inline-fill fill-date">{{ $day }} of {{ $month_year }}</span>
                    for the purpose of <span class="inline-fill fill-purpose">{{ $purpose }}</span>
                </div>
                <div class="cert-line">
                    <span class="inline-fill fill-wide">&nbsp;</span>,
                </div>

                <div class="cert-line cert-indent" style="margin-top: 2mm;">
                    ISSUED this <span class="inline-fill fill-day">{{ $day }}</span>
                    day of <span class="inline-fill fill-month">{{ $month_year }}</span>,
                    at Oas, Albay upon request of the,
                </div>
                <div class="cert-line">
                    interested party for reference purpose.
                </div>
            </div>

            <!-- Signature -->
            <table class="stbl">
                <tr>
                    <td style="width:50%;"></td>
                    <td class="sig-right">
                        <div class="sig-name">Wilma R. Gamboa</div>
                        <div class="sig-title">Secretary to the Sanggunian</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</div>
</body>
</html>
