<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/PickupScheduleModel.php';
require_once __DIR__ . '/../models/MatchModel.php';
require_once __DIR__ . '/../models/FoundItemModel.php';
require_once __DIR__ . '/../models/LostReportModel.php';

/**
 * PickupScheduleController
 * Menangani alur penjadwalan pengambilan barang
 *
 * Akses:
 *   index, show => petugas & pelapor (pelapor hanya data miliknya)
 *   create      => pelapor (mengajukan jadwal)
 *   review      => petugas (setujui/tolak)
 *   reschedule  => petugas (ubah jadwal aktif)
 *   cancel      => pelapor (hanya saat menunggu_persetujuan)
 *   complete    => petugas (menyelesaikan serah terima)
 */
class PickupScheduleController
{
    /** @var PickupScheduleModel */
    private $scheduleModel;
    /** @var MatchModel */
    private $matchModel;
    /** @var FoundItemModel */
    private $foundItemModel;
    /** @var LostReportModel */
    private $lostReportModel;

    public function __construct()
    {
        $this->scheduleModel = new PickupScheduleModel();
        $this->matchModel = new MatchModel();
        $this->foundItemModel = new FoundItemModel();
        $this->lostReportModel = new LostReportModel();
    }

    // ── GET /api/pickup-schedules ────────────────────────────────────────────
    public function index(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role = $authUser['role'] ?? '';
        $userId = (int) ($authUser['user_id'] ?? 0);

        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'match_id' => isset($_GET['match_id']) ? (int) $_GET['match_id'] : 0,
        ];

        $validStatus = ['menunggu_persetujuan', 'disetujui', 'ditolak', 'dibatalkan', 'selesai'];
        if ($filters['status'] !== '' && !in_array($filters['status'], $validStatus, true)) {
            ResponseHelper::error(
                'Nilai status tidak valid. Pilihan: menunggu_persetujuan, disetujui, ditolak, dibatalkan, selesai.',
                422
            );
        }

        if ($role === ROLE_PELAPOR) {
            $filters['pelapor_id'] = $userId;
        }

        $schedules = $this->scheduleModel->getAll($filters);

        ResponseHelper::success(
            ['pickup_schedules' => $schedules, 'total' => count($schedules)],
            'Data jadwal pengambilan berhasil diambil.'
        );
    }

    // ── GET /api/pickup-schedules/{id} ───────────────────────────────────────
    public function show(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role = $authUser['role'] ?? '';
        $userId = (int) ($authUser['user_id'] ?? 0);
        $id = (int) ($GLOBALS['route_params']['id'] ?? 0);

        $schedule = $this->scheduleModel->findById($id);
        if (!$schedule) {
            ResponseHelper::notFound('Jadwal pengambilan tidak ditemukan.');
        }

        if ($role === ROLE_PELAPOR && (int) $schedule['pelapor_id'] !== $userId) {
            ResponseHelper::forbidden('Anda tidak berhak melihat jadwal ini.');
        }

        ResponseHelper::success(
            ['pickup_schedule' => $schedule],
            'Detail jadwal pengambilan berhasil diambil.'
        );
    }

    // ── POST /api/pickup-schedules ───────────────────────────────────────────
    public function create(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role = $authUser['role'] ?? '';
        $userId = (int) ($authUser['user_id'] ?? 0);

        if ($role !== ROLE_PELAPOR) {
            ResponseHelper::forbidden('Hanya pelapor yang dapat mengajukan jadwal pengambilan.');
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        $errors = ValidationHelper::required($input, ['match_id', 'waktu_jadwal', 'lokasi_pengambilan']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::maxLength($input['lokasi_pengambilan'], 200)) {
            ResponseHelper::validationError(['lokasi_pengambilan' => 'Lokasi pengambilan maksimal 200 karakter.']);
        }

        if (!$this->isValidDateTime($input['waktu_jadwal'])) {
            ResponseHelper::validationError(['waktu_jadwal' => 'Format waktu_jadwal harus: YYYY-MM-DD HH:MM:SS']);
        }

        $matchId = (int) $input['match_id'];
        $match = $this->matchModel->findById($matchId);
        if (!$match) {
            ResponseHelper::notFound('Data pencocokan tidak ditemukan.');
        }

        if ((int) $match['pelapor_id'] !== $userId) {
            ResponseHelper::forbidden('Anda hanya dapat membuat jadwal untuk pencocokan milik Anda sendiri.');
        }

        if ($match['status'] !== 'diverifikasi') {
            ResponseHelper::error('Jadwal hanya bisa dibuat untuk pencocokan berstatus diverifikasi.', 409);
        }

        $activeSchedule = $this->scheduleModel->findActiveByMatchId($matchId);
        if ($activeSchedule) {
            ResponseHelper::error('Pencocokan ini sudah memiliki jadwal aktif.', 409);
        }

        $id = $this->scheduleModel->create([
            'match_id' => $matchId,
            'pelapor_id' => $userId,
            'waktu_jadwal' => $input['waktu_jadwal'],
            'lokasi_pengambilan' => $input['lokasi_pengambilan'],
            'catatan' => $input['catatan'] ?? null,
            'status' => 'menunggu_persetujuan',
        ]);

        $created = $this->scheduleModel->findById($id);
        ResponseHelper::success(
            ['pickup_schedule' => $created],
            'Pengajuan jadwal pengambilan berhasil dibuat.',
            201
        );
    }

    // ── PUT /api/pickup-schedules/{id}/review ────────────────────────────────
    public function review(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $petugasId = (int) ($authUser['user_id'] ?? 0);
        $id = (int) ($GLOBALS['route_params']['id'] ?? 0);

        $schedule = $this->scheduleModel->findById($id);
        if (!$schedule) {
            ResponseHelper::notFound('Jadwal pengambilan tidak ditemukan.');
        }

        if ($schedule['status'] !== 'menunggu_persetujuan') {
            ResponseHelper::error('Hanya jadwal berstatus menunggu_persetujuan yang dapat direview.', 409);
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        $errors = ValidationHelper::required($input, ['action']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        $action = trim($input['action']);
        if (!ValidationHelper::inArray($action, ['disetujui', 'ditolak'])) {
            ResponseHelper::validationError(['action' => 'Action tidak valid. Pilihan: disetujui atau ditolak.']);
        }

        $match = $this->matchModel->findById((int) $schedule['match_id']);
        if (!$match) {
            ResponseHelper::notFound('Data pencocokan tidak ditemukan.');
        }

        if ($action === 'disetujui' && $match['status'] !== 'diverifikasi') {
            ResponseHelper::error('Jadwal hanya dapat disetujui jika status pencocokan masih diverifikasi.', 409);
        }

        $catatan = isset($input['catatan']) ? trim($input['catatan']) : null;
        $this->scheduleModel->updateStatus($id, $action, $petugasId, $catatan);

        $updated = $this->scheduleModel->findById($id);
        ResponseHelper::success(
            ['pickup_schedule' => $updated],
            'Jadwal pengambilan berhasil direview.'
        );
    }

    // ── PUT /api/pickup-schedules/{id}/reschedule ────────────────────────────
    public function reschedule(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $petugasId = (int) ($authUser['user_id'] ?? 0);
        $id = (int) ($GLOBALS['route_params']['id'] ?? 0);

        $schedule = $this->scheduleModel->findById($id);
        if (!$schedule) {
            ResponseHelper::notFound('Jadwal pengambilan tidak ditemukan.');
        }

        if (!in_array($schedule['status'], ['menunggu_persetujuan', 'disetujui'], true)) {
            ResponseHelper::error('Hanya jadwal aktif yang dapat diubah.', 409);
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        $errors = ValidationHelper::required($input, ['waktu_jadwal', 'lokasi_pengambilan']);
        if (!empty($errors)) {
            ResponseHelper::validationError($errors);
        }

        if (!ValidationHelper::maxLength($input['lokasi_pengambilan'], 200)) {
            ResponseHelper::validationError(['lokasi_pengambilan' => 'Lokasi pengambilan maksimal 200 karakter.']);
        }

        if (!$this->isValidDateTime($input['waktu_jadwal'])) {
            ResponseHelper::validationError(['waktu_jadwal' => 'Format waktu_jadwal harus: YYYY-MM-DD HH:MM:SS']);
        }

        $catatan = isset($input['catatan']) ? trim($input['catatan']) : null;

        $this->scheduleModel->updateActive($id, [
            'waktu_jadwal' => $input['waktu_jadwal'],
            'lokasi_pengambilan' => $input['lokasi_pengambilan'],
            'catatan' => $catatan,
        ]);

        $this->scheduleModel->updateStatus($id, $schedule['status'], $petugasId, $catatan);

        $updated = $this->scheduleModel->findById($id);
        ResponseHelper::success(
            ['pickup_schedule' => $updated],
            'Jadwal pengambilan berhasil diperbarui.'
        );
    }

    // ── PUT /api/pickup-schedules/{id}/cancel ────────────────────────────────
    public function cancel(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $role = $authUser['role'] ?? '';
        $userId = (int) ($authUser['user_id'] ?? 0);
        $id = (int) ($GLOBALS['route_params']['id'] ?? 0);

        if ($role !== ROLE_PELAPOR) {
            ResponseHelper::forbidden('Hanya pelapor yang dapat membatalkan jadwal.');
        }

        $schedule = $this->scheduleModel->findById($id);
        if (!$schedule) {
            ResponseHelper::notFound('Jadwal pengambilan tidak ditemukan.');
        }

        if ((int) $schedule['pelapor_id'] !== $userId) {
            ResponseHelper::forbidden('Anda tidak berhak membatalkan jadwal ini.');
        }

        if ($schedule['status'] !== 'menunggu_persetujuan') {
            ResponseHelper::error('Jadwal hanya bisa dibatalkan pelapor saat status menunggu_persetujuan.', 409);
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        $catatan = isset($input['catatan']) ? trim($input['catatan']) : 'Dibatalkan oleh pelapor';

        $this->scheduleModel->updateStatus($id, 'dibatalkan', null, $catatan);

        $updated = $this->scheduleModel->findById($id);
        ResponseHelper::success(
            ['pickup_schedule' => $updated],
            'Jadwal pengambilan berhasil dibatalkan.'
        );
    }

    // ── PUT /api/pickup-schedules/{id}/complete ──────────────────────────────
    public function complete(): void
    {
        $authUser = $GLOBALS['auth_user'] ?? null;
        $petugasId = (int) ($authUser['user_id'] ?? 0);
        $id = (int) ($GLOBALS['route_params']['id'] ?? 0);

        $schedule = $this->scheduleModel->findById($id);
        if (!$schedule) {
            ResponseHelper::notFound('Jadwal pengambilan tidak ditemukan.');
        }

        if ($schedule['status'] !== 'disetujui') {
            ResponseHelper::error('Hanya jadwal berstatus disetujui yang dapat diselesaikan.', 409);
        }

        $match = $this->matchModel->findById((int) $schedule['match_id']);
        if (!$match) {
            ResponseHelper::notFound('Data pencocokan tidak ditemukan.');
        }

        if ($match['status'] !== 'diverifikasi') {
            ResponseHelper::error('Pencocokan harus berstatus diverifikasi untuk menyelesaikan pengambilan.', 409);
        }

        $input = ValidationHelper::sanitizeAll(ValidationHelper::getInput());
        $catatan = isset($input['catatan']) ? trim($input['catatan']) : null;
        $waktuSerah = date('Y-m-d H:i:s');

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $this->scheduleModel->updateStatus($id, 'selesai', $petugasId, $catatan, $waktuSerah);
            $this->matchModel->updateStatus((int) $schedule['match_id'], 'selesai', $catatan, $waktuSerah);

            $this->foundItemModel->archive((int) $match['barang_temuan_id'], $catatan);

            $laporan = $this->lostReportModel->findById((int) $match['laporan_id']);
            if ($laporan) {
                $this->lostReportModel->update((int) $match['laporan_id'], array_merge($laporan, ['status' => 'selesai']));
            }

            $db->commit();

            $updated = $this->scheduleModel->findById($id);
            ResponseHelper::success(
                ['pickup_schedule' => $updated],
                'Pengambilan berhasil diselesaikan.'
            );
        } catch (\Exception $e) {
            $db->rollBack();
            ResponseHelper::error('Terjadi kesalahan saat menyelesaikan pengambilan: ' . $e->getMessage(), 500);
        }
    }

    private function isValidDateTime(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $dt && $dt->format('Y-m-d H:i:s') === $value;
    }
}

