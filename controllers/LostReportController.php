<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/LostReportModel.php';

/**
 * LostReportController
 * Menangani operasi laporan kehilangan
 *
 * Akses:
 *   index, show   => petugas (semua laporan + info pelapor), pelapor (hanya miliknya)
 *   store         => petugas & pelapor (pelapor_id otomatis dari token)
 *   update        => petugas (semua laporan), pelapor (hanya miliknya, status terbatas)
 *   delete        => petugas only (hard delete, tidak bisa jika ada pencocokan aktif)
 */
class LostReportController
{
    /** @var LostReportModel */
    private $model;

    public function __construct()
    {
        $this->model = new LostReportModel();
    }

    // ── GET /api/lost-reports ────────────────────────────────────────────────
    public function index(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role']    ?? '';
        $userId   = (int) ($authUser['user_id'] ?? 0);

        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'lokasi' => isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        // Validasi nilai status jika diberikan
        $validStatus = ['menunggu', 'dicocokkan', 'selesai', 'ditutup'];
        if ($filters['status'] !== '' && !in_array($filters['status'], $validStatus, true)) {
            ResponseHelper::error(
                'Nilai status tidak valid. Pilihan: menunggu, dicocokkan, selesai, ditutup.',
                422
            );
        }

        if ($role === ROLE_PETUGAS) {
            $reports = $this->model->getAll($filters);
        } else {
            // Pelapor hanya melihat laporan miliknya sendiri
            $reports = $this->model->getAllByPelapor($userId, $filters);
        }

        ResponseHelper::success(
            ['lost_reports' => $reports, 'total' => count($reports)],
            'Data laporan kehilangan berhasil diambil.'
        );
    }

    // ── GET /api/lost-reports/{id} ───────────────────────────────────────────
    public function show(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role']    ?? '';
        $userId   = (int) ($authUser['user_id'] ?? 0);
        $id       = (int) ($GLOBALS['route_params']['id'] ?? 0);

        if ($role === ROLE_PETUGAS) {
            $report = $this->model->findById($id);
        } else {
            // Pelapor hanya boleh melihat laporan miliknya sendiri
            $report = $this->model->findByIdAndPelapor($id, $userId);
        }

        if (!$report) {
            ResponseHelper::notFound('Laporan kehilangan tidak ditemukan.');
        }

        ResponseHelper::success(
            ['lost_report' => $report],
            'Detail laporan kehilangan berhasil diambil.'
        );
    }

    // ── POST /api/lost-reports ───────────────────────────────────────────────
    public function store(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $userId   = (int) ($authUser['user_id'] ?? 0);

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());

        $errors = ValidationHelper::required($input, ['nama_barang', 'lokasi', 'waktu_hilang']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::maxLength($input['nama_barang'], 150)) {
            ResponseHelper::validationError(['nama_barang' => 'Nama barang maksimal 150 karakter.']);
        }

        if (!ValidationHelper::maxLength($input['lokasi'], 200)) {
            ResponseHelper::validationError(['lokasi' => 'Lokasi maksimal 200 karakter.']);
        }

        $waktu = \DateTime::createFromFormat('Y-m-d H:i:s', $input['waktu_hilang']);
        if (!$waktu) {
            ResponseHelper::validationError(['waktu_hilang' => 'Format waktu_hilang harus: YYYY-MM-DD HH:MM:SS']);
        }

        $id = $this->model->create([
            'pelapor_id'   => $userId,
            'nama_barang'  => $input['nama_barang'],
            'deskripsi'    => $input['deskripsi'] ?? null,
            'lokasi'       => $input['lokasi'],
            'waktu_hilang' => $input['waktu_hilang'],
            'status'       => 'menunggu',
        ]);

        $report = $this->model->findById($id);

        ResponseHelper::success(
            ['lost_report' => $report],
            'Laporan kehilangan berhasil dibuat.',
            201
        );
    }

    // ── PUT /api/lost-reports/{id} ───────────────────────────────────────────
    public function update(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role']    ?? '';
        $userId   = (int) ($authUser['user_id'] ?? 0);
        $id       = (int) ($GLOBALS['route_params']['id'] ?? 0);

        // Ambil laporan — petugas bisa akses semua, pelapor hanya miliknya
        if ($role === ROLE_PETUGAS) {
            $report = $this->model->findById($id);
        } else {
            $report = $this->model->findByIdAndPelapor($id, $userId);
        }

        if (!$report) {
            ResponseHelper::notFound('Laporan kehilangan tidak ditemukan.');
        }

        // Laporan yang sudah selesai/ditutup tidak dapat diubah
        if (in_array($report['status'], ['selesai', 'ditutup'], true)) {
            ResponseHelper::error(
                'Laporan yang sudah selesai atau ditutup tidak dapat diubah.',
                409
            );
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());

        $errors = ValidationHelper::required($input, ['nama_barang', 'lokasi', 'waktu_hilang', 'status']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        // Pelapor hanya boleh set status 'menunggu' atau 'ditutup' (menutup laporan sendiri)
        // Petugas boleh set semua status
        if ($role === ROLE_PETUGAS) {
            $validStatus = ['menunggu', 'dicocokkan', 'selesai', 'ditutup'];
        } else {
            $validStatus = ['menunggu', 'ditutup'];
        }

        if (!ValidationHelper::inArray($input['status'], $validStatus)) {
            ResponseHelper::validationError([
                'status' => 'Status tidak valid. Pilihan yang tersedia: ' . implode(', ', $validStatus) . '.'
            ]);
        }

        if (!ValidationHelper::maxLength($input['nama_barang'], 150)) {
            ResponseHelper::validationError(['nama_barang' => 'Nama barang maksimal 150 karakter.']);
        }

        if (!ValidationHelper::maxLength($input['lokasi'], 200)) {
            ResponseHelper::validationError(['lokasi' => 'Lokasi maksimal 200 karakter.']);
        }

        $waktu = \DateTime::createFromFormat('Y-m-d H:i:s', $input['waktu_hilang']);
        if (!$waktu) {
            ResponseHelper::validationError(['waktu_hilang' => 'Format waktu_hilang harus: YYYY-MM-DD HH:MM:SS']);
        }

        $this->model->update($id, [
            'nama_barang'  => $input['nama_barang'],
            'deskripsi'    => $input['deskripsi'] ?? null,
            'lokasi'       => $input['lokasi'],
            'waktu_hilang' => $input['waktu_hilang'],
            'status'       => $input['status'],
        ]);

        if ($role === ROLE_PETUGAS) {
            $updated = $this->model->findById($id);
        } else {
            $updated = $this->model->findByIdAndPelapor($id, $userId);
        }

        ResponseHelper::success(
            ['lost_report' => $updated],
            'Laporan kehilangan berhasil diperbarui.'
        );
    }

    // ── DELETE /api/lost-reports/{id} ───────────────────────────────────────
    public function delete(): void
    {
        $id     = (int) ($GLOBALS['route_params']['id'] ?? 0);
        $report = $this->model->findById($id);

        if (!$report) {
            ResponseHelper::notFound('Laporan kehilangan tidak ditemukan.');
        }

        // Tolak delete jika laporan masih ada pencocokan aktif
        if ($this->model->hasActiveMatch($id)) {
            ResponseHelper::error(
                'Laporan tidak dapat dihapus karena sedang dalam proses pencocokan aktif.',
                409
            );
        }

        try {
            $this->model->delete($id);

            ResponseHelper::success(
                ['id' => $id],
                'Laporan kehilangan berhasil dihapus.'
            );
        } catch (\PDOException $e) {
            // Cek constraint violation (23000)
            if ($e->getCode() === '23000') {
                ResponseHelper::error(
                    'Laporan tidak dapat dihapus karena sudah memiliki riwayat pencocokan (aktif maupun tidak aktif).',
                    409
                );
            }
            ResponseHelper::error('Terjadi kesalahan saat menghapus laporan: ' . $e->getMessage(), 500);
        }
    }
}

