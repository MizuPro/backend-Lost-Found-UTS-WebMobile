<?php

/**
 * routes/api.php
 *
 * Format tiap route:
 * 'METHOD /path' => [ControllerClass, method, middleware[]]
 *
 * Middleware yang tersedia:
 *   'auth'           => Wajib login (AuthMiddleware)
 *   'role:petugas'   => Hanya petugas
 *   'role:pelapor'   => Hanya pelapor
 *   'role:petugas,pelapor' => Keduanya boleh
 */

return [

    // ── AUTH ─────────────────────────────────────────────────────────────────
    'POST /api/auth/register'         => ['AuthController',  'register',        []],
    'POST /api/auth/login'            => ['AuthController',  'login',           []],
    'POST /api/auth/logout'           => ['AuthController',  'logout',          ['auth']],
    'GET /api/auth/me'                => ['AuthController',  'me',              ['auth']],
    'PUT /api/auth/profile'           => ['AuthController',  'updateProfile',   ['auth']],
    'PUT /api/auth/change-password'   => ['AuthController',  'changePassword',  ['auth']],

    // ── BARANG TEMUAN (Found Items) ───────────────────────────────────────────
    'GET /api/found-items'                    => ['FoundItemController', 'index',   ['auth', 'role:petugas,pelapor']],
    'GET /api/found-items/selesai'            => ['FoundItemController', 'selesai', ['auth', 'role:petugas,pelapor']],
    'GET /api/found-items/ongoing'            => ['FoundItemController', 'ongoing', ['auth', 'role:petugas,pelapor']],
    'GET /api/found-items/{id}'               => ['FoundItemController', 'show',    ['auth', 'role:petugas,pelapor']],
    'POST /api/found-items'                   => ['FoundItemController', 'store',   ['auth', 'role:petugas']],
    'PUT /api/found-items/{id}'               => ['FoundItemController', 'update',  ['auth', 'role:petugas']],
    'PATCH /api/found-items/{id}/archive'     => ['FoundItemController', 'archive', ['auth', 'role:petugas']],

    // ── LAPORAN KEHILANGAN (Lost Reports) ────────────────────────────────────
    'GET /api/lost-reports'           => ['LostReportController', 'index',   ['auth', 'role:petugas,pelapor']],
    'GET /api/lost-reports/{id}'      => ['LostReportController', 'show',    ['auth', 'role:petugas,pelapor']],
    'POST /api/lost-reports'          => ['LostReportController', 'store',   ['auth', 'role:petugas,pelapor']],
    'PUT /api/lost-reports/{id}'      => ['LostReportController', 'update',  ['auth', 'role:petugas,pelapor']],
    'DELETE /api/lost-reports/{id}'   => ['LostReportController', 'delete',  ['auth', 'role:petugas']],

    // ── PENCOCOKAN & KLAIM (Matching & Claims) ───────────────────────────────
    'GET /api/matches'                => ['MatchController', 'index',         ['auth', 'role:petugas']],
    'GET /api/matches/{id}'           => ['MatchController', 'show',          ['auth', 'role:petugas,pelapor']],
    'POST /api/matches'               => ['MatchController', 'matchItem',     ['auth', 'role:petugas']],
    'PUT /api/matches/{id}/verify'    => ['MatchController', 'verifyClaim',   ['auth', 'role:petugas']],
    'PUT /api/matches/{id}/handover'  => ['MatchController', 'recordHandover', ['auth', 'role:petugas']],
    'PUT /api/matches/{id}/cancel'    => ['MatchController', 'cancelMatch',    ['auth', 'role:petugas']],

];
