<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';
$user_id = $_SESSION['user_id'];

// Contar mensagens não lidas para o usuário logado
$stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
if (!$stmt) {
    die("Erro na consulta de mensagens: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();

// Contar ordens de trabalho atribuídas e ainda não aceitas
$stmt_ot = $conn->prepare("SELECT COUNT(*) FROM work_orders WHERE assigned_user = ? AND accept_at IS NULL");
if (!$stmt_ot) {
    die("Erro na consulta de ordens de trabalho: " . $conn->error);
}
$stmt_ot->bind_param("i", $user_id);
$stmt_ot->execute();
$stmt_ot->bind_result($unaccepted_ot_count);
$stmt_ot->fetch();
$stmt_ot->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema CMMS - Página Inicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin: 10px;
        }
        .badge {
            float: right; /* Para alinhar à direita */
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-4">
    <h1 class="text-center">Bem-vindo ao Sistema CMMS</h1>
    <div class="row">
    <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ativos</h5>
                    <p class="card-text">Registre novos ativos para o gerenciamento eficaz.</p>
                    <a href="list_assets.php" class="btn btn-primary">Listar Ativos</a>
                    <a href="create_asset.php" class="btn btn-secondary">Novo Ativo</a>
                </div>
            </div>
        </div> 
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Gerir Utilizadores</h5>
                    <p class="card-text">Administre os utilizadores do sistema.</p>
                    <a href="manage_users.php" class="btn btn-primary">Gerir</a>
                </div>
            </div>
        </div>
        <!-- Novo Card para Sistema de Mensagens -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sistema de Mensagens 
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?= $unread_count; ?></span>
                        <?php endif; ?>
                    </h5>
                    <p class="card-text">Ver e enviar mensagens para outros utilizadores.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="inbox.php" class="btn btn-primary me-md-2">Ver Mensagens</a>
                        <a href="send_message.php" class="btn btn-secondary">Nova Mensagem</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Novo Card para Sistema de Relatórios -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sistema de Relatórios</h5>
                    <p class="card-text">Ver e redigir relatórios.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="list_reports.php" class="btn btn-primary me-md-2">Listar Relatórios</a>
                        <a href="create_report.php" class="btn btn-secondary">Novo Relatório</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Novo Card para Sistema de Relatórios -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Estatísticas</h5>
                    <p class="card-text">Ver estatísticas várias.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="statistics.php" class="btn btn-primary me-md-2">Ver</a>                        
                    </div>
                </div>
            </div>
        </div>
        <!-- Novo Card para Ordens de Trabalho -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ordens de Trabalho 
                        <?php if ($unaccepted_ot_count > 0): ?>
                            <span class="badge bg-warning"><?= $unaccepted_ot_count; ?></span>
                        <?php endif; ?>
                    </h5>
                    <p class="card-text">Gerencie as ordens de trabalho dos ativos.</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="list_work_orders.php" class="btn btn-primary me-md-2">Ver Ordens</a>
                        <a href="create_work_order.php" class="btn btn-secondary">Nova Ordem</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
