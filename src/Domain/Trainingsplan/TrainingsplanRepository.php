<?php

namespace App\Domain\Trainingsplan;

use PDO;

class TrainingsplanRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Findet einen Trainingsplan anhand seiner ID
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM trainingsplaene WHERE trainingsplan_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $trainingsplan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $trainingsplan ?: null;
    }

    /**
     * Findet alle Trainingspläne mit Mitgliedsinformationen
     */
    public function findAllWithMitglieder(): array
    {
        $sql = "SELECT t.*, m.vorname, m.nachname 
                FROM trainingsplaene t
                JOIN mitglieder m ON t.mitglied_id = m.mitglied_id
                ORDER BY t.erstellt_am DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Findet alle Trainingspläne eines bestimmten Mitglieds
     */
    public function findAllByMitgliedId(int $mitgliedId): array
    {
        $sql = "SELECT * FROM trainingsplaene 
                WHERE mitglied_id = :mitglied_id 
                ORDER BY erstellt_am DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Erstellt einen neuen Trainingsplan
     */
    public function createTrainingsplan(array $data): int
    {
        $sql = "INSERT INTO trainingsplaene 
                (mitglied_id, plan_name, schwierigkeitsgrad, trainingsart, notizen, ist_aktiv) 
                VALUES (:mitglied_id, :plan_name, :schwierigkeitsgrad, :trainingsart, :notizen, :ist_aktiv)";
        
        // Wenn ein neuer aktiver Plan erstellt wird, alle anderen Pläne des Mitglieds deaktivieren
        if ($data['ist_aktiv']) {
            $this->deactivateAllPlansForMember($data['mitglied_id']);
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':mitglied_id', $data['mitglied_id'], PDO::PARAM_INT);
        $stmt->bindParam(':plan_name', $data['plan_name'], PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $data['schwierigkeitsgrad'], PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $data['trainingsart'], PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $data['notizen'], PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $data['ist_aktiv'], PDO::PARAM_BOOL);
        $stmt->execute();
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Aktualisiert einen bestehenden Trainingsplan
     */
    public function updateTrainingsplan(int $id, array $data): bool
    {
        // Wenn der Plan aktiviert werden soll, alle anderen Pläne des Mitglieds deaktivieren
        if (isset($data['ist_aktiv']) && $data['ist_aktiv']) {
            $trainingsplan = $this->findById($id);
            if ($trainingsplan) {
                $this->deactivateAllPlansForMember($trainingsplan['mitglied_id'], $id);
            }
        }
        
        $sql = "UPDATE trainingsplaene SET 
                plan_name = :plan_name, 
                schwierigkeitsgrad = :schwierigkeitsgrad, 
                trainingsart = :trainingsart, 
                notizen = :notizen, 
                ist_aktiv = :ist_aktiv 
                WHERE trainingsplan_id = :id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':plan_name', $data['plan_name'], PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $data['schwierigkeitsgrad'], PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $data['trainingsart'], PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $data['notizen'], PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $data['ist_aktiv'], PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    /**
     * Löscht einen Trainingsplan und alle zugehörigen Daten
     */
    public function deleteTrainingsplan(int $id): bool
    {
        $sql = "DELETE FROM trainingsplaene WHERE trainingsplan_id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Deaktiviert alle Trainingspläne eines Mitglieds (außer dem angegebenen)
     */
    private function deactivateAllPlansForMember(int $mitgliedId, ?int $exceptPlanId = null): void
    {
        $sql = "UPDATE trainingsplaene SET ist_aktiv = 0 
                WHERE mitglied_id = :mitglied_id";
        
        if ($exceptPlanId !== null) {
            $sql .= " AND trainingsplan_id != :except_id";
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
        
        if ($exceptPlanId !== null) {
            $stmt->bindParam(':except_id', $exceptPlanId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
    }

    /**
     * Findet alle Übungen eines Trainingsplans
     */
    public function findUebungenByPlanId(int $planId): array
    {
        $sql = "SELECT tu.*, u.name, u.beschreibung, u.schwierigkeitsgrad, u.trainingsart, 
                u.muskelgruppe, u.equipment, u.anleitung, u.sicherheitshinweise, u.video_url
                FROM trainingsplan_uebungen tu
                JOIN uebungen u ON tu.uebung_id = u.uebung_id
                WHERE tu.trainingsplan_id = :plan_id
                ORDER BY tu.trainingstag, tu.reihenfolge";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Findet alle Details einer Trainingsplan-Übung
     */
    public function findUebungDetailsByUebungId(int $trainingsplanUebungId): array
    {
        $sql = "SELECT * FROM trainingsplan_uebung_details
                WHERE trainingsplan_uebung_id = :uebung_id
                ORDER BY satz_nummer";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fügt eine Übung zu einem Trainingsplan hinzu
     */
    public function addUebungToTrainingsplan(array $data): int
    {
        $sql = "INSERT INTO trainingsplan_uebungen 
                (trainingsplan_id, uebung_id, reihenfolge, trainingstag) 
                VALUES (:trainingsplan_id, :uebung_id, :reihenfolge, :trainingstag)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_id', $data['trainingsplan_id'], PDO::PARAM_INT);
        $stmt->bindParam(':uebung_id', $data['uebung_id'], PDO::PARAM_INT);
        $stmt->bindParam(':reihenfolge', $data['reihenfolge'], PDO::PARAM_INT);
        $stmt->bindParam(':trainingstag', $data['trainingstag'], PDO::PARAM_STR);
        $stmt->execute();
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Fügt Details zu einer Trainingsplan-Übung hinzu
     */
    public function addUebungDetails(array $data): int
    {
        $sql = "INSERT INTO trainingsplan_uebung_details 
                (trainingsplan_uebung_id, satz_nummer, gewicht_kg, gewicht_prozent, wiederholungen, pause_sekunden) 
                VALUES (:trainingsplan_uebung_id, :satz_nummer, :gewicht_kg, :gewicht_prozent, :wiederholungen, :pause_sekunden)";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $data['trainingsplan_uebung_id'], PDO::PARAM_INT);
        $stmt->bindParam(':satz_nummer', $data['satz_nummer'], PDO::PARAM_INT);
        $stmt->bindParam(':gewicht_kg', $data['gewicht_kg'], PDO::PARAM_STR);
        $stmt->bindParam(':gewicht_prozent', $data['gewicht_prozent'], PDO::PARAM_INT);
        $stmt->bindParam(':wiederholungen', $data['wiederholungen'], PDO::PARAM_INT);
        $stmt->bindParam(':pause_sekunden', $data['pause_sekunden'], PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Aktualisiert die Details einer Trainingsplan-Übung
     */
    public function updateUebungDetail(int $detailId, array $data): bool
    {
        $sql = "UPDATE trainingsplan_uebung_details SET 
                gewicht_kg = :gewicht_kg, 
                gewicht_prozent = :gewicht_prozent, 
                wiederholungen = :wiederholungen, 
                pause_sekunden = :pause_sekunden, 
                notizen = :notizen 
                WHERE detail_id = :detail_id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':detail_id', $detailId, PDO::PARAM_INT);
        $stmt->bindParam(':gewicht_kg', $data['gewicht_kg'], PDO::PARAM_STR);
        $stmt->bindParam(':gewicht_prozent', $data['gewicht_prozent'], PDO::PARAM_INT);
        $stmt->bindParam(':wiederholungen', $data['wiederholungen'], PDO::PARAM_INT);
        $stmt->bindParam(':pause_sekunden', $data['pause_sekunden'], PDO::PARAM_INT);
        $stmt->bindParam(':notizen', $data['notizen'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Ersetzt eine Übung in einem Trainingsplan durch eine andere
     */
    public function replaceUebung(int $trainingsplanUebungId, int $neueUebungId): bool
    {
        $sql = "UPDATE trainingsplan_uebungen SET 
                uebung_id = :neue_uebung_id 
                WHERE trainingsplan_uebung_id = :trainingsplan_uebung_id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        $stmt->bindParam(':neue_uebung_id', $neueUebungId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Aktualisiert die Reihenfolge einer Übung im Trainingsplan
     */
    public function updateUebungReihenfolge(int $trainingsplanUebungId, int $reihenfolge): bool
    {
        $sql = "UPDATE trainingsplan_uebungen SET 
                reihenfolge = :reihenfolge 
                WHERE trainingsplan_uebung_id = :trainingsplan_uebung_id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        $stmt->bindParam(':reihenfolge', $reihenfolge, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Aktualisiert den Trainingstag einer Übung im Trainingsplan
     */
    public function updateUebungTrainingstag(int $trainingsplanUebungId, string $trainingstag): bool
    {
        $sql = "UPDATE trainingsplan_uebungen SET 
                trainingstag = :trainingstag 
                WHERE trainingsplan_uebung_id = :trainingsplan_uebung_id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        $stmt->bindParam(':trainingstag', $trainingstag, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Entfernt eine Übung aus einem Trainingsplan
     */
    public function removeUebungFromTrainingsplan(int $trainingsplanUebungId): bool
    {
        $sql = "DELETE FROM trainingsplan_uebungen 
                WHERE trainingsplan_uebung_id = :trainingsplan_uebung_id";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Findet die höchste Reihenfolge für einen bestimmten Tag in einem Trainingsplan
     */
    public function findMaxReihenfolgeForTag(int $trainingsplanId, string $trainingstag): int
    {
        $sql = "SELECT MAX(reihenfolge) as max_reihenfolge 
                FROM trainingsplan_uebungen 
                WHERE trainingsplan_id = :trainingsplan_id 
                AND trainingstag = :trainingstag";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_id', $trainingsplanId, PDO::PARAM_INT);
        $stmt->bindParam(':trainingstag', $trainingstag, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['max_reihenfolge'] ?? 0;
    }
}