<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * FoundItemModel
 * Semua operasi database untuk tabel `barang_temuan`
 *
 * Kebijakan akses data:
 *   - petugas : melihat semua field (nama, deskripsi, lokasi, foto, dll)
 *   - pelapor : hanya nama_barang dan waktu_temuan (mencegah klaim palsu)
 */
class FoundItemModel
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Untuk PETUGAS — semua field ──────────────────────────────────────────

    /**
     * Ambil semua barang temuan (full data) — khusus petugas
     * Mendukung filter: status, lokasi, search, include_archived
     */
    public function getAll(array $filters = [], bool $includeArchived = false): array
    {
        $sql    = 'SELECT bt.id, bt.petugas_id, u.name AS petugas_name,
                          bt.nama_barang, bt.deskripsi, bt.lokasi,
                          bt.waktu_temuan, bt.foto_path, bt.catatan_selesai,
                          bt.status, bt.created_at, bt.updated_at
                   FROM barang_temuan bt
                   JOIN users u ON bt.petugas_id = u.id
                   WHERE 1=1';
        $params = [];

        if (!$includeArchived) {
            $sql .= ' AND bt.deleted_at IS NULL';
        }

        if (!empty($filters['status'])) {
            $sql    .= ' AND bt.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['lokasi'])) {
            $sql    .= ' AND bt.lokasi LIKE ?';
            $params[] = '%' . $filters['lokasi'] . '%';
        }

        if (!empty($filters['search'])) {
            $sql    .= ' AND bt.nama_barang LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY bt.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu barang temuan (full data) — khusus petugas
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT bt.id, bt.petugas_id, u.name AS petugas_name,
                    bt.nama_barang, bt.deskripsi, bt.lokasi,
                    bt.waktu_temuan, bt.foto_path, bt.catatan_selesai,
                    bt.status, bt.created_at, bt.updated_at
             FROM barang_temuan bt
             JOIN users u ON bt.petugas_id = u.id
             WHERE bt.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Untuk PELAPOR — field terbatas ───────────────────────────────────────

    /**
     * Ambil semua barang temuan (field terbatas) — untuk pelapor
     * Hanya: id, nama_barang, waktu_temuan, status
     * Mendukung filter: status, search
     */
    public function getAllForPelapor(array $filters = [], bool $includeArchived = false): array
    {
        $sql    = 'SELECT bt.id, bt.nama_barang, bt.waktu_temuan, bt.status
                   FROM barang_temuan bt
                   WHERE 1=1';
        $params = [];

        if (!$includeArchived) {
            $sql .= ' AND bt.deleted_at IS NULL';
        }

        if (!empty($filters['status'])) {
            $sql    .= ' AND bt.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql    .= ' AND bt.nama_barang LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY bt.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu barang temuan (field terbatas) — untuk pelapor
     * Hanya: id, nama_barang, waktu_temuan, status
     */
    public function findByIdForPelapor(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT bt.id, bt.nama_barang, bt.waktu_temuan, bt.status
             FROM barang_temuan bt
             WHERE bt.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Write operations ─────────────────────────────────────────────────────

    /**
     * Buat barang temuan baru
     * Return: ID yang baru dibuat
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO barang_temuan
                (petugas_id, nama_barang, deskripsi, lokasi, waktu_temuan, foto_path, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['petugas_id'],
            $data['nama_barang'],
            $data['deskripsi']    ?? null,
            $data['lokasi'],
            $data['waktu_temuan'],
            $data['foto_path']    ?? null,
            $data['status']       ?? 'tersimpan',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update data barang temuan
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE barang_temuan
             SET nama_barang      = ?,
                 deskripsi        = ?,
                 lokasi           = ?,
                 waktu_temuan     = ?,
                 foto_path        = ?,
                 catatan_selesai  = ?,
                 status           = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['nama_barang'],
            $data['deskripsi']       ?? null,
            $data['lokasi'],
            $data['waktu_temuan'],
            $data['foto_path']       ?? null,
            $data['catatan_selesai'] ?? null,
            $data['status'],
            $id,
        ]);
    }

    /**
     * Archive barang temuan — set status='selesai' dan catat deleted_at
     * Data tetap tersimpan di database, tidak benar-benar dihapus
     */
    public function archive(int $id, ?string $catatan = null): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE barang_temuan
             SET status           = 'selesai',
                 catatan_selesai  = ?,
                 deleted_at       = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$catatan, $id]);
    }

    /**
     * Cek apakah barang temuan sedang terlibat di pencocokan aktif
     * (status pencocokan: pending atau diverifikasi)
     */
    public function hasActiveMatch(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pencocokan
             WHERE barang_temuan_id = ? AND status IN ('pending', 'diverifikasi')"
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

