<?php

namespace App\Domain\Mitglied;

use PDO;

class MitgliedRepository 
{
    private PDO $pdo; 

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM mitglieder ORDER BY vorname";
        $query = $this->pdo->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public function findById(int $mitgliederId): array
    {
        $sql = "SELECT * FROM mitglieder WHERE mitglied_id = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $mitgliederId, PDO::PARAM_INT);
        $query->execute();

        $mitglied = $query->fetch();

        return $mitglied;
    }

}