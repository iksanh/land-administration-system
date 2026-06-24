<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Berita Acara Pemeriksaan Lapang</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page { size: 21cm 29.7cm; margin: 2.5cm 2.5cm 2.5cm 3cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    @include('berita-acara._dokumen', ['ba' => $ba, 'mode' => 'word'])
</body>
</html>
