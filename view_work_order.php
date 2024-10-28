<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Verifica se o ID da ordem de trabalho foi passado na URL
if (isset($_GET['id'])) {
    $work_order_id = $_GET['id'];

    // Prepara e executa a consulta para obter os detalhes da ordem de trabalho
    $stmt = $conn->prepare("
        SELECT w.id, a.name AS asset_name, w.description, w.status, w.priority, 
               u.first_name AS assigned_user, w.created_at, w.accept_by, w.accept_at, 
               w.closed_at, acceptor.first_name AS acceptor_name 
        FROM work_orders w 
        JOIN assets a ON w.asset_id = a.id 
        JOIN users u ON w.assigned_user = u.id
        LEFT JOIN users acceptor ON w.accept_by = acceptor.id
        WHERE w.id = ?
    ");
    
    $stmt->bind_param("i", $work_order_id);
    $stmt->execute();
    $stmt->bind_result($id, $asset_name, $description, $status, $priority, $assigned_user, $created_at, $accept_by, $accept_at, $closed_at, $acceptor_name);
    
    // Fetch the details
    if ($stmt->fetch()) {
        // Certifique-se de que $created_at e $closed_at estão definidos
        if (isset($created_at) && isset($closed_at)) {
            // Calcular o tempo decorrido entre aceitação e fecho
            $create_date = new DateTime($created_at);
            $close_date = new DateTime($closed_at);
            $interval = $create_date->diff($close_date);
    
            // Formatar o tempo decorrido
            $elapsed_time = $interval->format('%d dias, %h horas, %i minutos');
        } else {
            $elapsed_time = 'Dados de aceitação ou fechamento não disponíveis.';
        }
    } else {
        die("Ordem de trabalho não encontrada.");
    }
    $stmt->close();
} else {
    die("ID da ordem de trabalho não fornecido.");
}

// Consultar usuários para o dropdown
$users = [];
$user_stmt = $conn->prepare("SELECT id, first_name FROM users");
$user_stmt->execute();
$user_stmt->bind_result($user_id, $user_name);
while ($user_stmt->fetch()) {
    $users[] = ['id' => $user_id, 'name' => $user_name];
}
$user_stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Ordem de Trabalho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <a href="list_work_orders.php" class="btn btn-primary mt-3">Voltar à Lista de Ordens de Trabalho</a>
    <br>
    <h2 class="mb-4">Detalhes da Ordem de Trabalho</h2>
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">ID</h5>
                    <p class="card-text"><?= htmlspecialchars($id); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ativo</h5>
                    <p class="card-text"><?= htmlspecialchars($asset_name); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Prioridade</h5>
                    <p class="card-text"><?= htmlspecialchars($priority); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status</h5>
                    <p class="card-text"><?= htmlspecialchars($status); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Atribuído a</h5>
                    <p class="card-text"><?= htmlspecialchars($assigned_user); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data de Criação</h5>
                    <p class="card-text"><?= htmlspecialchars($created_at); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Aceite por</h5>
                    <p class="card-text"><?= !empty($acceptor_name) ? htmlspecialchars($acceptor_name) : 'N/A'; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data de Aceitação</h5>
                    <p class="card-text"><?= htmlspecialchars($accept_at); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Data de Fecho</h5>
                    <p class="card-text"><?= htmlspecialchars($closed_at); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tempo Decorrido</h5>
                    <p class="card-text"><?= htmlspecialchars($elapsed_time); ?></p>
                </div>
            </div>
        </div>
        <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Descrição</h5>
                    <p class="card-text"><?= htmlspecialchars($description); ?></p>
                </div>
            </div>
        </div>
    
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                <h5 class="card-title">Ações</h5>
                    <form method="POST" action="update_work_order.php">
                        <input type="hidden" name="work_order_id" value="<?= $id; ?>">

                        <div class="mb-3">
                            <label for="assign_user" class="form-label">Passar a:</label>
                            <select id="assign_user" name="assign_user" class="form-select">
                                <option value="">Selecionar utilizador</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Alterar status:</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Selecionar status</option>
                                <option value="Pendente">Pendente</option>
                                <option value="Aceite">Aceite</option>
                                <option value="Em Andamento">Em Andamento</option>
                                <option value="Fechada">Fechada</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary" <?= $status === 'Fechada' ? 'disabled' : ''; ?>>Salvar Ações</button>
                        </div>                
                        </div>
                    </div> 
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-body">              
                                <div class="mb-3">
                                    <button type="submit" name="accept" value="accept" class="btn btn-success" <?= $status === 'Fechada' ? 'disabled' : ''; ?>>Aceitar OT</button>
                                    <button type="submit" name="close" value="close" class="btn btn-danger"><?= $status === 'Fechada' ? 'Reabrir OT' : 'Fechar OT'; ?></button>
                                </div> 
                    </form>
                </div>
            </div>
        </div>
    </div>   
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
