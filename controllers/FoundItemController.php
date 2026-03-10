<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/FoundItemModel.php';

/**
 * FoundItemController
 * Menangani operasi barang temuan
 *
 * Akses:
 *   index, show   => petugas (full data), pelapor (terbatas: nama + waktu + status)
 *   store, update => petugas only
 *   archive       => petugas only (soft delete — set status=selesai, catat deleted_at)
 */
class FoundItemController
{
    /** @var FoundItemModel */
    private $model;

    public function __construct()
    {
        $this->model = new FoundItemModel();
    }

    // ── GET /api/found-items ─────────────────────────────────────────────────
    public function index(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role'] ?? '';

        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'lokasi' => isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        // Validasi nilai status jika diberikan
        $validStatus = ['tersimpan', 'dicocokkan', 'diserahkan', 'selesai'];
        if ($filters['status'] !== '' && !in_array($filters['status'], $validStatus, true)) {
            ResponseHelper::error(
                'Nilai status tidak valid. Pilihan: tersimpan, dicocokkan, diserahkan, selesai.',
                422
            );
        }

        if ($role === ROLE_PETUGAS) {
            // Petugas: semua field
            $items = $this->model->getAll($filters, true);
        } else {
            // Pelapor: hanya nama_barang, waktu_temuan, status
            // filter lokasi tidak relevan untuk pelapor (field tidak ditampilkan)
            $items = $this->model->getAllForPelapor($filters, true);
        }

        ResponseHelper::success(
            ['found_items' => $items, 'total' => count($items)],
            'Data barang temuan berhasil diambil.'
        );
    }

    // ── GET /api/found-items/{id} ────────────────────────────────────────────
    public function show(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role'] ?? '';
        $id       = (int) ($GLOBALS['route_params']['id'] ?? 0);

        if ($role === ROLE_PETUGAS) {
            $item = $this->model->findById($id);
        } else {
            $item = $this->model->findByIdForPelapor($id);
        }

        if (!$item) {
            ResponseHelper::notFound('Barang temuan tidak ditemukan.');
        }

        ResponseHelper::success(
            ['found_item' => $item],
            'Detail barang temuan berhasil diambil.'
        );
    }

    // ── POST /api/found-items ────────────────────────────────────────────────
    public function store(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $input = ValidationHelper::sanitizeAll($_POST);
        } else {
            $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        }

        $errors = ValidationHelper::required($input, ['nama_barang', 'lokasi', 'waktu_temuan']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::maxLength($input['nama_barang'], 150)) {
            ResponseHelper::validationError(['nama_barang' => 'Nama barang maksimal 150 karakter.']);
        }
        if (!ValidationHelper::maxLength($input['lokasi'], 200)) {
            ResponseHelper::validationError(['lokasi' => 'Lokasi maksimal 200 karakter.']);
        }

        $waktu = \DateTime::createFromFormat('Y-m-d H:i:s', $input['waktu_temuan']);
        if (!$waktu) {
            ResponseHelper::validationError(['waktu_temuan' => 'Format waktu_temuan harus: YYYY-MM-DD HH:MM:SS']);
        }

        $fotoPath = null;
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fotoPath = $this->handleUpload($_FILES['foto']);
        }

        $id   = $this->model->create([
            'petugas_id'   => (int) $authUser['user_id'],
            'nama_barang'  => $input['nama_barang'],
            'deskripsi'    => $input['deskripsi'] ?? null,
            'lokasi'       => $input['lokasi'],
            'waktu_temuan' => $input['waktu_temuan'],
            'foto_path'    => $fotoPath,
            'status'       => 'tersimpan',
        ]);

        $item = $this->model->findById($id);

        ResponseHelper::success(
            ['found_item' => $item],
            'Barang temuan berhasil ditambahkan.',
            201
        );
    }

    // ── PUT /api/found-items/{id} ────────────────────────────────────────────
    public function update(): void
    {
        $id   = (int) ($GLOBALS['route_params']['id'] ?? 0);
        $item = $this->model->findById($id);

        if (!$item) {
            ResponseHelper::notFound('Barang temuan tidak ditemukan.');
        }

        // Tolak update jika sudah di-archive
        if ($item['status'] === 'selesai') {
            ResponseHelper::error('Barang temuan yang sudah selesai tidak dapat diubah.', 409);
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $input = ValidationHelper::sanitizeAll($_POST);
        } else {
            $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        }

        $errors = ValidationHelper::required($input, ['nama_barang', 'lokasi', 'waktu_temuan', 'status']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        // status selesai tidak boleh di-set manual lewat update — gunakan endpoint archive
        $validStatus = ['tersimpan', 'dicocokkan', 'diserahkan'];
        if (!ValidationHelper::inArray($input['status'], $validStatus)) {
            ResponseHelper::validationError([
                'status' => 'Status tidak valid. Pilihan: tersimpan, dicocokkan, diserahkan. Untuk menyelesaikan, gunakan endpoint PATCH /archive.'
            ]);
        }

        if (!ValidationHelper::maxLength($input['nama_barang'], 150)) {
            ResponseHelper::validationError(['nama_barang' => 'Nama barang maksimal 150 karakter.']);
        }
        if (!ValidationHelper::maxLength($input['lokasi'], 200)) {
            ResponseHelper::validationError(['lokasi' => 'Lokasi maksimal 200 karakter.']);
        }

        $waktu = \DateTime::createFromFormat('Y-m-d H:i:s', $input['waktu_temuan']);
        if (!$waktu) {
            ResponseHelper::validationError(['waktu_temuan' => 'Format waktu_temuan harus: YYYY-MM-DD HH:MM:SS']);
        }

        $fotoPath = $item['foto_path'];
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fotoPath = $this->handleUpload($_FILES['foto']);
            if ($item['foto_path']) {
                $oldFile = UPLOAD_PATH . basename($item['foto_path']);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }

        $this->model->update($id, [
            'nama_barang'     => $input['nama_barang'],
            'deskripsi'       => $input['deskripsi'] ?? null,
            'lokasi'          => $input['lokasi'],
            'waktu_temuan'    => $input['waktu_temuan'],
            'foto_path'       => $fotoPath,
            'catatan_selesai' => null,
            'status'          => $input['status'],
        ]);

        $updated = $this->model->findById($id);

        ResponseHelper::success(
            ['found_item' => $updated],
            'Barang temuan berhasil diperbarui.'
        );
    }

    // ── PATCH /api/found-items/{id}/archive ──────────────────────────────────
    public function archive(): void
    {
        $id   = (int) ($GLOBALS['route_params']['id'] ?? 0);
        $item = $this->model->findById($id);

        if (!$item) {
            ResponseHelper::notFound('Barang temuan tidak ditemukan.');
        }

        // Sudah di-archive sebelumnya
        if ($item['status'] === 'selesai') {
            ResponseHelper::error('Barang temuan ini sudah berstatus selesai.', 409);
        }

        // Tolak jika sedang dalam pencocokan aktif
        if ($this->model->hasActiveMatch($id)) {
            ResponseHelper::error(
                'Barang temuan tidak dapat diselesaikan karena sedang dalam proses pencocokan aktif.',
                409
            );
        }

        $input   = ValidationHelper::getInput();
        $catatan = isset($input['catatan_selesai']) ? trim($input['catatan_selesai']) : null;

        $this->model->archive($id, $catatan);

        ResponseHelper::success(
            [
                'id'              => $id,
                'status'          => 'selesai',
                'catatan_selesai' => $catatan,
            ],
            'Barang temuan berhasil diselesaikan dan diarsipkan.'
        );
    }

    // ── GET /api/found-items/selesai ──────────────────────────────────────────
    public function selesai(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role'] ?? '';

        $filters = [
            'status' => 'selesai',
            'lokasi' => isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        if ($role === ROLE_PETUGAS) {
            $items = $this->model->getAll($filters, true);
        } else {
            $items = $this->model->getAllForPelapor($filters, true);
        }

        ResponseHelper::success(
            ['found_items' => $items, 'total' => count($items)],
            'Data barang temuan yang sudah selesai berhasil diambil.'
        );
    }

    // ── GET /api/found-items/ongoing ──────────────────────────────────────────
    public function ongoing(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role     = $authUser['role'] ?? '';

        // Status yang dihitung sebagai ongoing
        // Kita tidak menggunakan filter status= karena kita ingin semua yang belum 'selesai'
        // Namun, model getAll kita saat ini defaultnya adalah deleted_at IS NULL (yang berarti belum selesai/archive)

        $filters = [
            'lokasi' => isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        if ($role === ROLE_PETUGAS) {
            $items = $this->model->getAll($filters, false); // false = ongoing only
        } else {
            $items = $this->model->getAllForPelapor($filters, false);
        }

        ResponseHelper::success(
            ['found_items' => $items, 'total' => count($items)],
            'Data barang temuan yang sedang diproses berhasil diambil.'
        );
    }

    // ── Private: Handle Upload Foto ──────────────────────────────────────────
    private function handleUpload(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            ResponseHelper::error('Upload foto gagal. Coba lagi.', 422);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            ResponseHelper::error('Ukuran foto maksimal 5 MB.', 422);
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_TYPES, true)) {
            ResponseHelper::error('Format foto tidak didukung. Gunakan JPEG, PNG, atau WebP.', 422);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'found_' . uniqid('', true) . '.' . strtolower($ext);
        $destPath = UPLOAD_PATH . $fileName;

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            ResponseHelper::error('Gagal menyimpan foto. Coba lagi.', 500);
        }

        return UPLOAD_URL . $fileName;
    }
}

