<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * UserModel
 * Semua operasi database yang berkaitan dengan tabel `users`
 */
class UserModel
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cari user berdasarkan email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password, role, created_at FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Cari user berdasarkan ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buat user baru
     * Return: ID user yang baru dibuat
     */
    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $email, $passwordHash, $role]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Cek apakah email sudah dipakai
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Update profile user (nama & email)
     */
    public function updateProfile(int $id, string $name, string $email): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name = ?, email = ? WHERE id = ?'
        );
        return $stmt->execute([$name, $email, $id]);
    }

    /**
     * Update password user
     */
    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([$passwordHash, $id]);
    }

    /**
     * Ambil semua user (untuk petugas admin)
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }
}
