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
        try {
            // Transaction starten für Atomarität
            $this->pdo->beginTransaction();
            
            // 1. Alle Referenzen in abhängigen Tabellen löschen
            $deleteQueries = [
                "DELETE FROM kursanmeldungen WHERE mitglied_id = :id",
                "DELETE FROM mitglied_infos WHERE mitglied_id = :id", 
                "DELETE FROM trainingseinheiten WHERE mitglied_id = :id"
            ];
            
            foreach ($deleteQueries as $sql) {
                $query = $this->pdo->prepare($sql);
                $query->bindParam(':id', $mitgliederId, PDO::PARAM_INT);
                $query->execute();
            }
            
            // 2. Haupteintrag in mitglieder Tabelle löschen
            $sql = "DELETE FROM mitglieder WHERE mitglied_id = :id";
            $query = $this->pdo->prepare($sql);
            $query->bindParam(':id', $mitgliederId, PDO::PARAM_INT);
            $result = $query->execute();
            
            // Transaction committen
            $this->pdo->commit();
            
            return $result;
            
        } catch (Exception $e) {
            // Bei Fehler: Rollback
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function update(int $mitgliederId, array $formularDaten): bool
    {
        $sql = "UPDATE mitglieder
                SET vorname = :vorname,
                    nachname = :nachname,
                    email = :email,
                    geburtsdatum = :geburtsdatum,
                    mitgliedschaft_typ = :mitgliedschaft_typ
                WHERE mitglied_id = :id";
        
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':vorname', $formularDaten['vorname']);
        $query->bindParam(':nachname', $formularDaten['nachname']);
        $query->bindParam(':email', $formularDaten['email']);
        $query->bindParam(':geburtsdatum', $formularDaten['geburtsdatum']);
        $query->bindParam(':mitgliedschaft_typ', $formularDaten['mitgliedschaft_typ']);
        $query->bindParam(':id', $mitgliederId, PDO::PARAM_INT);

        return $query->execute();
    }

   /**
 * Erstellt einen neuen Eintrag in mitglied_infos
 * 
 * @param array $data Die Daten für mitglied_infos
 * @return int Die ID des neu erstellten Eintrags
 */
public function createInfo(array $data): int
{
    $sql = "INSERT INTO mitglied_infos (
                mitglied_id, 
                gewicht, 
                groesse, 
                geschlecht, 
                max_kraft, 
                weitere_informationen
            ) VALUES (
                :mitglied_id, 
                :gewicht, 
                :groesse, 
                :geschlecht, 
                :max_kraft, 
                :weitere_informationen
            )";
    
    $query = $this->pdo->prepare($sql);
    
    $query->bindParam(':mitglied_id', $data['mitglied_id'], PDO::PARAM_INT);
    $query->bindParam(':gewicht', $data['gewicht']);
    $query->bindParam(':groesse', $data['groesse']);
    $query->bindParam(':geschlecht', $data['geschlecht']);
    $query->bindParam(':max_kraft', $data['max_kraft']);
    $query->bindParam(':weitere_informationen', $data['weitere_informationen']);
    
    $query->execute();
    
    return (int)$this->pdo->lastInsertId();
}

/**
 * Findet mitglied_infos anhand der mitglied_id
 * 
 * @param int $mitgliedId Die ID des Mitglieds
 * @return array|null Die gefundenen Infos oder null
 */
public function findInfoByMitgliedId(int $mitgliedId): ?array
{
    $sql = "SELECT * FROM mitglied_infos WHERE mitglied_id = :mitglied_id";
    $query = $this->pdo->prepare($sql);
    $query->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
    $query->execute();
    
    $result = $query->fetch();
    return $result ?: null;
}

public function findByIdWithInfo(int $mitgliederId): ?array
{
    $sql = "SELECT m.*, mi.gewicht, mi.groesse, mi.geschlecht, mi.max_kraft, mi.weitere_informationen
            FROM mitglieder m
            LEFT JOIN mitglied_infos mi ON m.mitglied_id = mi.mitglied_id
            WHERE m.mitglied_id = :id";
    
    $query = $this->pdo->prepare($sql);
    $query->bindParam(':id', $mitgliederId, PDO::PARAM_INT);
    $query->execute();
    
    $result = $query->fetch();
    return $result ?: null;
}

public function updateInfo(int $mitgliedId, array $data): bool
{
    $sql = "UPDATE mitglied_infos 
            SET gewicht = :gewicht,
                groesse = :groesse,
                geschlecht = :geschlecht,
                max_kraft = :max_kraft,
                weitere_informationen = :weitere_informationen
            WHERE mitglied_id = :mitglied_id";
    
    $query = $this->pdo->prepare($sql);
    
    $query->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
    $query->bindParam(':gewicht', $data['gewicht']);
    $query->bindParam(':groesse', $data['groesse']);
    $query->bindParam(':geschlecht', $data['geschlecht']);
    $query->bindParam(':max_kraft', $data['max_kraft']);
    $query->bindParam(':weitere_informationen', $data['weitere_informationen']);
    
    return $query->execute();
}
    
}
