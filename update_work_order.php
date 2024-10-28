<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $work_order_id = $_POST['work_order_id'];
    $status = $_POST['status'] ?? null;
    $assign_user = $_POST['assign_user'] ?? null;

    // Verifica se a ordem de trabalho foi aceita
    if (isset($_POST['accept'])) {
        $user_id = $_SESSION['user_id'];
        $accept_at = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE work_orders SET status = 'Aceite', accept_by = ?, accept_at = ? WHERE id = ?");
        $stmt->bind_param('isi', $user_id, $accept_at, $work_order_id);
        $stmt->execute();
        $stmt->close();
    }

    // Verifica se a ordem de trabalho foi fechada
    if (isset($_POST['close'])) {
        $current_status_stmt = $conn->prepare("SELECT status FROM work_orders WHERE id = ?");
        $current_status_stmt->bind_param('i', $work_order_id);
        $current_status_stmt->execute();
        $current_status_stmt->bind_result($current_status);
        $current_status_stmt->fetch();
        $current_status_stmt->close();

        if ($current_status === 'Fechada') {
            // Reabrir OT
            $stmt = $conn->prepare("UPDATE work_orders SET status = 'Em Andamento', closed_at = NULL WHERE id = ?");
            $stmt->bind_param('i', $work_order_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Fechar OT
            $closed_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE work_orders SET status = 'Fechada', closed_at = ? WHERE id = ?");
            $stmt->bind_param('si', $closed_at, $work_order_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Verifica se o status foi alterado
    if (!empty($status)) {
        $stmt = $conn->prepare("UPDATE work_orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $work_order_id);
        $stmt->execute();
        $stmt->close();
    }

    // Verifica se a ordem de trabalho foi atribuída a outro usuário
    if (!empty($assign_user)) {
        $stmt = $conn->prepare("UPDATE work_orders SET assigned_user = ? WHERE id = ?");
        $stmt->bind_param('ii', $assign_user, $work_order_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: view_work_order.php?id=$work_order_id");
    exit;
}
?>
