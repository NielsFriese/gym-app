<?php

namespace App\Domain\Uebungen; // Geändert von App\Domain\Uebung

use PDO;

class UebungRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
    /**
     * Findet alle Übungen
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM uebungen WHERE ist_aktiv = 1 ORDER BY name";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Findet eine Übung anhand ihrer ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM uebungen WHERE uebung_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $uebung = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $uebung ?: null;
    }

    /**
     * Findet Übungen anhand von Trainingsart und Schwierigkeitsgrad
     */
    public function findByTrainingsartAndSchwierigkeit(string $trainingsart, string $schwierigkeitsgrad): array
    {
        $sql = "SELECT * FROM uebungen 
                WHERE trainingsart = :trainingsart 
                AND schwierigkeitsgrad = :schwierigkeitsgrad 
                AND ist_aktiv = 1
                ORDER BY muskelgruppe, name";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsart', $trainingsart, PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $schwierigkeitsgrad, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Findet Übungen anhand der Muskelgruppe
     */
    public function findByMuskelgruppe(string $muskelgruppe): array
    {
        $sql = "SELECT * FROM uebungen 
                WHERE muskelgruppe = :muskelgruppe 
                AND ist_aktiv = 1
                ORDER BY name";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':muskelgruppe', $muskelgruppe, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Erstellt eine neue Übung
     */
    public function createUebung(array $data): int
    {
        $sql = "INSERT INTO uebungen 
                (name, beschreibung, schwierigkeitsgrad, trainingsart, muskelgruppe, 
                equipment, anleitung, sicherheitshinweise, video_url, ist_aktiv) 
                VALUES 
                (:name, :beschreibung, :schwierigkeitsgrad, :trainingsart, :muskelgruppe, 
                :equipment, :anleitung, :sicherheitshinweise, :video_url, :ist_aktiv)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':beschreibung', $data['beschreibung'], PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $data['schwierigkeitsgrad'], PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $data['trainingsart'], PDO::PARAM_STR);
        $stmt->bindParam(':muskelgruppe', $data['muskelgruppe'], PDO::PARAM_STR);
        $stmt->bindParam(':equipment', $data['equipment'], PDO::PARAM_STR);
        $stmt->bindParam(':anleitung', $data['anleitung'], PDO::PARAM_STR);
        $stmt->bindParam(':sicherheitshinweise', $data['sicherheitshinweise'], PDO::PARAM_STR);
        $stmt->bindParam(':video_url', $data['video_url'], PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $data['ist_aktiv'], PDO::PARAM_BOOL);
        $stmt->execute();
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Aktualisiert eine bestehende Übung
     */
    public function updateUebung(int $id, array $data): bool
    {
        $sql = "UPDATE uebungen SET 
                name = :name, 
                beschreibung = :beschreibung, 
                schwierigkeitsgrad = :schwierigkeitsgrad, 
                trainingsart = :trainingsart, 
                muskelgruppe = :muskelgruppe, 
                equipment = :equipment, 
                anleitung = :anleitung, 
                sicherheitshinweise = :sicherheitshinweise, 
                video_url = :video_url, 
                ist_aktiv = :ist_aktiv 
                WHERE uebung_id = :id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':beschreibung', $data['beschreibung'], PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $data['schwierigkeitsgrad'], PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $data['trainingsart'], PDO::PARAM_STR);
        $stmt->bindParam(':muskelgruppe', $data['muskelgruppe'], PDO::PARAM_STR);
        $stmt->bindParam(':equipment', $data['equipment'], PDO::PARAM_STR);
        $stmt->bindParam(':anleitung', $data['anleitung'], PDO::PARAM_STR);
        $stmt->bindParam(':sicherheitshinweise', $data['sicherheitshinweise'], PDO::PARAM_STR);
        $stmt->bindParam(':video_url', $data['video_url'], PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $data['ist_aktiv'], PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    /**
     * Löscht eine Übung (setzt ist_aktiv auf false)
     */
    public function deleteUebung(int $id): bool
    {
        $sql = "UPDATE uebungen SET ist_aktiv = 0 WHERE uebung_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Findet alle verfügbaren Muskelgruppen
     */
    public function findAllMuskelgruppen(): array
    {
        $sql = "SELECT DISTINCT muskelgruppe FROM uebungen WHERE ist_aktiv = 1 ORDER BY muskelgruppe";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_column($result, 'muskelgruppe');
    }

    /**
     * Findet alle verfügbaren Equipment-Typen
     */
    public function findAllEquipment(): array
    {
        $sql = "SELECT DISTINCT equipment FROM uebungen WHERE ist_aktiv = 1 AND equipment IS NOT NULL ORDER BY equipment";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_column($result, 'equipment');
    }
}