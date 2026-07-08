<?php
// config/database.php

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    public $conn;

    public function __construct() {
        $config = [
            'host'     => 'localhost',
            'db_name'  => 'vite_gourmand',
            'username' => 'root',
            'password' => '',
        ];

        $local = __DIR__ . '/database.local.php';
        if (is_file($local)) {
            $override = require $local;
            if (is_array($override)) {
                $config = array_replace($config, $override);
            }
        }

        $this->host     = $config['host'];
        $this->db_name  = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            echo 'Erreur de connexion à la base de données : ' . $exception->getMessage();
        }

        return $this->conn;
    }
}
