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
        (mitglied_id, plan_name, schwierigkeitsgrad, trainingsart, split_typ, trainingsdauer, 
        trainingsumgebung, trainingsfrequenz, spezifisches_ziel, koerperliche_einschraenkungen, 
        erholungszeit, periodisierung, spezielle_techniken, aufwaermphase, progression, 
        kardio_integration, ernaehrungshinweise, notizen, ist_aktiv) 
        VALUES 
        (:mitglied_id, :plan_name, :schwierigkeitsgrad, :trainingsart, :split_typ, :trainingsdauer, 
        :trainingsumgebung, :trainingsfrequenz, :spezifisches_ziel, :koerperliche_einschraenkungen, 
        :erholungszeit, :periodisierung, :spezielle_techniken, :aufwaermphase, :progression, 
        :kardio_integration, :ernaehrungshinweise, :notizen, :ist_aktiv)";
        
        // Wenn ein neuer aktiver Plan erstellt wird, alle anderen Pläne des Mitglieds deaktivieren
        if ($data['ist_aktiv']) {
            $this->deactivateAllPlansForMember($data['mitglied_id']);
        }
        
        // Variablen für bindParam erstellen
        $mitgliedId = $data['mitglied_id'];
        $planName = $data['plan_name'];
        $schwierigkeitsgrad = $data['schwierigkeitsgrad'];
        $trainingsart = $data['trainingsart'];
        $splitTyp = $data['split_typ'] ?? 'Ganzkörper';
        $trainingsdauer = $data['trainingsdauer'] ?? 60;
        $trainingsumgebung = $data['trainingsumgebung'] ?? 'Fitnessstudio';
        $trainingsfrequenz = $data['trainingsfrequenz'] ?? 3;
        $spezifischesZiel = $data['spezifisches_ziel'] ?? null;
        $koerperlicheEinschraenkungen = $data['koerperliche_einschraenkungen'] ?? null;
        $erholungszeit = $data['erholungszeit'] ?? 'Mittel';
        $periodisierung = $data['periodisierung'] ?? false;
        $spezielleTechniken = $data['spezielle_techniken'] ?? false;
        $aufwaermphase = $data['aufwaermphase'] ?? true;
        $progression = $data['progression'] ?? true;
        $kardioIntegration = $data['kardio_integration'] ?? false;
        $ernaehrungshinweise = $data['ernaehrungshinweise'] ?? null;
        $notizen = $data['notizen'] ?? null;
        $istAktiv = $data['ist_aktiv'];
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
        $stmt->bindParam(':plan_name', $planName, PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $schwierigkeitsgrad, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $trainingsart, PDO::PARAM_STR);
        $stmt->bindParam(':split_typ', $splitTyp, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsdauer', $trainingsdauer, PDO::PARAM_INT);
        $stmt->bindParam(':trainingsumgebung', $trainingsumgebung, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsfrequenz', $trainingsfrequenz, PDO::PARAM_INT);
        $stmt->bindParam(':spezifisches_ziel', $spezifischesZiel, PDO::PARAM_STR);
        $stmt->bindParam(':koerperliche_einschraenkungen', $koerperlicheEinschraenkungen, PDO::PARAM_STR);
        $stmt->bindParam(':erholungszeit', $erholungszeit, PDO::PARAM_STR);
        $stmt->bindParam(':periodisierung', $periodisierung, PDO::PARAM_BOOL);
        $stmt->bindParam(':spezielle_techniken', $spezielleTechniken, PDO::PARAM_BOOL);
        $stmt->bindParam(':aufwaermphase', $aufwaermphase, PDO::PARAM_BOOL);
        $stmt->bindParam(':progression', $progression, PDO::PARAM_BOOL);
        $stmt->bindParam(':kardio_integration', $kardioIntegration, PDO::PARAM_BOOL);
        $stmt->bindParam(':ernaehrungshinweise', $ernaehrungshinweise, PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $notizen, PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $istAktiv, PDO::PARAM_BOOL);
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
        split_typ = :split_typ,
        trainingsdauer = :trainingsdauer,
        trainingsumgebung = :trainingsumgebung,
        trainingsfrequenz = :trainingsfrequenz,
        spezifisches_ziel = :spezifisches_ziel,
        koerperliche_einschraenkungen = :koerperliche_einschraenkungen,
        erholungszeit = :erholungszeit,
        periodisierung = :periodisierung,
        spezielle_techniken = :spezielle_techniken,
        aufwaermphase = :aufwaermphase,
        progression = :progression,
        kardio_integration = :kardio_integration,
        ernaehrungshinweise = :ernaehrungshinweise,
        notizen = :notizen, 
        ist_aktiv = :ist_aktiv 
        WHERE trainingsplan_id = :id";
        
        // Variablen für bindParam erstellen
        $planName = $data['plan_name'];
        $schwierigkeitsgrad = $data['schwierigkeitsgrad'];
        $trainingsart = $data['trainingsart'];
        $splitTyp = $data['split_typ'] ?? 'Ganzkörper';
        $trainingsdauer = $data['trainingsdauer'] ?? 60;
        $trainingsumgebung = $data['trainingsumgebung'] ?? 'Fitnessstudio';
        $trainingsfrequenz = $data['trainingsfrequenz'] ?? 3;
        $spezifischesZiel = $data['spezifisches_ziel'] ?? null;
        $koerperlicheEinschraenkungen = $data['koerperliche_einschraenkungen'] ?? null;
        $erholungszeit = $data['erholungszeit'] ?? 'Mittel';
        $periodisierung = $data['periodisierung'] ?? false;
        $spezielleTechniken = $data['spezielle_techniken'] ?? false;
        $aufwaermphase = $data['aufwaermphase'] ?? true;
        $progression = $data['progression'] ?? true;
        $kardioIntegration = $data['kardio_integration'] ?? false;
        $ernaehrungshinweise = $data['ernaehrungshinweise'] ?? null;
        $notizen = $data['notizen'] ?? null;
        $istAktiv = $data['ist_aktiv'];
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':plan_name', $planName, PDO::PARAM_STR);
        $stmt->bindParam(':schwierigkeitsgrad', $schwierigkeitsgrad, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $trainingsart, PDO::PARAM_STR);
        $stmt->bindParam(':split_typ', $splitTyp, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsdauer', $trainingsdauer, PDO::PARAM_INT);
        $stmt->bindParam(':trainingsumgebung', $trainingsumgebung, PDO::PARAM_STR);
        $stmt->bindParam(':trainingsfrequenz', $trainingsfrequenz, PDO::PARAM_INT);
        $stmt->bindParam(':spezifisches_ziel', $spezifischesZiel, PDO::PARAM_STR);
        $stmt->bindParam(':koerperliche_einschraenkungen', $koerperlicheEinschraenkungen, PDO::PARAM_STR);
        $stmt->bindParam(':erholungszeit', $erholungszeit, PDO::PARAM_STR);
        $stmt->bindParam(':periodisierung', $periodisierung, PDO::PARAM_BOOL);
        $stmt->bindParam(':spezielle_techniken', $spezielleTechniken, PDO::PARAM_BOOL);
        $stmt->bindParam(':aufwaermphase', $aufwaermphase, PDO::PARAM_BOOL);
        $stmt->bindParam(':progression', $progression, PDO::PARAM_BOOL);
        $stmt->bindParam(':kardio_integration', $kardioIntegration, PDO::PARAM_BOOL);
        $stmt->bindParam(':ernaehrungshinweise', $ernaehrungshinweise, PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $notizen, PDO::PARAM_STR);
        $stmt->bindParam(':ist_aktiv', $istAktiv, PDO::PARAM_BOOL);
        
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
* KORRIGIERT: Stellt sicher, dass alle Übungsinformationen korrekt abgerufen werden
*/
public function findUebungenByPlanId(int $planId): array
{
    $sql = "SELECT tpu.trainingsplan_uebung_id, tpu.trainingsplan_id, tpu.uebung_id,
    tpu.reihenfolge, tpu.trainingswoche, tpu.trainingstag, tpu.ist_aktiv,
    u.name, u.beschreibung, u.schwierigkeitsgrad, u.trainingsart,
    u.muskelgruppe, u.equipment, u.anleitung, u.sicherheitshinweise, u.video_url,
    tpud.detail_id, tpud.satz_nummer, tpud.gewicht_kg, tpud.gewicht_prozent,
    tpud.wiederholungen, tpud.pause_sekunden, tpud.technik, tpud.notizen
    FROM trainingsplan_uebungen tpu
    JOIN uebungen u ON tpu.uebung_id = u.uebung_id
    LEFT JOIN trainingsplan_uebung_details tpud ON tpu.trainingsplan_uebung_id = tpud.trainingsplan_uebung_id
    WHERE tpu.trainingsplan_id = :plan_id AND tpu.ist_aktiv = 1
    ORDER BY tpu.trainingstag, tpu.reihenfolge, tpud.satz_nummer";

    $stmt = $this->connection->prepare($sql);
    $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Wenn keine Ergebnisse gefunden wurden, leeres Array zurückgeben
    if (empty($results)) {
        return [];
    }

    $uebungen = [];

    foreach ($results as $row) {
        $uebungId = $row['trainingsplan_uebung_id'];
        if (!isset($uebungen[$uebungId])) {
            // Grundlegende Übungsinformationen initialisieren
            $uebungen[$uebungId] = [
                'trainingsplan_uebung_id' => $row['trainingsplan_uebung_id'],
                'trainingsplan_id' => $row['trainingsplan_id'],
                'uebung_id' => $row['uebung_id'],
                'reihenfolge' => $row['reihenfolge'],
                'trainingswoche' => $row['trainingswoche'],
                'trainingstag' => $row['trainingstag'],
                'ist_aktiv' => $row['ist_aktiv'],
                'name' => $row['name'],
                'beschreibung' => $row['beschreibung'],
                'schwierigkeitsgrad' => $row['schwierigkeitsgrad'] ?? null,
                'trainingsart' => $row['trainingsart'] ?? null,
                'muskelgruppe' => $row['muskelgruppe'] ?? null,
                'equipment' => $row['equipment'] ?? null,
                'anleitung' => $row['anleitung'] ?? null,
                'sicherheitshinweise' => $row['sicherheitshinweise'] ?? null,
                'video_url' => $row['video_url'] ?? null,
                'details' => []
            ];

            // Füge optionale Felder hinzu, wenn sie in der Datenbank existieren
            if (isset($row['koerperbereich'])) {
                $uebungen[$uebungId]['koerperbereich'] = $row['koerperbereich'];
            }

            if (isset($row['uebungstyp'])) {
                $uebungen[$uebungId]['uebungstyp'] = $row['uebungstyp'];
            }
        }

        // Füge Details hinzu, wenn vorhanden
        if (!empty($row['detail_id'])) {
            $uebungen[$uebungId]['details'][] = [
                'detail_id' => $row['detail_id'],
                'satz_nummer' => $row['satz_nummer'] ?? null,
                'gewicht_kg' => $row['gewicht_kg'] ?? null,
                'gewicht_prozent' => $row['gewicht_prozent'] ?? null,
                'wiederholungen' => $row['wiederholungen'] ?? null,
                'pause_sekunden' => $row['pause_sekunden'] ?? null,
                'technik' => $row['technik'] ?? null,
                'notizen' => $row['notizen'] ?? null
            ];
        }
    }

    return array_values($uebungen);
}

    /**
     * Findet die Details einer Trainingsplan-Übung
     */
    public function findUebungDetailsByUebungId(int $trainingsplanUebungId): array
    {
        $sql = "SELECT * FROM trainingsplan_uebung_details
                WHERE trainingsplan_uebung_id = :trainingsplan_uebung_id
                ORDER BY satz_nummer";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
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
        
        $trainingsplanId = $data['trainingsplan_id'];
        $uebungId = $data['uebung_id'];
        $reihenfolge = $data['reihenfolge'];
        $trainingstag = $data['trainingstag'];
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_id', $trainingsplanId, PDO::PARAM_INT);
        $stmt->bindParam(':uebung_id', $uebungId, PDO::PARAM_INT);
        $stmt->bindParam(':reihenfolge', $reihenfolge, PDO::PARAM_INT);
        $stmt->bindParam(':trainingstag', $trainingstag, PDO::PARAM_STR);
        $stmt->execute();
        
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Fügt Details zu einer Trainingsplan-Übung hinzu
     */
    public function addUebungDetails(array $data): int
    {
        $sql = "INSERT INTO trainingsplan_uebung_details
        (trainingsplan_uebung_id, satz_nummer, gewicht_kg, gewicht_prozent, wiederholungen, pause_sekunden, technik, notizen)
        VALUES (:trainingsplan_uebung_id, :satz_nummer, :gewicht_kg, :gewicht_prozent, :wiederholungen, :pause_sekunden, :technik, :notizen)";

        $trainingsplanUebungId = $data['trainingsplan_uebung_id'];
        $satzNummer = $data['satz_nummer'];
        $gewichtKg = $data['gewicht_kg'];
        $gewichtProzent = $data['gewicht_prozent'];
        $wiederholungen = $data['wiederholungen'];
        $pauseSekunden = $data['pause_sekunden'];
        $technik = $data['technik'] ?? 'Normal';
        $notizen = $data['notizen'] ?? '';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
        $stmt->bindParam(':satz_nummer', $satzNummer, PDO::PARAM_INT);
        $stmt->bindParam(':gewicht_kg', $gewichtKg, PDO::PARAM_STR);
        $stmt->bindParam(':gewicht_prozent', $gewichtProzent, PDO::PARAM_INT);
        $stmt->bindParam(':wiederholungen', $wiederholungen, PDO::PARAM_INT);
        $stmt->bindParam(':pause_sekunden', $pauseSekunden, PDO::PARAM_INT);
        $stmt->bindParam(':technik', $technik, PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $notizen, PDO::PARAM_STR);
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
        technik = :technik,
        notizen = :notizen 
        WHERE detail_id = :detail_id";
        
        $gewichtKg = $data['gewicht_kg'];
        $gewichtProzent = $data['gewicht_prozent'];
        $wiederholungen = $data['wiederholungen'];
        $pauseSekunden = $data['pause_sekunden'];
        $technik = $data['technik'] ?? 'Normal';
        $notizen = $data['notizen'];
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':detail_id', $detailId, PDO::PARAM_INT);
        $stmt->bindParam(':gewicht_kg', $gewichtKg, PDO::PARAM_STR);
        $stmt->bindParam(':gewicht_prozent', $gewichtProzent, PDO::PARAM_INT);
        $stmt->bindParam(':wiederholungen', $wiederholungen, PDO::PARAM_INT);
        $stmt->bindParam(':pause_sekunden', $pauseSekunden, PDO::PARAM_INT);
        $stmt->bindParam(':technik', $technik, PDO::PARAM_STR);
        $stmt->bindParam(':notizen', $notizen, PDO::PARAM_STR);
        
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

    /**
     * Fügt Beispielübungen zu einem Trainingsplan hinzu, wenn keine Übungen vorhanden sind
     */
    public function addExampleUebungenToTrainingsplan(int $trainingsplanId): bool
    {
        // Zuerst prüfen, ob bereits Übungen vorhanden sind
        $sql = "SELECT COUNT(*) FROM trainingsplan_uebungen WHERE trainingsplan_id = :trainingsplan_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':trainingsplan_id', $trainingsplanId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            // Es sind bereits Übungen vorhanden, nichts tun
            return false;
        }

        // Trainingsplan-Informationen abrufen
        $trainingsplan = $this->findById($trainingsplanId);
        if (!$trainingsplan) {
            return false;
        }

        // Passende Übungen basierend auf Schwierigkeitsgrad und Trainingsart finden
        $sql = "SELECT uebung_id, name, muskelgruppe FROM uebungen
                WHERE schwierigkeitsgrad = :schwierigkeitsgrad
                AND (trainingsart = :trainingsart OR trainingsart = 'Kraftausdauer')
                LIMIT 6";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':schwierigkeitsgrad', $trainingsplan['schwierigkeitsgrad'], PDO::PARAM_STR);
        $stmt->bindParam(':trainingsart', $trainingsplan['trainingsart'], PDO::PARAM_STR);
        $stmt->execute();

        $uebungen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($uebungen)) {
            // Wenn keine passenden Übungen gefunden wurden, nehme beliebige Übungen
            $sql = "SELECT uebung_id, name, muskelgruppe FROM uebungen LIMIT 6";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            $uebungen = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($uebungen)) {
            return false; // Keine Übungen in der Datenbank
        }

        // Trainingstage festlegen
        $trainingstage = ['Montag', 'Mittwoch', 'Freitag'];

        // Übungen auf die Trainingstage verteilen
        $uebungenProTag = ceil(count($uebungen) / count($trainingstage));
        $uebungIndex = 0;

        try {
            $this->connection->beginTransaction();

            foreach ($trainingstage as $tag) {
                for ($i = 0; $i < $uebungenProTag && $uebungIndex < count($uebungen); $i++) {
                    $uebung = $uebungen[$uebungIndex];

                    // Übung zum Trainingsplan hinzufügen
                    $sql = "INSERT INTO trainingsplan_uebungen
                            (trainingsplan_id, uebung_id, reihenfolge, trainingstag, ist_aktiv)
                            VALUES (:trainingsplan_id, :uebung_id, :reihenfolge, :trainingstag, 1)";

                    $stmt = $this->connection->prepare($sql);
                    $stmt->bindParam(':trainingsplan_id', $trainingsplanId, PDO::PARAM_INT);
                    $stmt->bindParam(':uebung_id', $uebung['uebung_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':reihenfolge', $i, PDO::PARAM_INT);
                    $stmt->bindParam(':trainingstag', $tag, PDO::PARAM_STR);
                    $stmt->execute();

                    $trainingsplanUebungId = (int)$this->connection->lastInsertId();

                    // Details für die Übung hinzufügen (3 Sätze)
                    for ($satz = 1; $satz <= 3; $satz++) {
                        $wiederholungen = ($trainingsplan['trainingsart'] == 'Kraftausdauer') ? 15 : 10;
                        $gewichtProzent = 70 + ($satz - 1) * 5; // 70%, 75%, 80%
                        $pauseSekunden = ($trainingsplan['trainingsart'] == 'Kraftausdauer') ? 60 : 90;

                        $sql = "INSERT INTO trainingsplan_uebung_details
                                (trainingsplan_uebung_id, satz_nummer, gewicht_kg, gewicht_prozent, wiederholungen, pause_sekunden, technik)
                                VALUES (:trainingsplan_uebung_id, :satz_nummer, 0, :gewicht_prozent, :wiederholungen, :pause_sekunden, 'Normal')";

                        $stmt = $this->connection->prepare($sql);
                        $stmt->bindParam(':trainingsplan_uebung_id', $trainingsplanUebungId, PDO::PARAM_INT);
                        $stmt->bindParam(':satz_nummer', $satz, PDO::PARAM_INT);
                        $stmt->bindParam(':gewicht_prozent', $gewichtProzent, PDO::PARAM_INT);
                        $stmt->bindParam(':wiederholungen', $wiederholungen, PDO::PARAM_INT);
                        $stmt->bindParam(':pause_sekunden', $pauseSekunden, PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    $uebungIndex++;
                }
            }

            $this->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            return false;
        }
    }
}