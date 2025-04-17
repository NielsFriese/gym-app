<?php

namespace App\Domain\Kurse;

use PDO;

class KursRepository 
{
    private PDO $pdo; 

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM kurse ORDER BY kursname";
        $query = $this->pdo->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public function findById(int $kursId): array
    {
        $sql = "SELECT * FROM kurse WHERE kurs_id = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $kursId, PDO::PARAM_INT);
        $query->execute();

        $kurs = $query->fetch();

        return $kurs;
    }

}