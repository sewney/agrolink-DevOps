<?php
trait Database
{
    private function connect()
    {
        if (isset($GLOBALS['__AGROLINK_DB_CONNECTION']) && $GLOBALS['__AGROLINK_DB_CONNECTION'] instanceof PDO) {
            return $GLOBALS['__AGROLINK_DB_CONNECTION'];
        }

        $host = (string)DBHOST;
        $string = "mysql:host=" . $host . ";dbname=" . DBNAME . ";charset=utf8mb4";

        try {
            $con = new PDO($string, DBUSER, DBPASS);
        } catch (PDOException $e) {
            // Local XAMPP setups often fail socket-based localhost connections.
            if ($host === 'localhost') {
                $fallback = "mysql:host=127.0.0.1;dbname=" . DBNAME . ";charset=utf8mb4";
                $con = new PDO($fallback, DBUSER, DBPASS);
            } else {
                throw $e;
            }
        }

        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        $GLOBALS['__AGROLINK_DB_CONNECTION'] = $con;
        return $con;
    }

    public function query($query, $data = [])
    {
        try {
            $con = $this->connect();
            $stm = $con->prepare($query);

            $check = $stm->execute($data);
            if ($check) {
                $result = $stm->fetchAll(PDO::FETCH_OBJ);
                if (is_array($result) && count($result)) {
                    return $result;
                }
            }
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
        }
        return false;
    }

    public function get_row($query, $data = [])
    {
        try {
            $con = $this->connect();
            $stm = $con->prepare($query);

            $check = $stm->execute($data);
            if ($check) {
                $result = $stm->fetchAll(PDO::FETCH_OBJ);
                if (is_array($result) && count($result)) {
                    return $result[0];
                }
            }
        } catch (PDOException $e) {
            error_log("Get Row Error: " . $e->getMessage());
        }
        return false;
    }

    // NEW: execute INSERT/UPDATE/DELETE
    public function write($query, $data = [])
    {
        try {
            $con = $this->connect();
            $stm = $con->prepare($query);
            if ($stm->execute($data)) {
                // Return inserted ID only for INSERT statements; otherwise return affected rows.
                // Using lastInsertId() for non-INSERT statements can leak stale IDs.
                $trimmedQuery = ltrim((string)$query);
                $isInsert = stripos($trimmedQuery, 'insert') === 0;

                if ($isInsert) {
                    $lastInsertId = $con->lastInsertId();
                    if (is_numeric($lastInsertId) && (int)$lastInsertId > 0) {
                        return (int)$lastInsertId;
                    }
                }

                return (int)$stm->rowCount();
            }
        } catch (PDOException $e) {
            error_log("Write Error: " . $e->getMessage() . " | Query: " . $query . " | Data: " . json_encode($data));
        }
        return false;
    }

    public function beginTransaction()
    {
        try {
            $con = $this->connect();
            if ($con->inTransaction()) {
                return true;
            }

            return $con->beginTransaction();
        } catch (PDOException $e) {
            error_log("Transaction Begin Error: " . $e->getMessage());
        }

        return false;
    }

    public function commit()
    {
        try {
            $con = $this->connect();
            if (!$con->inTransaction()) {
                return false;
            }

            return $con->commit();
        } catch (PDOException $e) {
            error_log("Transaction Commit Error: " . $e->getMessage());
        }

        return false;
    }

    public function rollBack()
    {
        try {
            $con = $this->connect();
            if (!$con->inTransaction()) {
                return false;
            }

            return $con->rollBack();
        } catch (PDOException $e) {
            error_log("Transaction Rollback Error: " . $e->getMessage());
        }

        return false;
    }

    public function inTransaction()
    {
        try {
            return $this->connect()->inTransaction();
        } catch (PDOException $e) {
            error_log("Transaction State Error: " . $e->getMessage());
        }

        return false;
    }
}
