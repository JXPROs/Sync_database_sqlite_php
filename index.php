<?php

function checkDatabaseFile($path) {
    if (!file_exists($path)) {
        echo "Erreur : Le fichier de base de données n'existe pas : $path\n";
        return false;
    }
    if (!is_readable($path) || !is_writable($path)) {
        echo "Erreur : Problème de permissions sur le fichier : $path\n";
        return false;
    }
    return true;
}

function getTableSchema($db, $table) {
    return $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
}

function syncDatabases($db1Path, $db2Path) {
    if (!checkDatabaseFile($db1Path) || !checkDatabaseFile($db2Path)) {
        return;
    }

    try {
        if (!in_array('sqlite', PDO::getAvailableDrivers())) {
            throw new Exception("L'extension SQLite n'est pas activée dans PHP.");
        }

        $db1 = new PDO("sqlite:$db1Path");
        $db2 = new PDO("sqlite:$db2Path");

        $tables = $db1->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $tableExistsInDb2 = $db2->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();

            if (!$tableExistsInDb2) {
                echo "La table '$table' n'existe pas dans la base de destination. Création de la table...\n";
                $tableSchema = getTableSchema($db1, $table);
                $db2->exec($tableSchema);
                echo "Table '$table' créée dans la base de destination.\n";
            }

            $rows = $db1->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                echo "La table '$table' est vide. Passons à la suivante.\n";
                continue;
            }

            $db2->beginTransaction();

            // Récupérer la structure de la table dans db2
            $columns_db2 = $db2->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);

            // Filtrer les colonnes qui existent dans db2
            $columns = array_intersect(array_keys($rows[0]), $columns_db2);
            
            if (empty($columns)) {
                echo "Aucune colonne commune trouvée pour la table '$table'. Elle sera ignorée.\n";
                $db2->rollBack();
                continue;
            }

            $columns_str = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $db2->exec("DELETE FROM $table");
            $stmt = $db2->prepare("INSERT INTO $table ($columns_str) VALUES ($placeholders)");

            foreach ($rows as $row) {
                $values = array_intersect_key($row, array_flip($columns));
                $stmt->execute(array_values($values));
            }

            $db2->commit();
            echo "Table '$table' synchronisée avec succès.\n";
        }

        echo "Synchronisation terminée.\n";
    } catch (PDOException $e) {
        echo "Erreur de synchronisation PDO : " . $e->getMessage() . "\n";
        if (isset($db2) && $db2->inTransaction()) {
            $db2->rollBack();
        }
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    }
}

$db1Path = 'x/association.sqlite';
$db2Path = 'y/association.sqlite';
syncDatabases($db2Path, $db1Path);
?>