<?php
$servername = "localhost"; // ou o endereço do seu servidor de banco de dados
$username = "root"; // substitua pelo seu usuário do MySQL
$password = ""; // substitua pela sua senha do MySQL
$dbname = "cmms"; // substitua pelo nome do seu banco de dados

// Criando a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
