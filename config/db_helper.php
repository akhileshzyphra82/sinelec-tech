<?php
class MySQLDB {

    private $conn;

    public function __construct() {

        $serverName = "localhost";

        if ($_SERVER['HTTP_HOST'] === 'localhost') {
           //$serverName = "162.241.15.242";
            $username = "root";
            $password = "";
            $database = "sinelect_panel_productdb";
        } else {
            $serverName = "localhost";
            $username = "sinelect_db";
            $password = "0W@IdwHzWxE&";
            $database = "sinelect_panel_productdb";
        }


        $this->conn = new mysqli($serverName, $username, $password, $database);

        if ($this->conn->connect_error) {
            $this->handleError("Database connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    /* ----------------------------------------
       Determine parameter types for bind_param
    ---------------------------------------- */
    private function determineTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /* ----------------------------------------
       Centralized error handler
    ---------------------------------------- */
    private function handleError($message, $sql = null) {
        $full = $sql ? $message . ' | SQL: ' . $sql : $message;
        throw new RuntimeException('Database Error: ' . $full);
    }

    /* ----------------------------------------
       Prepare & execute query
    ---------------------------------------- */
    private function executeQuery($sql, $params = null) {

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->handleError($this->conn->error, $sql);
        }

        if (!empty($params)) {
            $types = $this->determineTypes($params);
            if (!$stmt->bind_param($types, ...$params)) {
                $this->handleError($stmt->error, $sql);
            }
        }

        if (!$stmt->execute()) {
            $this->handleError($stmt->error, $sql);
        }

        return $stmt;
    }

    /* ----------------------------------------
       SELECT (mysqlnd-safe)
    ---------------------------------------- */
    public function select($sql, $params = null) {

        $stmt = $this->executeQuery($sql, $params);

        $meta = $stmt->result_metadata();
        if (!$meta) {
            return [];
        }

        $fields = [];
        $row = [];

        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }

        call_user_func_array([$stmt, 'bind_result'], $fields);

        $data = [];

        while ($stmt->fetch()) {
            $record = [];
            foreach ($row as $key => $value) {
                $record[strtoupper($key)] = $value;
            }
            $data[] = (object) $record;
        }

        $stmt->free_result();
        $stmt->close();

        return $data;
    }


public function insert($sql, $params = null) {
    $stmt = $this->executeQuery($sql, $params);

    if ($stmt->affected_rows <= 0) {
        $stmt->close();
        return 0;
    }

    $insertId = $this->conn->insert_id;
    $stmt->close();

    return $insertId;
}

    /* ----------------------------------------
       UPDATE / DELETE
    ---------------------------------------- */
    public function update($sql, $params = null) {
        $stmt = $this->executeQuery($sql, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

?>
