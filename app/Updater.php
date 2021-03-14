<?php

namespace Technicalpenguins\Framed;

use Pusher\Pusher;

class Updater extends Database {
    var $deleted = [];
    var $new = [];

    public function __construct() {
        parent::__construct();

        $sheetData = file_get_contents($_ENV['DRIVEAPP_URL']);

        if ($sheetData) {
            $this->sheetData = json_decode($sheetData);
            $this->sheetIds = array_keys((array)$this->sheetData);
            $this->sort();
            $this->checkDeleted();
            $this->checkNew();
            $this->sendNotification();
        }
    }

    private function checkDeleted() {
        if (!empty($this->deleted)) {
            foreach ($this->deleted as $photo) {
                unlink(Database::$photoDir . $photo['filename']);
                $this->db->query('DELETE FROM photolib WHERE id = "'. $photo['id']. '"');
            }
        }
    }

    private function checkNew() {
        if (!empty($this->sheetData)) {
            foreach ($this->sheetData as $photo) {
                $photo->filename = $this->convertTitle($photo->filename);
                $this->download($photo);
                $this->db->query('INSERT INTO photolib (id,filename,updated) VALUES ("' . $photo->id . '", "' . $photo->filename . '", "' . $photo->updateDate .'")');
            }
        }
    }

    private function convertTitle($str, $delimiter='-') {
        return strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str))))), $delimiter));
    }

    private function download($photo) {
        $photoData = file_get_contents($photo->downloadUrl);
        file_put_contents(Database::$photoDir . $photo->filename, $photoData);
    }

    private function getOldQuery() {
        $this->oldQuery = $this->db->query('SELECT * FROM photolib');
    }    

    private function sendNotification() {
        $this->sheetData = (array)$this->sheetData;
        if (!empty($this->deleted) || !empty($this->sheetData)) {
            $options = array(
                'cluster' => $_ENV['PUSHER_CLUSTER'],
                'useTLS' => $_ENV['PUSHER_TLS']
            );
            $pusher = new Pusher(
                $_ENV['PUSHER_KEY'],
                $_ENV['PUSHER_SECRET'],
                $_ENV['PUSHER_APP_ID'],
                $options
            );
        }
        if (!empty($this->deleted)) {
            $data = $this->deleted;
            $pusher->trigger('photoView', 'deleted', $data);
        }
        if (!empty($this->sheetData)) {
            $data = [];
            foreach ($this->sheetData as $photo) {
                array_push($data, $photo);
            }
            $pusher->trigger('photoView', 'new', $data);

        }
    }

    private function sort() {
        $this->getOldQuery();
        while ($row = $this->oldQuery->fetchArray(SQLITE3_ASSOC)) {
            if (in_array($row['id'], $this->sheetIds)) {
                unset($this->sheetData->{$row['id']});
            } else {
                array_push($this->deleted, $row);
            }
        }
    }
}