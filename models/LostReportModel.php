<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * LostReportModel
 * Semua operasi database untuk tabel `laporan_kehilangan`
 *
 * Kebijakan akses data:
 *   - petugas : melihat semua laporan + info pelapor
 *   - pelapor : hanya laporan miliknya sendiri
 */
class LostReportModel
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Untuk PETUGAS — semua laporan ────────────────────────────────────────

    /**
     * Ambil semua laporan kehilangan — khusus petugas
     * Mendukung filter: status, search (nama_barang)
     */
    public function getAll(array $filters = []): array
    {
        $sql = 'SELECT lk.id, lk.pelapor_id, u.name AS pelapor_name, u.email AS pelapor_email,
                       lk.nama_barang, lk.deskripsi, lk.lokasi,
                       lk.waktu_hilang, lk.status,
                       lk.created_at, lk.updated_at
                FROM laporan_kehilangan lk
                JOIN users u ON lk.pelapor_id = u.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql     .= ' AND lk.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql     .= ' AND lk.nama_barang LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['lokasi'])) {
            $sql     .= ' AND lk.lokasi LIKE ?';
            $params[] = '%' . $filters['lokasi'] . '%';
        }

        $sql .= ' ORDER BY lk.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu laporan kehilangan berdasarkan ID — khusus petugas
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT lk.id, lk.pelapor_id, u.name AS pelapor_name, u.email AS pelapor_email,
                    lk.nama_barang, lk.deskripsi, lk.lokasi,
                    lk.waktu_hilang, lk.status,
                    lk.created_at, lk.updated_at
             FROM laporan_kehilangan lk
             JOIN users u ON lk.pelapor_id = u.id
             WHERE lk.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Untuk PELAPOR — hanya laporan milik sendiri ───────────────────────────

    /**
     * Ambil semua laporan kehilangan milik pelapor tertentu
     */
    public function getAllByPelapor(int $pelaporId, array $filters = []): array
    {
        $sql = 'SELECT lk.id, lk.pelapor_id,
                       lk.nama_barang, lk.deskripsi, lk.lokasi,
                       lk.waktu_hilang, lk.status,
                       lk.created_at, lk.updated_at
                FROM laporan_kehilangan lk
                WHERE lk.pelapor_id = ?';
        $params = [$pelaporId];

        if (!empty($filters['status'])) {
            $sql     .= ' AND lk.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql     .= ' AND lk.nama_barang LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY lk.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu laporan kehilangan milik pelapor tertentu
     */
    public function findByIdAndPelapor(int $id, int $pelaporId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT lk.id, lk.pelapor_id,
                    lk.nama_barang, lk.deskripsi, lk.lokasi,
                    lk.waktu_hilang, lk.status,
                    lk.created_at, lk.updated_at
             FROM laporan_kehilangan lk
             WHERE lk.id = ? AND lk.pelapor_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, $pelaporId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // ── Write operations ─────────────────────────────────────────────────────

    /**
     * Buat laporan kehilangan baru
     * Return: ID yang baru dibuat
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO laporan_kehilangan
                (pelapor_id, nama_barang, deskripsi, lokasi, waktu_hilang, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['pelapor_id'],
            $data['nama_barang'],
            $data['deskripsi']   ?? null,
            $data['lokasi'],
            $data['waktu_hilang'],
            $data['status']      ?? 'menunggu',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update laporan kehilangan
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE laporan_kehilangan
             SET nama_barang  = ?,
                 deskripsi    = ?,
                 lokasi       = ?,
                 waktu_hilang = ?,
                 status       = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['nama_barang'],
            $data['deskripsi']   ?? null,
            $data['lokasi'],
            $data['waktu_hilang'],
            $data['status'],
            $id,
        ]);
    }

    /**
     * Hapus laporan kehilangan (hard delete — hanya oleh petugas)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM laporan_kehilangan WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Cek apakah laporan sedang terlibat dalam pencocokan aktif
     * (status pencocokan: pending atau diverifikasi)
     */
    public function hasActiveMatch(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pencocokan
             WHERE laporan_id = ? AND status IN ('pending', 'diverifikasi')"
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

