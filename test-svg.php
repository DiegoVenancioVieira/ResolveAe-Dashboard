<?php
// Teste simples para verificar se SVG está sendo gerado

function generateTestSVG() {
    $svg = '<svg width="200" height="100" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';
    $svg .= '<rect x="10" y="10" width="80" height="80" fill="#3b82f6"/>';
    $svg .= '<text x="100" y="55" font-size="14" fill="#000">Teste SVG</text>';
    $svg .= '</svg>';
    return $svg;
}

$testSVG = generateTestSVG();

echo "<h1>Teste de SVG</h1>";
echo "<h2>SVG Inline:</h2>";
echo $testSVG;

echo "<hr>";
echo "<h2>SVG Raw Code:</h2>";
echo "<pre>" . htmlspecialchars($testSVG) . "</pre>";
