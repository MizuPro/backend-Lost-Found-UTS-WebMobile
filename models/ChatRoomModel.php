<?php

require_once __DIR__ . '/../config/Database.php';

class ChatRoomModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function createRoom($petugas_id, $pelapor_id, $laporan_id)
    {
        $firebase_room_id = uniqid('room_');
        $stmt = $this->pdo->prepare("INSERT INTO chat_rooms (firebase_room_id, petugas_id, pelapor_id, laporan_id, status) VALUES (?, ?, ?, ?, 'aktif')");
        $stmt->execute([$firebase_room_id, $petugas_id, $pelapor_id, $laporan_id]);
        return $this->getRoomById($this->pdo->lastInsertId());
    }

    public function getRoomById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chat_rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRoomByFirebaseId($firebase_room_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chat_rooms WHERE firebase_room_id = ?");
        $stmt->execute([$firebase_room_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRoomsByUser($user_id, $role)
    {
        $column = $role === 'petugas' ? 'petugas_id' : 'pelapor_id';
        $stmt = $this->pdo->prepare("SELECT * FROM chat_rooms WHERE $column = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function endRoom($id)
    {
        $stmt = $this->pdo->prepare("UPDATE chat_rooms SET status = 'selesai' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getRoomByLaporanAndPetugas($laporan_id, $petugas_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chat_rooms WHERE laporan_id = ? AND petugas_id = ? AND status = 'aktif' LIMIT 1");
        $stmt->execute([$laporan_id, $petugas_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

