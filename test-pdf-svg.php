<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../../vendor/tecnickcom/tcpdf/tcpdf.php';

// Gerar SVG simples
$svg = '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="white"/>
    <rect x="50" y="50" width="100" height="100" fill="#3b82f6"/>
    <text x="200" y="100" font-size="20" text-anchor="middle" fill="#000">Teste SVG no PDF</text>
</svg>';

// Criar HTML com SVG
$html = '
<style>
    body { font-family: helvetica; }
    .title { font-size: 16pt; font-weight: bold; margin-bottom: 10px; }
</style>
<div class="title">Teste de SVG no TCPDF</div>
' . $svg . '
<p>Se você ver um quadrado azul acima, o SVG está funcionando!</p>
';

// Gerar PDF
try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Test');
    $pdf->SetAuthor('Test');
    $pdf->SetTitle('Teste SVG');

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);

    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('test_svg.pdf', 'I');
} catch (Exception $e) {
    echo '<pre>';
    echo 'Erro: ' . $e->getMessage();
    echo "\n\nStack trace:\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}
