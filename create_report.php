<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recuperar os dados do relatório
    $report_date = $_POST['report_date'];
    $report_details = $_POST['report_details'];
    $technician_id = $_SESSION['user_id'];

    // Verificar se já existe um relatório para o mesmo técnico e data
    $stmt = $conn->prepare("SELECT * FROM reports WHERE technician_id = ? AND report_date = ?");
    $stmt->bind_param("is", $technician_id, $report_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Já existe um relatório submetido para essa data. Por favor, escolha outra.";
    } else {
        // Inserir os dados do relatório no banco de dados
        $stmt = $conn->prepare("INSERT INTO reports (technician_id, report_date, execution_date, report_details) 
                                VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iss", $technician_id, $report_date, $report_details);
        if ($stmt->execute()) {
            $report_id = $stmt->insert_id;
        } else {
            echo "Erro ao criar o relatório.";
            exit;
        }
        $stmt->close();

        // Verificar se as fotos foram enviadas
        if (!empty($_FILES['photos']['name'][0])) {
            // Iterar sobre as fotos enviadas
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $file_name = $_FILES['photos']['name'][$key];
                    $file_tmp = $_FILES['photos']['tmp_name'][$key];

                    // Definir o caminho para salvar as fotos
                    $photo_path = 'uploads/' . basename($file_name);
                    
                    // Mover o arquivo para o diretório de uploads
                    if (move_uploaded_file($file_tmp, $photo_path)) {
                        // Inserir o caminho da foto no banco de dados
                        $stmt = $conn->prepare("INSERT INTO report_photos (report_id, photo_path) VALUES (?, ?)");
                        $stmt->bind_param("is", $report_id, $photo_path);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        echo "Erro ao fazer upload da foto.";
                    }
                }
            }
        }

        // Redirecionar para a página de lista de relatórios
        header("Location: list_reports.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Relatório</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 50px;
        }
        .card {
            margin-top: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            background-color: #f9f9f9;
        }
        .alert {
            margin-top: 20px;
        }
        .date-input {
            width: 130px !important;
        }
        .form-control, .form-select {
            border-radius: 10px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .form-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container">
    <h3 class="text-start">Criar Relatório</h3>
    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="create_report.php" method="post" enctype="multipart/form-data">
                <!-- Campo de data do relatório -->
                <div class="mb-3">
                    <label for="report_date" class="form-label">Data do Relatório:</label>
                    <input type="date" class="form-control date-input" id="report_date" name="report_date" required value="<?= isset($report_date) ? htmlspecialchars($report_date) : ''; ?>">
                </div>
                <!-- Campo para o conteúdo do relatório -->
                <div class="mb-3">
                    <label for="report_details" class="form-label">Detalhes:</label>
                    <textarea class="form-control" id="report_details" name="report_details" rows="4" placeholder="Descreva os detalhes aqui..." required><?= isset($report_details) ? htmlspecialchars($report_details) : ''; ?></textarea>
                </div>
                <!-- Campo de upload de fotos -->
                <div class="mb-3">
                    <label for="photos" class="form-label">Anexar fotos:</label>
                    <input type="file" name="photos[]" id="photos" class="form-control" multiple>
                </div>
                <button type="submit" class="btn btn-primary">Submeter Relatório</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
