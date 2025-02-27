<?php

namespace App\Services;

use OCI8\Connection;  // Si tu utilises Oracle

class DatabaseConnection
{
    protected $connection;

    // Constructeur pour initialiser la connexion
    public function __construct()
    {
        $this->connection = $this->connectToDatabase();
    }

    // Fonction pour établir la connexion avec la base de données
    public function connectToDatabase()
    {
        $connectionString = '192.168.2.241/propme'; // Exemple d'hôte, à adapter
        $username = 'DBCDR';     // Remplace par ton nom d'utilisateur
        $password = 'cdrpro2024';     // Remplace par ton mot de passe

        // Connexion à Oracle (tu peux l'adapter à une autre base de données)
        $connection = oci_connect($username, $password, $connectionString);

        if (!$connection) {
            $e = oci_error();
            throw new \Exception("Connection failed: " . $e['message']);
        }

        return $connection;
    }

    // Fonction pour obtenir la connexion
    public function getConnection()
    {
        return $this->connection;
    }
}
