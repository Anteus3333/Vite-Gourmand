<?php
// src/Services/SqlDatabase.php — connexion PDO MySQL

class SqlDatabase {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    // Commentaire de type PHPDoc pour indiquer que 
    // la propriété $conn peut être null
    // @var indique type de variable
    // PDO indique que c'est un objet PDO (classique pour intérargir avec BB)
    // |null indique que la variable peut être null
    /** @var PDO|null */
    // $conn est une propriété de la classe SqlDatabase
    // qui est un objet PDO ou null
    // C'est une déclaration classique dans ce type de classe 
    // pour une connexion PDO vers BDD
    public $conn;

    public function __construct() {
        $config = require __DIR__ . '/../../config/database.php';

        $this->host     = $config['host'];
        $this->db_name  = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    public function getConnection(): ?PDO {
        $this->conn = null;

        try {
            // charset=utf8mb4 est un paramètre de la connexion PDO
            // pour indiquer que la connexion doit utiliser l'encodage UTF-8
            // pour les caractères Unicode
            // utf8mb4 est un encodage Unicode qui supporte plus de caractères que utf8
            // https://www.php.net/manual/fr/pdo.connections.php
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                $this->username,
                $this->password
            );

            // setAttribute est une méthode de l'objet PDO
            // pour définir des attributs de la connexion
            // ATTR_ERRMODE est un attribut de la connexion PDO
            // La valeur est ERRMODE_EXCEPTION qui est 
            // une constante de la classe PDO
            // qui indique que la connexion doit lancer une exception en cas d'erreur
            // https://www.php.net/manual/fr/pdo.setattribute.php
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // ATTR_DEFAULT_FETCH_MODE est un attribut de la connexion PDO
            // La valeur est FETCH_ASSOC qui est 
            // une constante de la classe PDO
            // qui indique que les résultats de la requête doivent 
            // être retournés sous forme de tableau associatif
            // plutôt que de récupérer les résultats sous forme de 
            // tableau indexé
            // un tableau indexé est un tableau où les éléments sont accessibles 
            // par leur indice numérique
            // un tableau associatif est un tableau où les éléments sont accessibles 
            // par leur clé
            // L'indice numérique est un entier qui commence à 0
            // La clé est une chaîne de caractères qui est unique
            // https://www.php.net/manual/fr/language.types.array.php
            // https://www.php.net/manual/fr/pdofetch.php
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // return $this->conn; est une instruction qui retourne l'objet PDO
            // qui est la connexion à la base de données
            // C'est une instruction classique dans ce type de classe pour retourner un objet PDO
        } 
        catch (PDOException $exception) {
            echo 'Erreur de connexion à la base de données : ' . $exception->getMessage();
        }

        return $this->conn;
    }
}
