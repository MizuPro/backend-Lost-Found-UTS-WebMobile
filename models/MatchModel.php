<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * MatchModel
 * Semua operasi database untuk tabel `pencocokan`
 */
class MatchModel
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil semua data pencocokan
     * Mendukung filter: status
     */
    public function getAll(array $filters = []): array
    {
        $sql = 'SELECT p.id, p.barang_temuan_id, p.laporan_id, p.petugas_id,
                       p.status, p.catatan, p.waktu_serah, p.created_at, p.updated_at,
                       bt.nama_barang AS barang_temuan_nama,
                       lk.nama_barang AS laporan_nama,
                       u.name AS petugas_name,
                       pelapor.name AS pelapor_name,
                       pelapor.email AS pelapor_email
                FROM pencocokan p
                JOIN barang_temuan bt ON p.barang_temuan_id = bt.id
                JOIN laporan_kehilangan lk ON p.laporan_id = lk.id
                JOIN users u ON p.petugas_id = u.id
                JOIN users pelapor ON lk.pelapor_id = pelapor.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql     .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu data pencocokan berdasarkan ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.barang_temuan_id, p.laporan_id, p.petugas_id,
                    p.status, p.catatan, p.waktu_serah, p.created_at, p.updated_at,
                    bt.nama_barang AS barang_temuan_nama,
                    lk.nama_barang AS laporan_nama,
                    u.name AS petugas_name,
                    pelapor.name AS pelapor_name,
                    pelapor.email AS pelapor_email,
                    lk.pelapor_id
             FROM pencocokan p
             JOIN barang_temuan bt ON p.barang_temuan_id = bt.id
             JOIN laporan_kehilangan lk ON p.laporan_id = lk.id
             JOIN users u ON p.petugas_id = u.id
             JOIN users pelapor ON lk.pelapor_id = pelapor.id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buat riwayat pencocokan baru
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pencocokan
                (barang_temuan_id, laporan_id, petugas_id, status)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['barang_temuan_id'],
            $data['laporan_id'],
            $data['petugas_id'],
            $data['status'] ?? 'pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update data pencocokan (status, catatan, waktu_serah)
     */
    public function updateStatus(int $id, string $status, ?string $catatan = null, ?string $waktuSerah = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE pencocokan
             SET status      = ?,
                 catatan     = COALESCE(?, catatan),
                 waktu_serah = COALESCE(?, waktu_serah)
             WHERE id = ?'
        );
        return $stmt->execute([
            $status,
            $catatan,
            $waktuSerah,
            $id,
        ]);
    }

    /**
     * Cek apakah barang dan laporan sudah dicocokkan sebelumnya (mencegah duplikat set)
     */
    public function findActiveMatch(int $barangId, int $laporanId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pencocokan
             WHERE barang_temuan_id = ? AND laporan_id = ?
             AND status IN (\'pending\', \'diverifikasi\')
             LIMIT 1'
        );
        $stmt->execute([$barangId, $laporanId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
