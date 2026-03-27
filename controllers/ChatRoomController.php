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
        $user = $_REQUEST['user'];

        if ($user['role'] !== 'petugas') {
            ResponseHelper::json(["message" => "Hanya petugas yang dapat membuat chat room"], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $errors = ValidationHelper::validate($input, [
            'laporan_id' => 'required|integer',
        ]);

        if (count($errors) > 0) {
            ResponseHelper::json(["message" => "Validasi gagal", "errors" => $errors], 400);
            return;
        }

        $laporan_id = $input['laporan_id'];
        $report = $this->reportModel->findById($laporan_id);

        if (!$report) {
            ResponseHelper::json(["message" => "Laporan tidak ditemukan"], 404);
            return;
        }

        $pelapor_id = $report['user_id'];
        $petugas_id = $user['id'];

        // Check if active room already exists for this report and officer
        $existing = $this->chatModel->getRoomByLaporanAndPetugas($laporan_id, $petugas_id);
        if ($existing) {
            ResponseHelper::json([
                "message" => "Room chat aktif sudah ada",
                "data" => $existing
            ], 200);
            return;
        }

        $room = $this->chatModel->createRoom($petugas_id, $pelapor_id, $laporan_id);
        ResponseHelper::json([
            "message" => "Chat room berhasil dibuat",
            "data" => $room
        ], 201);
    }

    public function index()
    {
        $user = $_REQUEST['user'];
        $rooms = $this->chatModel->getRoomsByUser($user['id'], $user['role']);
        
        ResponseHelper::json([
            "message" => "Berhasil mengambil daftar chat room",
            "data" => $rooms
        ]);
    }

    public function endRoom($id)
    {
        $user = $_REQUEST['user'];

        if ($user['role'] !== 'petugas') {
            ResponseHelper::json(["message" => "Hanya petugas yang dapat mengakhiri chat room"], 403);
            return;
        }

        $room = $this->chatModel->getRoomById($id);
        if (!$room) {
            ResponseHelper::json(["message" => "Chat room tidak ditemukan"], 404);
            return;
        }

        if ($room['petugas_id'] != $user['id']) {
            ResponseHelper::json(["message" => "Anda tidak memiliki akses ke room ini"], 403);
            return;
        }

        if ($room['status'] === 'selesai') {
            ResponseHelper::json(["message" => "Chat room sudah dalam status selesai"], 400);
            return;
        }

        if ($this->chatModel->endRoom($id)) {
            ResponseHelper::json([
                "message" => "Chat room berhasil diakhiri",
                "data" => array_merge($room, ['status' => 'selesai'])
            ]);
        } else {
            ResponseHelper::json(["message" => "Gagal mengakhiri chat room"], 500);
        }
    }

    public function getFirebaseToken()
    {
        $user = $_REQUEST['user'];
        $uid = (string)$user['id'];
        $role = $user['role'];

        $serviceAccountPath = __DIR__ . '/../firebase-service-account.json';
        
        if (!file_exists($serviceAccountPath)) {
            ResponseHelper::json([
                "message" => "File Service Account Firebase tidak ditemukan. Silakan tambahkan file firebase-service-account.json ke root folder."
            ], 500);
            return;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        if (!$serviceAccount || !isset($serviceAccount['client_email']) || !isset($serviceAccount['private_key'])) {
            ResponseHelper::json(["message" => "Format file Service Account Firebase tidak valid"], 500);
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
                'role' => $role
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
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        
        if (!$privateKey) {
            ResponseHelper::json(["message" => "Gagal membaca Private Key dari Service Account"], 500);
            return;
        }

        openssl_sign($dataToSign, $signature, $privateKey, 'sha256WithRSAEncryption');
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $customToken = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;

        ResponseHelper::json([
            "message" => "Firebase custom token berhasil di-generate",
            "firebase_token" => $customToken
        ]);
    }
}

