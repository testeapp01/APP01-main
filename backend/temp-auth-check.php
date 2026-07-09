<?php
require 'vendor/autoload.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, role TEXT)');
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
$stmt->execute(['name' => 'Cliente Teste', 'email' => 'cliente@example.com', 'password' => 'secret123', 'role' => 'admin']);
$repo = new App\Repositories\UserRepository($pdo);
$u = $repo->findByEmail('cliente@example.com');
var_dump($u);
