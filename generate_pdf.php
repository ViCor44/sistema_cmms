<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';
require 'fpdf/fpdf.php'; // Certifique-se de que o caminho para a biblioteca FPDF está correto

// Função para desenhar caixas arredondadas
class PDF extends FPDF {
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }
	
	// Método Footer para adicionar a assinatura no rodapé
    function Footer() {
        // Define a posição a 1.5cm do fundo
        $this->SetY(-12);
        $this->SetFont('DejaVu', '', 10);
        $this->Cell(0, 10, utf8_decode("Assinatura do Técnico: __________________________"), 0, 0, 'R');
    }
}

if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];

    // Recuperar informações do relatório
    $stmt = $conn->prepare("SELECT r.id, r.report_date, r.execution_date, r.report_details, u.username 
                            FROM reports r 
                            JOIN users u ON r.technician_id = u.id 
                            WHERE r.id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->bind_result($id, $report_date, $execution_date, $report_details, $technician_name);
    $stmt->fetch();
    $stmt->close();

    // Recuperar as fotos do relatório
    $photos = [];
    $stmt = $conn->prepare("SELECT photo_path FROM report_photos WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $photos[] = $row['photo_path'];
    }
    $stmt->close();
	
	

	// Formatar a data do relatório em português com dia da semana
	
    // Recuperar informações do relatório, incluindo a data de edição
    $stmt = $conn->prepare("SELECT r.id, r.report_date, r.execution_date, r.report_details, u.first_name, u.last_name, r.edit_date
    FROM reports r 
    JOIN users u ON r.technician_id = u.id 
    WHERE r.id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->bind_result($id, $report_date, $execution_date, $report_details, $first_name, $last_name, $edit_date);
    $stmt->fetch();
    $stmt->close();
	$technician_name = $first_name . " " . $last_name;    
	setlocale( LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
	$report_date = strftime('%A, %d-%m-%Y', strtotime($report_date));

    // Criação do PDF no formato A5 (210mm x 148mm) horizontal
    $pdf = new PDF('L', 'mm', 'A5');
    $pdf->AddPage();

    // Adiciona o logotipo
    $pdf->Image('images/logo.png', 10, 10, 30); // Ajuste o caminho do logotipo e o tamanho conforme necessário

    // Adiciona o título centralizado
    $pdf->AddFont('DejaVu','','DejaVuSans.php'); // Usar DejaVu para suporte UTF-8
    $pdf->SetFont('DejaVu', '', 20); // Define a fonte DejaVu
    $pdf->Cell(0, 10, utf8_decode('Relatório Diário da Manutenção'), 0, 1, 'R');
    $pdf->Ln(1);

    // Adiciona uma linha logo abaixo do título
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // Define a linha horizontal de X=10 a X=200 na posição Y atual
    $pdf->Ln(10); // Adiciona um espaço extra após a linha

    // Adicionar informações do técnico, data do relatório e data de execução numa única linha
    $pdf->SetFont('DejaVu', '', 10);

 	// Informações do relatório na mesma linha
	$pdf->SetXY(12, 27);	
	$pdf->Cell(25, 10, utf8_decode(htmlspecialchars("N°:")), 0, 0); // Label fora da caixa
	$pdf->RoundedRect(22, 27, 20, 10, 2, 'D'); // Caixa arredondada para o número do relatório
	$pdf->SetXY(25, 27);	
	$pdf->Cell(0, 10, utf8_decode($id), 0, 0);
	
	$pdf->SetXY(45, 27);	
	$pdf->Cell(20, 10, utf8_decode(htmlspecialchars("Técnico:")), 0, 0); // Label fora da caixa
	$pdf->RoundedRect(65, 27, 50, 10, 2, 'D'); // Caixa arredondada para o nome do técnico
	$pdf->SetXY(67, 27);	
	$pdf->Cell(0, 10, utf8_decode($technician_name), 0, 0);
	
	$pdf->SetXY(118, 27);	
	$pdf->Cell(35, 10, utf8_decode(htmlspecialchars("Data:")), 0, 0); // Label fora da caixa
	$pdf->RoundedRect(133, 27, 62, 10, 2, 'D'); // Caixa arredondada para a data do relatório
	$pdf->SetXY(138, 27);	
	$pdf->Cell(0, 10, utf8_decode($report_date), 0, 0);
	$pdf->Ln(2);

    // Adicionar a linha abaixo dessas informações
    $pdf->Ln(10); // Adiciona um pequeno espaço
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY()); // Linha horizontal de X=10 a X=200 na posição Y atual
    $pdf->Ln(10); // Adiciona um espaço extra após a linha

    // Detalhes do Relatório (com limite de 255 caracteres por página)
    $pdf->Ln(1);
    $pdf->SetXY(10, 43);   
    $pdf->Cell(40, 10, utf8_decode(htmlspecialchars("Detalhes:")), 0, 0); // Label fora da caixa
    $pdf->RoundedRect(15, 55, 180, 75, 5, 'D'); // Caixa arredondada para os detalhes do relatório
    
    
    // Limitar os detalhes a 750 caracteres por página
	 $pdf->SetFont('DejaVu', '', 12);
    $pdf->SetXY(18, 57);
    $details_text = utf8_decode(htmlspecialchars($report_details));
    $details_page_1 = substr($details_text, 0, 750);
    $pdf->MultiCell(170, 5, $details_page_1);

    // Se houver mais de 750 caracteres, adicionar outra página para o restante
    if (strlen($details_text) > 750) {
        $pdf->AddPage();
        $details_page_2 = substr($details_text, 750);
        $pdf->RoundedRect(15, 10, 180, 120, 5, 'D');
        $pdf->SetXY(18, 15);
        $pdf->MultiCell(170, 5, $details_page_2);
    }
/*
    // Adiciona as fotos ao PDF
    foreach ($photos as $photo) {
        if (file_exists($photo)) { // Verifica se a foto existe
            $pdf->Image($photo, 150, null, 40); // Ajusta o tamanho da imagem e a posição
            $pdf->Ln(5); // Espaço entre as imagens
        }
    }
*/

    // Adicionar nota sobre as fotos se houver
    if (count($photos) > 0) {
        $pdf->Ln(5); // Adiciona espaço antes da nota
        $pdf->SetFont('DejaVu', '', 10);
        $pdf->Cell(0, 10, utf8_decode("Ver fotos anexadas no formato digital"), 0, 1, 'C'); // Nota centralizada
    }

    // No PDF, após os detalhes do relatório, adicionamos uma nota se foi editado
    if ($edit_date) {
        $pdf->Ln(5);
        $pdf->SetFont('DejaVu', '', 10);
        $pdf->Cell(0, 10, utf8_decode("Relatório editado em: " . date('d/m/Y H:i', strtotime($edit_date))), 0, 1, 'C');
    }

    // Gera o PDF e exibe no navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="relatorio_' . $report_id . '.pdf"');
    $pdf->Output('I', 'relatorio_' . $report_id . '.pdf'); // Exibe o PDF no navegador

    // Atualizar o campo pdf_generated na base de dados
    if ($pdf_generated == 0) { // Se for a primeira vez que o PDF está sendo gerado
        $stmt = $conn->prepare("UPDATE reports SET pdf_generated = 1 WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        if ($stmt->execute()) {
            echo "O campo pdf_generated foi atualizado com sucesso.";
        } else {
            echo "Erro ao atualizar o campo pdf_generated: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    echo "ID do relatório não fornecido.";
}
?>
