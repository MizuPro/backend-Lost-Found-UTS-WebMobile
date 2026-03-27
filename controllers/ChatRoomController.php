<?php

require_once __DIR__ . '/../models/ChatRoomModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/LostReportModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';

class ChatRoomController
{
    private $chatModel;
    private $userModel;
    private $reportModel;

    public function __construct()
    {
        $this->chatModel = new ChatRoomModel();
        $this->userModel = new UserModel();
        $this->reportModel = new LostReportModel();
    }

    public function store()
    {
        $user = $GLOBALS['auth_user'];

        if ($user['role'] !== 'petugas') {
            ResponseHelper::error("Hanya petugas yang dapat membuat chat room", 403);
            return;
        }

        $input = ValidationHelper::getInput();

        $errors = ValidationHelper::required($input, ['laporan_id']);

        if (count($errors) > 0) {
            ResponseHelper::error("Validasi gagal", 400, $errors);
            return;
        }

        $laporan_id = (int)$input['laporan_id'];
        $report = $this->reportModel->findById($laporan_id);

        if (!$report) {
            ResponseHelper::error("Laporan tidak ditemukan", 404);
            return;
        }

        $pelapor_id = $report['pelapor_id'];
        $petugas_id = $user['user_id'];

        // Check if active room already exists for this report and officer
        $existing = $this->chatModel->getRoomByLaporanAndPetugas($laporan_id, $petugas_id);
        if ($existing) {
            ResponseHelper::success($existing, "Room chat aktif sudah ada", 200);
            return;
        }

        $room = $this->chatModel->createRoom($petugas_id, $pelapor_id, $laporan_id);
        ResponseHelper::success($room, "Chat room berhasil dibuat", 201);
    }

    public function index()
    {
        $user = $GLOBALS['auth_user'];
        $rooms = $this->chatModel->getRoomsByUser($user['user_id'], $user['role']);
        
        ResponseHelper::success($rooms, "Berhasil mengambil daftar chat room");
    }

    public function endRoom($id)
    {
        $user = $GLOBALS['auth_user'];

        if ($user['role'] !== 'petugas') {
            ResponseHelper::error("Hanya petugas yang dapat mengakhiri chat room", 403);
            return;
        }

        $room = $this->chatModel->getRoomById($id);
        if (!$room) {
            ResponseHelper::error("Chat room tidak ditemukan", 404);
            return;
        }

        if ($room['petugas_id'] != $user['user_id']) {
            ResponseHelper::error("Anda tidak memiliki akses ke room ini", 403);
            return;
        }

        if ($room['status'] === 'selesai') {
            ResponseHelper::error("Chat room sudah dalam status selesai", 400);
            return;
        }

        if ($this->chatModel->endRoom($id)) {
            ResponseHelper::success(array_merge($room, ['status' => 'selesai']), "Chat room berhasil diakhiri");
        } else {
            ResponseHelper::error("Gagal mengakhiri chat room", 500);
        }
    }

    public function getFirebaseToken()
    {
        $user = $GLOBALS['auth_user'];
        $uid = (string)$user['user_id'];
        $role = $user['role'];
        $name = isset($user['name']) ? $user['name'] : 'User ' . $uid;
        
        // Simulasikan username. Jika tidak ada field username, pakai email sebelum @
        $username = isset($user['username']) ? $user['username'] : explode('@', $user['email'])[0];

        $serviceAccountPath = __DIR__ . '/../firebase-service-account.json';
        
        if (!file_exists($serviceAccountPath)) {
            ResponseHelper::error("File Service Account Firebase tidak ditemukan. Silakan tambahkan file firebase-service-account.json ke root folder.", 500);
            return;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        if (!$serviceAccount || !isset($serviceAccount['client_email']) || !isset($serviceAccount['private_key'])) {
            ResponseHelper::error("Format file Service Account Firebase tidak valid", 500);
            return;
        }

        // Generating JWT Custom Token without external libraries to guarantee it works without Composer setup
        // based on Firebase Custom Token specs (RS256)
        
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
            'iat' => time(),
            'exp' => time() + 3600, // Token valid for 1 hour
            'uid' => $uid,
            'claims' => [
                'role' => $role,
                'name' => $name,
                'username' => $username
            ]
        ];

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;
        
        $signature = '';
        $privateKey = @openssl_pkey_get_private($serviceAccount['private_key']);
        
        if (!$privateKey) {
            ResponseHelper::error("Gagal membaca Private Key dari Service Account", 500);
            return;
        }

        @openssl_sign($dataToSign, $signature, $privateKey, 'sha256WithRSAEncryption');
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $customToken = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;

        ResponseHelper::success(["firebase_token" => $customToken], "Firebase custom token berhasil di-generate");
    }
}

