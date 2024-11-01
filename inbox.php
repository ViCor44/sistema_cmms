<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Recupera o nome do utilizador logado
include 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na consulta: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Parâmetros de pesquisa
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Buscar mensagens recebidas pelo usuário logado
$received_query = "
    SELECT messages.id, users.username AS sender, messages.message_text, messages.timestamp, messages.is_read 
    FROM messages 
    JOIN users ON messages.sender_id = users.id 
    WHERE receiver_id = ?
";
$received_params = [$user_id];
$received_types = 'i';

if (!empty($search)) {
    $received_query .= " AND (users.username LIKE ? OR messages.message_text LIKE ?)";
    $received_params[] = '%' . $search . '%';
    $received_params[] = '%' . $search . '%';
    $received_types .= 'ss';
}

$received_query .= " ORDER BY messages.timestamp DESC";
$stmt = $conn->prepare($received_query);

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param($received_types, ...$received_params);
$stmt->execute();
$received_messages = $stmt->get_result();

// Buscar mensagens enviadas pelo usuário logado
$sent_query = "
    SELECT messages.id, users.username AS receiver, messages.message_text, messages.timestamp, messages.is_read 
    FROM messages 
    JOIN users ON messages.receiver_id = users.id 
    WHERE sender_id = ?
";
$sent_params = [$user_id];
$sent_types = 'i';

if (!empty($search)) {
    $sent_query .= " AND (users.username LIKE ? OR messages.message_text LIKE ?)";
    $sent_params[] = '%' . $search . '%';
    $sent_params[] = '%' . $search . '%';
    $sent_types .= 'ss';
}

$sent_query .= " ORDER BY messages.timestamp DESC";
$stmt = $conn->prepare($sent_query);

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param($sent_types, ...$sent_params);
$stmt->execute();
$sent_messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caixa de Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <h2>Caixa de Entrada</h2>

        <div class="d-flex mb-3">
            <a href="send_message.php" class="btn btn-primary me-2">Nova Mensagem</a>
            <a href="redirect_page.php" class="btn btn-secondary">Voltar</a>
        </div>

        <form method="GET" action="inbox.php" class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Pesquisar por usuário ou conteúdo" value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Pesquisar</button>
                    <a href="inbox.php" class="btn btn-secondary">Limpar</a> <!-- Botão de Limpar -->
                </div>
            </div>
        </form>

        <h3>Mensagens Recebidas</h3>
        <?php if ($received_messages->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($row = $received_messages->fetch_assoc()): ?>
                    <div class="list-group-item <?= $row['is_read'] ? 'list-group-item-light' : 'list-group-item-warning' ?>">
                        <h5 class="mb-1">De: <?= htmlspecialchars($row['sender']); ?></h5>
                        <p class="mb-1"><?= htmlspecialchars($row['message_text']); ?></p>
                        <small>Enviado em: <?= $row['timestamp']; ?></small>
                        <div class="mt-2">
                            <!-- Marcar como lida -->
                            <?php if (!$row['is_read']): ?>
                                <a href="mark_as_read.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-success">Marcar como lida</a>
                            <?php endif; ?>
                            <!-- Responder mensagem -->
                            <a href="send_message.php?reply_to=<?= $row['sender']; ?>" class="btn btn-sm btn-primary">Responder</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Você não tem mensagens recebidas.</p>
        <?php endif; ?>

        <h3 class="mt-5">Mensagens Enviadas</h3>
        <?php if ($sent_messages->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($row = $sent_messages->fetch_assoc()): ?>
                    <div class="list-group-item <?= !$row['is_read'] ? 'list-group-item-warning' : 'list-group-item-light' ?>">
                        <h5 class="mb-1">Para: <?= htmlspecialchars($row['receiver']); ?></h5>
                        <p class="mb-1"><?= htmlspecialchars($row['message_text']); ?></p>
                        <small>Enviado em: <?= $row['timestamp']; ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Você não tem mensagens enviadas.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
