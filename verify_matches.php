<?php

$baseUrl = 'http://localhost:8123/api';

function request($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [];
    if ($data !== null) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }
    
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

$id = time();
$petugasEmail = "petugas_{$id}@test.com";
$pelaporEmail = "pelapor_{$id}@test.com";

echo "0. Register...\n";
request('POST', '/auth/register', [
    'name' => 'Test Petugas',
    'email' => $petugasEmail,
    'password' => 'password',
    'role' => 'petugas'
]);
request('POST', '/auth/register', [
    'name' => 'Test Pelapor',
    'email' => $pelaporEmail,
    'password' => 'password',
    'role' => 'pelapor'
]);


echo "1. Login...\n";
$res = request('POST', '/auth/login', [
    'email' => $petugasEmail,
    'password' => 'password'
]);
$tokenPetugas = $res['body']['data']['token'] ?? null;
if (!$tokenPetugas) {
    print_r($res['body']);
    die("Gagal login petugas\n");
}

$res = request('POST', '/auth/login', [
    'email' => $pelaporEmail,
    'password' => 'password'
]);
$tokenPelapor = $res['body']['data']['token'] ?? null;
if (!$tokenPelapor) {
    print_r($res['body']);
    die("Gagal login pelapor\n");
}

echo "2. Create Barang Temuan...\n";
$res = request('POST', '/found-items', [
    'nama_barang' => 'Dompet Hitam ' . time(),
    'lokasi' => 'Stasiun Manggarai',
    'waktu_temuan' => date('Y-m-d H:i:s'),
    'deskripsi' => 'Dompet kulit warna hitam isi KTP'
], $tokenPetugas);
$barangId = $res['body']['data']['found_item']['id'] ?? null;
echo "Barang ID: $barangId\n";

echo "3. Create Laporan Kehilangan...\n";
$res = request('POST', '/lost-reports', [
    'nama_barang' => 'Dompet Hitam',
    'lokasi' => 'Stasiun Manggarai / Sudirman',
    'waktu_hilang' => date('Y-m-d H:i:s', time() - 3600),
    'deskripsi' => 'Dompet kulit saya hilang'
], $tokenPelapor);
$laporanId = $res['body']['data']['lost_report']['id'] ?? null;
echo "Laporan ID: $laporanId\n";

if ($barangId && $laporanId) {
    echo "4. Match Item...\n";
    $res = request('POST', '/matches', [
        'barang_temuan_id' => $barangId,
        'laporan_id' => $laporanId
    ], $tokenPetugas);
    print_r($res['body']);
    $matchId = $res['body']['data']['match']['id'] ?? null;
    
    if ($matchId) {
        echo "5. Verify Claim...\n";
        $res = request('PUT', "/matches/$matchId/verify", [
            'catatan' => 'Bukti KTP cocok'
        ], $tokenPetugas);
        print_r($res['body']);
        
        echo "6. Record Handover...\n";
        $res = request('PUT', "/matches/$matchId/handover", [
            'catatan' => 'Diserahkan langsung ke pemilik'
        ], $tokenPetugas);
        print_r($res['body']);
    } else {
        echo "Match gagal dibuat.\n";
    }
} else {
    echo "Gagal membuat referensi item/report.\n";
}

echo "Selesai.\n";
