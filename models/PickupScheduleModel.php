<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * PickupScheduleModel
 * Semua operasi database untuk tabel `jadwal_pengambilan`
 */
class PickupScheduleModel
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil daftar jadwal pengambilan
     * Mendukung filter: status, match_id, pelapor_id
     */
    public function getAll(array $filters = []): array
    {
        $sql = 'SELECT jp.id, jp.match_id, jp.pelapor_id, jp.petugas_id,
                       jp.waktu_jadwal, jp.lokasi_pengambilan, jp.catatan,
                       jp.status, jp.completed_at, jp.created_at, jp.updated_at,
                       p.status AS match_status,
                       bt.nama_barang AS barang_temuan_nama,
                       lk.nama_barang AS laporan_nama,
                       pelapor.name AS pelapor_name,
                       pelapor.email AS pelapor_email,
                       petugas.name AS petugas_name
                FROM jadwal_pengambilan jp
                JOIN pencocokan p ON jp.match_id = p.id
                JOIN barang_temuan bt ON p.barang_temuan_id = bt.id
                JOIN laporan_kehilangan lk ON p.laporan_id = lk.id
                JOIN users pelapor ON jp.pelapor_id = pelapor.id
                LEFT JOIN users petugas ON jp.petugas_id = petugas.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND jp.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['match_id'])) {
            $sql .= ' AND jp.match_id = ?';
            $params[] = (int) $filters['match_id'];
        }

        if (!empty($filters['pelapor_id'])) {
            $sql .= ' AND jp.pelapor_id = ?';
            $params[] = (int) $filters['pelapor_id'];
        }

        $sql .= ' ORDER BY jp.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu jadwal pengambilan berdasarkan ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT jp.id, jp.match_id, jp.pelapor_id, jp.petugas_id,
                    jp.waktu_jadwal, jp.lokasi_pengambilan, jp.catatan,
                    jp.status, jp.completed_at, jp.created_at, jp.updated_at,
                    p.status AS match_status,
                    p.barang_temuan_id,
                    p.laporan_id,
                    bt.nama_barang AS barang_temuan_nama,
                    lk.nama_barang AS laporan_nama,
                    pelapor.name AS pelapor_name,
                    pelapor.email AS pelapor_email,
                    petugas.name AS petugas_name
             FROM jadwal_pengambilan jp
             JOIN pencocokan p ON jp.match_id = p.id
             JOIN barang_temuan bt ON p.barang_temuan_id = bt.id
             JOIN laporan_kehilangan lk ON p.laporan_id = lk.id
             JOIN users pelapor ON jp.pelapor_id = pelapor.id
             LEFT JOIN users petugas ON jp.petugas_id = petugas.id
             WHERE jp.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Buat pengajuan jadwal baru
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jadwal_pengambilan
                (match_id, pelapor_id, waktu_jadwal, lokasi_pengambilan, catatan, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['match_id'],
            $data['pelapor_id'],
            $data['waktu_jadwal'],
            $data['lokasi_pengambilan'],
            $data['catatan'] ?? null,
            $data['status'] ?? 'menunggu_persetujuan',
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Cek apakah match memiliki jadwal aktif
     * Jadwal aktif = menunggu_persetujuan atau disetujui
     */
    public function findActiveByMatchId(int $matchId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM jadwal_pengambilan
             WHERE match_id = ?
               AND status IN ('menunggu_persetujuan', 'disetujui')
             LIMIT 1"
        );
        $stmt->execute([$matchId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update detail jadwal aktif (tanpa ubah status)
     */
    public function updateActive(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE jadwal_pengambilan
             SET waktu_jadwal       = ?,
                 lokasi_pengambilan = ?,
                 catatan            = COALESCE(?, catatan)
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['waktu_jadwal'],
            $data['lokasi_pengambilan'],
            $data['catatan'] ?? null,
            $id,
        ]);
    }

    /**
     * Update status jadwal (approve/reject/cancel/complete)
     */
    public function updateStatus(
        int $id,
        string $status,
        ?int $petugasId = null,
        ?string $catatan = null,
        ?string $completedAt = null
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE jadwal_pengambilan
             SET status       = ?,
                 petugas_id   = COALESCE(?, petugas_id),
                 catatan      = COALESCE(?, catatan),
                 completed_at = COALESCE(?, completed_at)
             WHERE id = ?'
        );
        return $stmt->execute([
            $status,
            $petugasId,
            $catatan,
            $completedAt,
            $id,
        ]);
    }
}

