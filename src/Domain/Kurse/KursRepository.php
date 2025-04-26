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
    
    public function create(array $formularDaten): int
    {
        $sql = "INSERT INTO kurse (kursname, beschreibung, max_kapazitaet, trainer_id)
            VALUES (:kursname, :beschreibung, :max_kapazitaet, :trainer_id)";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':kursname', $formularDaten['kursname']);
        $query->bindParam(':beschreibung', $formularDaten['beschreibung']);
        $query->bindParam(':max_kapazitaet', $formularDaten['max_kapazitaet']);
        $query->bindParam(':trainer_id', $formularDaten['trainer_id']);

        
 
        $query->execute();
 
        return (int)$this->pdo->lastInsertId();
    }
}