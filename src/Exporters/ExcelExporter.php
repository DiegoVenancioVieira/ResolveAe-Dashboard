<?php
/**
 * Exportador Excel
 * Gera arquivos XLSX usando formato XML (Office Open XML)
 */

class ExcelExporter {
    private $report;
    private $tempDir;
    private $excelFile;

    public function __construct($report) {
        $this->report = $report;
        $this->tempDir = sys_get_temp_dir() . '/glpi_export_excel_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Exporta relatório como arquivo XLSX
     */
    public function export() {
        $this->excelFile = $this->tempDir . '/relatorio_glpi.xlsx';

        // Cria estrutura do XLSX
        $this->createXLSXStructure();

        return $this->excelFile;
    }

    /**
     * Cria estrutura do arquivo XLSX
     */
    private function createXLSXStructure() {
        $zip = new ZipArchive();

        if ($zip->open($this->excelFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Não foi possível criar arquivo Excel');
        }

        // Estrutura de diretórios
        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');

        // Arquivos base
        $zip->addFromString('[Content_Types].xml', $this->getContentTypes());
        $zip->addFromString('_rels/.rels', $this->getRels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getWorkbookRels());
        $zip->addFromString('xl/workbook.xml', $this->getWorkbook());
        $zip->addFromString('xl/styles.xml', $this->getStyles());

        // Adiciona sheets
        $sheetId = 1;

        // Sheet de informações
        $zip->addFromString('xl/worksheets/sheet' . $sheetId . '.xml', $this->generateMetadataSheet());
        $sheetId++;

        // Sheets para cada seção
        foreach ($this->report['data'] as $section => $data) {
            $methodName = 'generate' . str_replace('_', '', ucwords($section, '_')) . 'Sheet';

            if (method_exists($this, $methodName)) {
                $zip->addFromString('xl/worksheets/sheet' . $sheetId . '.xml', $this->$methodName($data));
                $sheetId++;
            }
        }

        $zip->close();
    }

    /**
     * Content Types XML
     */
    private function getContentTypes() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        $sheetId = 1;
        $totalSheets = count($this->report['data']) + 1; // +1 para metadata
        for ($i = 1; $i <= $totalSheets; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        $xml .= '</Types>';
        return $xml;
    }

    /**
     * Relationships XML
     */
    private function getRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
               '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
               '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
               '</Relationships>';
    }

    /**
     * Workbook Relationships XML
     */
    private function getWorkbookRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $sheetId = 1;
        $totalSheets = count($this->report['data']) + 1;
        for ($i = 1; $i <= $totalSheets; $i++) {
            $rId = $i + 1;
            $xml .= '<Relationship Id="rId' . $rId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        $xml .= '</Relationships>';
        return $xml;
    }

    /**
     * Workbook XML
     */
    private function getWorkbook() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';

        // Sheet de informações
        $xml .= '<sheet name="Informações" sheetId="1" r:id="rId2"/>';

        // Sheets de dados
        $sheetId = 2;
        $sectionNames = ReportGenerator::getSectionNames();
        foreach ($this->report['metadata']['secoes'] as $section) {
            $rId = $sheetId + 1;
            $name = $this->sanitizeSheetName($sectionNames[$section]);
            $xml .= '<sheet name="' . $name . '" sheetId="' . $sheetId . '" r:id="rId' . $rId . '"/>';
            $sheetId++;
        }

        $xml .= '</sheets>';
        $xml .= '</workbook>';
        return $xml;
    }

    /**
     * Styles XML
     */
    private function getStyles() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
               '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
               '<fonts count="2">' .
               '<font><sz val="11"/><name val="Calibri"/></font>' .
               '<font><b/><sz val="11"/><name val="Calibri"/></font>' .
               '</fonts>' .
               '<fills count="3">' .
               '<fill><patternFill patternType="none"/></fill>' .
               '<fill><patternFill patternType="gray125"/></fill>' .
               '<fill><patternFill patternType="solid"><fgColor rgb="FF4CAF50"/><bgColor indexed="64"/></patternFill></fill>' .
               '</fills>' .
               '<borders count="2">' .
               '<border><left/><right/><top/><bottom/><diagonal/></border>' .
               '<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>' .
               '</borders>' .
               '<cellXfs count="3">' .
               '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
               '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/>' .
               '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyBorder="1"/>' .
               '</cellXfs>' .
               '</styleSheet>';
    }

    /**
     * Gera Sheet de Metadados
     */
    private function generateMetadataSheet() {
        $metadata = $this->report['metadata'];

        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'RELATÓRIO GLPI - DASHBOARD RESOLVEAE', 1)
        ]);
        $xml .= $this->createRow($row++, []);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Período:', 1),
            $this->createCell('B', $metadata['periodo_formatado'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Data de Geração:', 1),
            $this->createCell('B', $metadata['data_geracao'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Seções Incluídas:', 1),
            $this->createCell('B', $metadata['secoes_incluidas'], 2)
        ]);

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet do Resumo Executivo
     */
    private function generateResumoExecutivoSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'RESUMO EXECUTIVO', 1)
        ]);
        $xml .= $this->createRow($row++, []);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Métrica', 1),
            $this->createCell('B', 'Valor', 1)
        ]);

        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Chamados Criados', 2),
            $this->createCell('B', $data['total_criados'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Chamados Abertos', 2),
            $this->createCell('B', $data['total_abertos'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Chamados Resolvidos', 2),
            $this->createCell('B', $data['total_resolvidos'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Chamados Fechados', 2),
            $this->createCell('B', $data['total_fechados'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Tempo Médio de Resolução', 2),
            $this->createCell('B', $data['tempo_medio_resolucao'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Satisfação Média', 2),
            $this->createCell('B', $data['satisfacao_media'] . ' ★ (' . $data['satisfacao_percentual'] . ')', 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Chamados Atrasados', 2),
            $this->createCell('B', $data['total_atrasados'], 2)
        ]);

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Chamados por Status
     */
    private function generateChamadosStatusSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Status', 1),
            $this->createCell('B', 'Quantidade', 1)
        ]);

        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total Criados', 2),
            $this->createCell('B', $data['total_criados'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Novos', 2),
            $this->createCell('B', $data['novos'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Atribuídos', 2),
            $this->createCell('B', $data['atribuidos'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Planejados', 2),
            $this->createCell('B', $data['planejados'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Pendentes', 2),
            $this->createCell('B', $data['pendentes'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Resolvidos', 2),
            $this->createCell('B', $data['resolvidos'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Fechados', 2),
            $this->createCell('B', $data['fechados'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total Abertos', 2),
            $this->createCell('B', $data['total_abertos'], 2)
        ]);

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Chamados por Prioridade
     */
    private function generateChamadosPrioridadeSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Prioridade', 1),
            $this->createCell('B', 'Quantidade', 1)
        ]);

        foreach ($data as $item) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', $item['priority_name'], 2),
                $this->createCell('B', $item['total'], 2)
            ]);
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Chamados por Categoria
     */
    private function generateChamadosCategoriaSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Categoria', 1),
            $this->createCell('B', 'Quantidade', 1)
        ]);

        foreach ($data as $item) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', $item['categoria'], 2),
                $this->createCell('B', $item['total'], 2)
            ]);
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Chamados por Setor
     */
    private function generateChamadosSetoresSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Setor/Entidade', 1),
            $this->createCell('B', 'Quantidade', 1)
        ]);

        foreach ($data as $item) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', $item['entidade'], 2),
                $this->createCell('B', $item['total'], 2)
            ]);
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Tendência Mensal
     */
    private function generateTendenciaMensalSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Mês/Ano', 1),
            $this->createCell('B', 'Quantidade', 1)
        ]);

        foreach ($data as $item) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', $item['mes_formatado'], 2),
                $this->createCell('B', $item['total'], 2)
            ]);
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Indicadores de Técnicos
     */
    private function generateIndicadoresTecnicosSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Técnico', 1),
            $this->createCell('B', 'Total', 1),
            $this->createCell('C', 'Fechados', 1),
            $this->createCell('D', 'Abertos', 1),
            $this->createCell('E', 'Taxa Resolução (%)', 1)
        ]);

        foreach ($data as $item) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', $item['tecnico'], 2),
                $this->createCell('B', $item['total_chamados'], 2),
                $this->createCell('C', $item['fechados'], 2),
                $this->createCell('D', $item['abertos'], 2),
                $this->createCell('E', $item['taxa_resolucao'], 2)
            ]);
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Tempo de Resolução
     */
    private function generateTempoResolucaoSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Métrica', 1),
            $this->createCell('B', 'Valor', 1)
        ]);

        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Tempo Médio', 2),
            $this->createCell('B', $data['media_formatada'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Tempo Mínimo (horas)', 2),
            $this->createCell('B', round($data['min_horas'], 1), 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Tempo Máximo (horas)', 2),
            $this->createCell('B', round($data['max_horas'], 1), 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total Resolvidos', 2),
            $this->createCell('B', $data['total_resolvidos'], 2)
        ]);

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Chamados Atrasados
     */
    private function generateChamadosAtrasadosSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total Atrasados', 1),
            $this->createCell('B', $data['total_vencidos'], 1)
        ]);
        $xml .= $this->createRow($row++, []);

        if (!empty($data['lista_array'])) {
            $xml .= $this->createRow($row++, [
                $this->createCell('A', 'Lista de Chamados', 1)
            ]);

            foreach ($data['lista_array'] as $chamado) {
                $xml .= $this->createRow($row++, [
                    $this->createCell('A', $chamado, 2)
                ]);
            }
        }

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Gera Sheet de Satisfação
     */
    private function generateSatisfacaoSheet($data) {
        $xml = $this->getSheetHeader();
        $xml .= '<sheetData>';

        $row = 1;
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Métrica', 1),
            $this->createCell('B', 'Valor', 1)
        ]);

        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Satisfação Média (Estrelas)', 2),
            $this->createCell('B', $data['estrelas'], 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Satisfação Média (Percentual)', 2),
            $this->createCell('B', $data['percentual'] . '%', 2)
        ]);
        $xml .= $this->createRow($row++, [
            $this->createCell('A', 'Total de Avaliações', 2),
            $this->createCell('B', $data['total_avaliacoes'], 2)
        ]);

        $xml .= '</sheetData>';
        $xml .= $this->getSheetFooter();

        return $xml;
    }

    /**
     * Header do Sheet
     */
    private function getSheetHeader() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
               '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    }

    /**
     * Footer do Sheet
     */
    private function getSheetFooter() {
        return '</worksheet>';
    }

    /**
     * Cria uma linha
     */
    private function createRow($rowNumber, $cells) {
        $xml = '<row r="' . $rowNumber . '">';
        $xml .= implode('', $cells);
        $xml .= '</row>';
        return $xml;
    }

    /**
     * Cria uma célula
     */
    private function createCell($col, $value, $style = 0) {
        $cellRef = $col . $this->currentRow;
        $value = $this->escapeXML($value);

        if (is_numeric($value)) {
            return '<c r="' . $cellRef . '" s="' . $style . '"><v>' . $value . '</v></c>';
        } else {
            return '<c r="' . $cellRef . '" s="' . $style . '" t="inlineStr"><is><t>' . $value . '</t></is></c>';
        }
    }

    private $currentRow = 1;

    /**
     * Sanitiza nome da sheet
     */
    private function sanitizeSheetName($name) {
        $name = str_replace(['/', '\\', '?', '*', '[', ']'], '', $name);
        return substr($name, 0, 31);
    }

    /**
     * Escapa XML
     */
    private function escapeXML($value) {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove arquivos temporários
     */
    public function cleanup() {
        if (file_exists($this->excelFile)) {
            unlink($this->excelFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }
}
