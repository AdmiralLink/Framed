<?php

namespace Technicalpenguins\Framed;

use SQLite3;

class Database {
    static $dbDir = __DIR__ . '/../photos.db';
    static $photoDir = __DIR__ . '/../public/photos/';

    public function __construct() {
        if (file_exists(self::$dbDir)) {
            $this->db = new SQLite3(self::$dbDir);
        } else {
            $this->db = new SQLite3(self::$dbDir);
            $this->db->exec('CREATE TABLE photolib(id VARCHAR(45) PRIMARY KEY, filename VARCHAR(200), updated VARCHAR(30))');
        }
    }
}