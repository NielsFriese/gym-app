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

   public function create(array $formularDaten): int
   {
       $sql = "INSERT INTO mitglieder (vorname, nachname, email, geburtsdatum, beitrittsdatum, mitgliedschaft_typ)
           VALUES (:vorname, :nachname, :email, :geburtsdatum, :beitrittsdatum, :mitgliedschaft_typ)";
       $query = $this->pdo->prepare($sql);
       $query->bindParam(':vorname', $formularDaten['vorname']);
       $query->bindParam(':nachname', $formularDaten['nachname']);
       $query->bindParam(':email', $formularDaten['email']);
       $query->bindParam(':geburtsdatum', $formularDaten['geburtsdatum']);
       $query->bindParam(':beitrittsdatum', $formularDaten['beitrittsdatum']);
       $query->bindParam(':mitgliedschaft_typ', $formularDaten['mitgliedschaft_typ']);

       $query->execute();

       return (int)$this->pdo->lastInsertId();
   }

    public function delete(int $mitgliederId): bool
    {
        $sql = "DELETE FROM mitglieder WHERE mitglied_id = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $id, PDO::PARAM_INT);

        return $query->execute();
    }
}