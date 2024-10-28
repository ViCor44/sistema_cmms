<?php 
session_start();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'db.php'; // Conexão ao banco de dados

// Obtém o ID do utilizador a ser editado a partir do parâmetro da URL
if (!isset($_GET['id'])) {
    die("ID de utilizador não fornecido.");
}

$user_id = $_GET['id'];

// Obtém os dados do utilizador para preencher o formulário
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone, user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($first_name_e, $last_name_e, $email_e, $phone_e, $user_type_e);
$stmt->fetch();
$stmt->close();

// Verifica se o usuário foi encontrado
if (!$first_name_e) {
    die("Usuário não encontrado.");
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $user_type = $_POST['user_type'];

    // Evita que o admin atual mude sua própria função (role)
    if ($user_id == $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
    } else {
        // Atualiza todos os campos, incluindo a função para outros utilizadores
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, user_type = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $user_type, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "Erro ao atualizar utilizador: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h1>Editar Utilizador</h1>
    <form method="post" action="edit_user.php?id=<?= htmlspecialchars($user_id); ?>">
        <div class="mb-3">
            <label for="first_name" class="form-label">Primeiro Nome</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name_e); ?>" required>
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Último Nome</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name_e); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email_e); ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Telefone</label>
            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone_e); ?>" required>
        </div>
        <!-- O campo de função só aparece se o admin não estiver a editar o seu próprio perfil -->
        <?php if ($user_id != $_SESSION['user_id']): ?>
        <div class="mb-3">
            <label for="user_type" class="form-label">Tipo</label>
            <select class="form-select" id="user_type" name="user_type" required>
                <option value="user" <?= $user_type_e == 'user' ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?= $user_type_e == 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            Não pode alterar o seu próprio tipo!
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="manage_users.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
