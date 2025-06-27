<?php

namespace App\Domain\Maximalkraft;

use PDO;

class MaximalkraftTestRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Findet alle Maximalkraft-Tests eines Mitglieds
     *
     * @param int $mitgliedId Die ID des Mitglieds
     * @return array Die gefundenen Tests
     */
    public function findAllByMitgliedId(int $mitgliedId): array
    {
        $sql = "SELECT * FROM maximalkraft_tests 
                WHERE mitglied_id = :mitglied_id 
                ORDER BY test_datum DESC";
        
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }

    /**
     * Findet einen Maximalkraft-Test anhand seiner ID
     *
     * @param int $testId Die ID des Tests
     * @return array|null Der gefundene Test oder null
     */
    public function findById(int $testId): ?array
    {
        $sql = "SELECT * FROM maximalkraft_tests WHERE test_id = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $testId, PDO::PARAM_INT);
        $query->execute();

        $test = $query->fetch();
        return $test ?: null;
    }

    /**
     * Erstellt einen neuen Maximalkraft-Test
     *
     * @param array $data Die Testdaten
     * @return int Die ID des neu erstellten Tests
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO maximalkraft_tests (
                    mitglied_id, 
                    test_datum,
                    uebung, 
                    gewicht, 
                    wiederholungen,
                    notizen
                ) VALUES (
                    :mitglied_id, 
                    :test_datum,
                    :uebung, 
                    :gewicht, 
                    :wiederholungen,
                    :notizen
                )";
        
        $query = $this->pdo->prepare($sql);
        
        // Wenn kein Datum angegeben wurde, verwende das aktuelle Datum/Zeit
        $testDatum = !empty($data['test_datum']) ? $data['test_datum'] : date('Y-m-d H:i:s');
        
        $query->bindParam(':mitglied_id', $data['mitglied_id'], PDO::PARAM_INT);
        $query->bindParam(':test_datum', $testDatum);
        $query->bindParam(':uebung', $data['uebung']);
        $query->bindParam(':gewicht', $data['gewicht']);
        $query->bindParam(':wiederholungen', $data['wiederholungen'], PDO::PARAM_INT);
        $query->bindParam(':notizen', $data['notizen']);
        
        $query->execute();
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Aktualisiert einen bestehenden Maximalkraft-Test
     *
     * @param int $testId Die ID des Tests
     * @param array $data Die neuen Testdaten
     * @return bool True bei Erfolg, sonst False
     */
    public function update(int $testId, array $data): bool
    {
        $sql = "UPDATE maximalkraft_tests 
                SET test_datum = :test_datum,
                    uebung = :uebung,
                    gewicht = :gewicht,
                    wiederholungen = :wiederholungen,
                    notizen = :notizen
                WHERE test_id = :test_id";
        
        $query = $this->pdo->prepare($sql);
        
        $query->bindParam(':test_id', $testId, PDO::PARAM_INT);
        $query->bindParam(':test_datum', $data['test_datum']);
        $query->bindParam(':uebung', $data['uebung']);
        $query->bindParam(':gewicht', $data['gewicht']);
        $query->bindParam(':wiederholungen', $data['wiederholungen'], PDO::PARAM_INT);
        $query->bindParam(':notizen', $data['notizen']);
        
        return $query->execute();
    }

    /**
     * Löscht einen Maximalkraft-Test
     *
     * @param int $testId Die ID des Tests
     * @return bool True bei Erfolg, sonst False
     */
    public function delete(int $testId): bool
    {
        $sql = "DELETE FROM maximalkraft_tests WHERE test_id = :id";
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':id', $testId, PDO::PARAM_INT);
        
        return $query->execute();
    }

    /**
     * Berechnet das 1RM mit der Brzycki-Formel
     * (Obwohl dies in der Datenbank als generiertes Feld existiert, 
     * kann diese Methode für Berechnungen vor dem Speichern nützlich sein)
     *
     * @param float $gewicht Das verwendete Gewicht
     * @param int $wiederholungen Die Anzahl der Wiederholungen
     * @return float Das berechnete 1RM
     */
    public function berechne1RM(float $gewicht, int $wiederholungen): float
    {
        // Brzycki-Formel: 1RM = Gewicht × (36 / (37 - Wiederholungen))
        return $gewicht * (36 / (37 - $wiederholungen));
    }

    /**
     * Findet die neuesten Tests für jede Übungsart eines Mitglieds
     *
     * @param int $mitgliedId Die ID des Mitglieds
     * @return array Die neuesten Tests pro Übungsart
     */
    public function findLatestTestsPerExercise(int $mitgliedId): array
    {
        $sql = "SELECT t1.* 
                FROM maximalkraft_tests t1
                INNER JOIN (
                    SELECT uebung, MAX(test_datum) as max_datum
                    FROM maximalkraft_tests
                    WHERE mitglied_id = :mitglied_id
                    GROUP BY uebung
                ) t2 ON t1.uebung = t2.uebung AND t1.test_datum = t2.max_datum
                WHERE t1.mitglied_id = :mitglied_id2
                ORDER BY t1.uebung";
        
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
        $query->bindParam(':mitglied_id2', $mitgliedId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }
// c:\xampp\htdocs\SlimSkelleton\src\Domain\Maximalkraft\MaximalkraftTestRepository.php

/**
* Findet den neuesten Test für eine bestimmte Übung eines Mitglieds
*
* @param int $mitgliedId Die ID des Mitglieds
* @param string $uebung Der Name der Übung
* @return array|null Der neueste Test oder null
*/
public function findLatestTestForExercise(int $mitgliedId, string $uebung): ?array
{
    $sql = "SELECT * FROM maximalkraft_tests 
            WHERE mitglied_id = :mitglied_id 
            AND uebung = :uebung
            ORDER BY test_datum DESC
            LIMIT 1";
    
    $query = $this->pdo->prepare($sql);
    $query->bindParam(':mitglied_id', $mitgliedId, PDO::PARAM_INT);
    $query->bindParam(':uebung', $uebung, PDO::PARAM_STR);
    $query->execute();

    $test = $query->fetch();
    return $test ?: null;
}
}