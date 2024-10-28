<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';
$user_id = $_SESSION['user_id'];

$message = "";

// Buscar as categorias do banco de dados
$categories = [];
$stmt = $conn->prepare("SELECT id, name FROM categories");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura os dados do formulário
    $name = $_POST['name'];
    $description = $_POST['description'];
    $features = $_POST['features']; // Novo campo: características
    $category_id = $_POST['category_id']; // Novo campo: categoria
    $photo = $_FILES['photo']['name'];
    $manual = $_FILES['manual']['name'];

    // Define o caminho para salvar os arquivos
    $target_dir = "uploads/";
    $photo_target = basename($photo);
    $manual_target = basename($manual);

    // Move os arquivos para o diretório de uploads, se fornecidos
    if (!empty($photo)) {
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_target);
    }
    if (!empty($manual)) {
        move_uploaded_file($_FILES['manual']['tmp_name'], $manual_target);
    }

    // Insere os dados na tabela assets
    $stmt = $conn->prepare("INSERT INTO assets (name, description, features, category_id, photo, manual) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $name, $description, $features, $category_id, $photo_target, $manual_target);
    if ($stmt->execute()) {
        $asset_id = $stmt->insert_id; // Recupera o ID do ativo inserido

        // Gerar o QR code
        include 'phpqrcode/qrlib.php'; // Inclua a biblioteca do QR Code
        $url = "http://serverlab/cmms/view_asset.php?id=" . $asset_id;
        $qrcode_file = $target_dir . "qrcode_" . $asset_id . ".png";
        QRcode::png($url, $qrcode_file); // Gera o QR Code

        // Atualiza a tabela de ativos com o caminho do QR Code
        $stmt = $conn->prepare("UPDATE assets SET qrcode = ? WHERE id = ?");
        $stmt->bind_param("si", $qrcode_file, $asset_id);
        $stmt->execute();

        $message = "Ativo criado com sucesso!";
    } else {
        $message = "Erro ao criar ativo: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Ativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2>Criar Ativo</h2>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Nome do Ativo</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Categoria</label> <!-- Novo campo: Categoria -->
            <select class="form-control" id="category_id" name="category_id" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['id']); ?>"><?= htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Descrição</label>
            <textarea class="form-control" id="description" name="description" required></textarea>
        </div>
        <div class="mb-3">
            <label for="features" class="form-label">Características</label> <!-- Novo campo: Características -->
            <textarea class="form-control" id="features" name="features"></textarea>
        </div>        
        <div class="mb-3">
            <label for="photo" class="form-label">Foto</label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
        </div>
        <div class="mb-3">
            <label for="manual" class="form-label">Manual (opcional)</label>
            <input type="file" class="form-control" id="manual" name="manual" accept=".pdf,.doc,.docx">
        </div>
        <div class="d-flex">
            <a href="redirect_page.php" class="btn btn-secondary me-2">Voltar</a> <!-- Botão de Voltar -->
            <button type="submit" class="btn btn-primary">Criar Ativo</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>