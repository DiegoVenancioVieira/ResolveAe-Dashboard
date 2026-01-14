<?php
/**
 * Exportador CSV
 * Gera arquivos CSV com UTF-8 BOM para compatibilidade com Excel brasileiro
 */

class CSVExporter {
    private $report;
    private $zipFile;
    private $tempDir;

    public function __construct($report) {
        $this->report = $report;
        $this->tempDir = sys_get_temp_dir() . '/glpi_export_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    /**
     * Exporta relatório como arquivo ZIP contendo múltiplos CSVs
     */
    public function export() {
        $this->zipFile = $this->tempDir . '/relatorio_glpi.zip';
        $zip = new ZipArchive();

        if ($zip->open($this->zipFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Não foi possível criar arquivo ZIP');
        }

        // Adiciona arquivo de informações do relatório
        $zip->addFromString('00_informacoes.csv', $this->generateMetadataCSV());

        // Gera CSV para cada seção
        foreach ($this->report['data'] as $section => $data) {
            $methodName = 'generate' . str_replace('_', '', ucwords($section, '_')) . 'CSV';

            if (method_exists($this, $methodName)) {
                $csvContent = $this->$methodName($data);
                $filename = $this->getSectionFileName($section) . '.csv';
                $zip->addFromString($filename, $csvContent);
            }
        }

        $zip->close();

        return $this->zipFile;
    }

    /**
     * Gera CSV de metadados
     */
    private function generateMetadataCSV() {
        $metadata = $this->report['metadata'];
        $csv = $this->getUTF8BOM();

        $csv .= "RELATÓRIO GLPI - DASHBOARD RESOLVEAE\n\n";
        $csv .= "Período;{$metadata['periodo_formatado']}\n";
        $csv .= "Data de Geração;{$metadata['data_geracao']}\n";
        $csv .= "Seções Incluídas;{$metadata['secoes_incluidas']}\n\n";

        $csv .= "SEÇÕES DO RELATÓRIO\n";
        $sectionNames = ReportGenerator::getSectionNames();
        foreach ($metadata['secoes'] as $section) {
            $csv .= $sectionNames[$section] . "\n";
        }

        return $csv;
    }

    /**
     * Gera CSV do Resumo Executivo
     */
    private function generateResumoExecutivoCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "RESUMO EXECUTIVO\n\n";
        $csv .= "Métrica;Valor\n";
        $csv .= "Total de Chamados Criados;{$data['total_criados']}\n";
        $csv .= "Total de Chamados Abertos;{$data['total_abertos']}\n";
        $csv .= "Total de Chamados Resolvidos;{$data['total_resolvidos']}\n";
        $csv .= "Total de Chamados Fechados;{$data['total_fechados']}\n";
        $csv .= "Tempo Médio de Resolução;{$data['tempo_medio_resolucao']}\n";
        $csv .= "Satisfação Média (Estrelas);{$data['satisfacao_media']}\n";
        $csv .= "Satisfação Média (Percentual);{$data['satisfacao_percentual']}\n";
        $csv .= "Total de Chamados Atrasados;{$data['total_atrasados']}\n";

        return $csv;
    }

    /**
     * Gera CSV de Chamados por Status
     */
    private function generateChamadosStatusCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "CHAMADOS POR STATUS\n\n";
        $csv .= "Status;Quantidade\n";
        $csv .= "Total Criados;{$data['total_criados']}\n";
        $csv .= "Novos;{$data['novos']}\n";
        $csv .= "Atribuídos;{$data['atribuidos']}\n";
        $csv .= "Planejados;{$data['planejados']}\n";
        $csv .= "Pendentes;{$data['pendentes']}\n";
        $csv .= "Resolvidos;{$data['resolvidos']}\n";
        $csv .= "Fechados;{$data['fechados']}\n";
        $csv .= "Total Abertos;{$data['total_abertos']}\n";

        return $csv;
    }

    /**
     * Gera CSV de Chamados por Prioridade
     */
    private function generateChamadosPrioridadeCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "CHAMADOS POR PRIORIDADE\n\n";
        $csv .= "Prioridade;Quantidade\n";

        foreach ($data as $row) {
            $csv .= "{$row['priority_name']};{$row['total']}\n";
        }

        return $csv;
    }

    /**
     * Gera CSV de Chamados por Categoria
     */
    private function generateChamadosCategoriaCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "CHAMADOS POR CATEGORIA (TOP 10)\n\n";
        $csv .= "Categoria;Quantidade\n";

        foreach ($data as $row) {
            $categoria = str_replace(';', ',', $row['categoria']);
            $csv .= "$categoria;{$row['total']}\n";
        }

        return $csv;
    }

    /**
     * Gera CSV de Chamados por Setor
     */
    private function generateChamadosSetoresCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "CHAMADOS POR SETOR/ENTIDADE (TOP 10)\n\n";
        $csv .= "Setor/Entidade;Quantidade\n";

        foreach ($data as $row) {
            $entidade = str_replace(';', ',', $row['entidade']);
            $csv .= "$entidade;{$row['total']}\n";
        }

        return $csv;
    }

    /**
     * Gera CSV de Tendência Mensal
     */
    private function generateTendenciaMensalCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "TENDÊNCIA MENSAL DE CHAMADOS\n\n";
        $csv .= "Mês/Ano;Quantidade\n";

        foreach ($data as $row) {
            $csv .= "{$row['mes_formatado']};{$row['total']}\n";
        }

        return $csv;
    }

    /**
     * Gera CSV de Indicadores de Técnicos
     */
    private function generateIndicadoresTecnicosCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "INDICADORES DE TÉCNICOS\n\n";
        $csv .= "Técnico;Total Chamados;Fechados;Abertos;Taxa de Resolução (%)\n";

        foreach ($data as $row) {
            $tecnico = str_replace(';', ',', $row['tecnico']);
            $csv .= "$tecnico;{$row['total_chamados']};{$row['fechados']};{$row['abertos']};{$row['taxa_resolucao']}\n";
        }

        return $csv;
    }

    /**
     * Gera CSV de Tempo de Resolução
     */
    private function generateTempoResolucaoCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "TEMPO DE RESOLUÇÃO\n\n";
        $csv .= "Métrica;Valor\n";
        $csv .= "Tempo Médio;{$data['media_formatada']}\n";
        $csv .= "Tempo Mínimo (horas);{$data['min_horas']}\n";
        $csv .= "Tempo Máximo (horas);{$data['max_horas']}\n";
        $csv .= "Total de Chamados Resolvidos;{$data['total_resolvidos']}\n";

        return $csv;
    }

    /**
     * Gera CSV de Chamados Atrasados
     */
    private function generateChamadosAtrasadosCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "CHAMADOS ATRASADOS (SLA)\n\n";
        $csv .= "Total de Chamados Atrasados;{$data['total_vencidos']}\n\n";

        if (!empty($data['lista_array'])) {
            $csv .= "Lista de Chamados Atrasados\n";
            foreach ($data['lista_array'] as $chamado) {
                $chamado = str_replace(';', ',', $chamado);
                $csv .= "$chamado\n";
            }
        }

        return $csv;
    }

    /**
     * Gera CSV de Satisfação
     */
    private function generateSatisfacaoCSV($data) {
        $csv = $this->getUTF8BOM();
        $csv .= "AVALIAÇÕES DE SATISFAÇÃO\n\n";
        $csv .= "Métrica;Valor\n";
        $csv .= "Satisfação Média (Estrelas);{$data['estrelas']}\n";
        $csv .= "Satisfação Média (Percentual);{$data['percentual']}%\n";
        $csv .= "Total de Avaliações;{$data['total_avaliacoes']}\n";

        return $csv;
    }

    /**
     * Retorna UTF-8 BOM para compatibilidade com Excel
     */
    private function getUTF8BOM() {
        return chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    /**
     * Retorna nome do arquivo para cada seção
     */
    private function getSectionFileName($section) {
        $names = [
            'resumo_executivo' => '01_resumo_executivo',
            'chamados_status' => '02_chamados_status',
            'chamados_prioridade' => '03_chamados_prioridade',
            'chamados_categoria' => '04_chamados_categoria',
            'chamados_setores' => '05_chamados_setores',
            'tendencia_mensal' => '06_tendencia_mensal',
            'indicadores_tecnicos' => '07_indicadores_tecnicos',
            'tempo_resolucao' => '08_tempo_resolucao',
            'chamados_atrasados' => '09_chamados_atrasados',
            'satisfacao' => '10_satisfacao'
        ];

        return $names[$section] ?? $section;
    }

    /**
     * Remove arquivos temporários
     */
    public function cleanup() {
        if (file_exists($this->zipFile)) {
            unlink($this->zipFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function __destruct() {
        // Cleanup não é chamado aqui pois o arquivo precisa ser enviado primeiro
    }
}
