<?php
@session_start();
@header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = [
    'host'     => 'localhost',
    'database' => '',
    'username' => '',
    'password' => '',
    'charset'  => 'utf8mb4',
];

function dd($i) {
    echo '<pre>'; print_r($i); echo '</pre>'; exit;
}
function dump($i) {
    echo '<pre>'; print_r($i); echo '</pre>';
}
function pr($i) {
    return htmlspecialchars(strip_tags(trim($i)), ENT_QUOTES);
}

class MysqliWrapper {
    private $mysqli;

    public function __construct(mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    public function get(string $sql, array $params = [], bool $single = false): mixed {
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . $this->mysqli->error);
        }

        if (!empty($params)) {
            $types = array_shift($params);
            $stmt->bind_param($types, ...array_values($params));
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) return [];

        return $single ? $result->fetch_assoc() : $result->fetch_all(MYSQLI_ASSOC);
    }

    public function raw(string $sql): bool|mysqli_result {
        return $this->mysqli->query($sql);
    }

    public function __call($method, $args) {
        return call_user_func_array([$this->mysqli, $method], $args);
    }
}

class Database {
    private static $instance;
    private $connection;

    private function __construct(array $config) {
        $mysqli = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($mysqli->connect_error) {
            throw new Exception("Ошибка подключения: " . $mysqli->connect_error);
        }

        $mysqli->set_charset($config['charset']);
        $this->connection = new MysqliWrapper($mysqli);
    }

    public static function getInstance(array $config): self {
        if (!self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getConnection(): MysqliWrapper {
        return $this->connection;
    }
}

$db = Database::getInstance($config)->getConnection();


$users = $db->get("SELECT * FROM users");
dump($users);

$user = $db->get("SELECT * FROM users WHERE id = ?", ['i', 1], true);
dd($user);