openapi: 3.0.3

info:
  title: CommuterLink Nusantara Lost & Found API
  description: |
    REST API untuk sistem Lost & Found CommuterLink Nusantara.
    Autentikasi menggunakan JWT Bearer Token.

    **Role yang tersedia:**
    - `petugas` — Admin/petugas yang mengelola barang temuan & pencocokan
    - `pelapor` — Penumpang yang melaporkan kehilangan
  version: 1.0.0
  contact:
    name: CommuterLink Nusantara

servers:
  - url: http://localhost/backend-Lost-Found
    description: Local Development Server (Laragon)

tags:
  - name: Auth
    description: Autentikasi dan manajemen akun pengguna
  - name: Found Items
    description: |
      Manajemen barang temuan.
      - **petugas** — dapat melakukan semua operasi (index, show, store, update, archive)
      - **pelapor** — hanya dapat melihat daftar dan detail, **dengan field terbatas**: `id`, `nama_barang`, `waktu_temuan`, `status` saja (deskripsi, lokasi, foto disembunyikan untuk mencegah klaim palsu)
  - name: Lost Reports
    description: |
      Manajemen laporan kehilangan.
      - **petugas** — dapat melakukan semua operasi (index, show, store, update, delete) pada semua laporan + melihat info pelapor lengkap
      - **pelapor** — dapat membuat laporan, melihat & update hanya laporan **miliknya sendiri**, dan menutup laporan dengan status `ditutup`

# ── Security Schemes & Reusable Components ──────────────────────────────────
components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: Token JWT didapat dari endpoint POST /api/auth/login

  schemas:

    SuccessResponse:
      type: object
      properties:
        status:
          type: string
          example: success
        message:
          type: string
          example: Berhasil
        data:
          nullable: true

    ErrorResponse:
      type: object
      properties:
        status:
          type: string
          example: error
        message:
          type: string
          example: Terjadi kesalahan
        data:
          nullable: true

    ValidationErrorResponse:
      type: object
      properties:
        status:
          type: string
          example: error
        message:
          type: string
          example: Validasi gagal.
        data:
          type: object
          description: Key adalah nama field, value adalah pesan error
          example:
            email: Format email tidak valid.
            password: Password minimal 8 karakter.

    User:
      type: object
      properties:
        id:
          type: integer
          example: 3
        name:
          type: string
          example: Budi Santoso
        email:
          type: string
          format: email
          example: budi@example.com
        role:
          type: string
          enum: [petugas, pelapor]
          example: pelapor
        created_at:
          type: string
          format: date-time
          example: "2026-03-10 08:00:00"

    FoundItem:
      type: object
      properties:
        id:
          type: integer
          example: 1
        petugas_id:
          type: integer
          example: 1
        petugas_name:
          type: string
          example: Admin Petugas
        nama_barang:
          type: string
          example: Dompet Hitam
        deskripsi:
          type: string
          nullable: true
          example: Dompet kulit warna hitam berisi KTP
        lokasi:
          type: string
          example: Stasiun Manggarai, Peron 3
        waktu_temuan:
          type: string
          format: date-time
          example: "2026-03-10 08:30:00"
        foto_path:
          type: string
          nullable: true
          example: "http://localhost/backend-Lost-Found/storage/uploads/found_abc123.jpg"
        catatan_selesai:
          type: string
          nullable: true
          description: Diisi saat barang diarsipkan via endpoint archive
          example: "Barang telah dikembalikan ke pemilik"
        status:
          type: string
          enum: [tersimpan, dicocokkan, diserahkan, selesai]
          example: tersimpan
        created_at:
          type: string
          format: date-time
          example: "2026-03-10 08:35:00"
        updated_at:
          type: string
          format: date-time
          example: "2026-03-10 08:35:00"

    FoundItemPublic:
      description: Data terbatas untuk pelapor — hanya nama dan waktu ditemukan
      type: object
      properties:
        id:
          type: integer
          example: 1
        nama_barang:
          type: string
          example: Dompet Hitam
        waktu_temuan:
          type: string
          format: date-time
          example: "2026-03-10 08:30:00"
        status:
          type: string
          enum: [tersimpan, dicocokkan, diserahkan, selesai]
          example: tersimpan

    LostReport:
      description: Data laporan kehilangan lengkap — untuk petugas
      type: object
      properties:
        id:
          type: integer
          example: 1
        pelapor_id:
          type: integer
          example: 3
        pelapor_name:
          type: string
          example: Budi Santoso
        pelapor_email:
          type: string
          format: email
          example: budi@example.com
        nama_barang:
          type: string
          example: Tas Ransel Biru
        deskripsi:
          type: string
          nullable: true
          example: Tas ransel merk Eiger warna biru navy berisi laptop
        lokasi:
          type: string
          example: KRL Commuter Line jurusan Bogor-Jakarta Kota
        waktu_hilang:
          type: string
          format: date-time
          example: "2026-03-10 07:15:00"
        status:
          type: string
          enum: [menunggu, dicocokkan, selesai, ditutup]
          example: menunggu
        created_at:
          type: string
          format: date-time
          example: "2026-03-10 08:00:00"
        updated_at:
          type: string
          format: date-time
          example: "2026-03-10 08:00:00"

    LostReportPelapor:
      description: Data laporan kehilangan milik pelapor sendiri (tanpa info pelapor)
      type: object
      properties:
        id:
          type: integer
          example: 1
        pelapor_id:
          type: integer
          example: 3
        nama_barang:
          type: string
          example: Tas Ransel Biru
        deskripsi:
          type: string
          nullable: true
          example: Tas ransel merk Eiger warna biru navy berisi laptop
        lokasi:
          type: string
          example: KRL Commuter Line jurusan Bogor-Jakarta Kota
        waktu_hilang:
          type: string
          format: date-time
          example: "2026-03-10 07:15:00"
        status:
          type: string
          enum: [menunggu, dicocokkan, selesai, ditutup]
          example: menunggu
        created_at:
          type: string
          format: date-time
          example: "2026-03-10 08:00:00"
        updated_at:
          type: string
          format: date-time
          example: "2026-03-10 08:00:00"

  responses:
    Unauthorized:
      description: Token tidak ada atau tidak valid
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            status: error
            message: Tidak terautentikasi. Silakan login terlebih dahulu.
            data: null

    Forbidden:
      description: Role tidak memiliki izin mengakses endpoint ini
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            status: error
            message: Akses ditolak. Anda tidak memiliki izin.
            data: null

    NotFound:
      description: Data tidak ditemukan
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            status: error
            message: Data tidak ditemukan.
            data: null

    ValidationError:
      description: Input tidak valid — cek field yang bermasalah di dalam data
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ValidationErrorResponse'

    Conflict:
      description: Konflik data — operasi tidak dapat dilakukan
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
          example:
            status: error
            message: Operasi tidak dapat dilakukan karena konflik data.
            data: null

# ── Paths ────────────────────────────────────────────────────────────────────
paths:

  # Health Check
  /:
    get:
      tags: [Auth]
      summary: Health check — cek apakah server API berjalan
      operationId: healthCheck
      responses:
        "200":
          description: API berjalan normal
          content:
            application/json:
              example:
                status: success
                message: API is running.
                data:
                  app: CommuterLink Nusantara Lost & Found
                  version: 1.0.0
                  status: running

  # ── Register ────────────────────────────────────────────────────────────
  /api/auth/register:
    post:
      tags: [Auth]
      summary: Registrasi akun baru
      operationId: register
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [name, email, password, role]
              properties:
                name:
                  type: string
                  example: Budi Santoso
                email:
                  type: string
                  format: email
                  example: budi@example.com
                password:
                  type: string
                  format: password
                  minLength: 8
                  example: password123
                role:
                  type: string
                  enum: [petugas, pelapor]
                  example: pelapor
      responses:
        "201":
          description: Registrasi berhasil
          content:
            application/json:
              example:
                status: success
                message: Registrasi berhasil.
                data:
                  user:
                    id: 3
                    name: Budi Santoso
                    email: budi@example.com
                    role: pelapor
                    created_at: "2026-03-10 09:00:00"
        "409":
          description: Email sudah terdaftar
          content:
            application/json:
              example:
                status: error
                message: Email sudah terdaftar. Gunakan email lain.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

  # ── Login ───────────────────────────────────────────────────────────────
  /api/auth/login:
    post:
      tags: [Auth]
      summary: Login dan dapatkan JWT token
      operationId: login
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [email, password]
              properties:
                email:
                  type: string
                  format: email
                  example: budi@example.com
                password:
                  type: string
                  format: password
                  example: password123
      responses:
        "200":
          description: Login berhasil — simpan token untuk request selanjutnya
          content:
            application/json:
              example:
                status: success
                message: Login berhasil.
                data:
                  token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
                  token_type: Bearer
                  expires_in: 86400
                  user:
                    id: 3
                    name: Budi Santoso
                    email: budi@example.com
                    role: pelapor
                    created_at: "2026-03-10 09:00:00"
        "401":
          description: Email atau password salah
          content:
            application/json:
              example:
                status: error
                message: Email atau password salah.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

  # ── Logout ──────────────────────────────────────────────────────────────
  /api/auth/logout:
    post:
      tags: [Auth]
      summary: Logout
      description: |
        JWT bersifat stateless sehingga tidak ada invalidasi di server.
        Endpoint ini memberikan konfirmasi logout.
        **Client wajib menghapus token dari storage secara mandiri.**
      operationId: logout
      security:
        - BearerAuth: []
      responses:
        "200":
          description: Logout berhasil
          content:
            application/json:
              example:
                status: success
                message: Logout berhasil. Silakan hapus token Anda.
                data: null
        "401":
          $ref: '#/components/responses/Unauthorized'

  # ── Me ──────────────────────────────────────────────────────────────────
  /api/auth/me:
    get:
      tags: [Auth]
      summary: Ambil data profil user yang sedang login
      operationId: me
      security:
        - BearerAuth: []
      responses:
        "200":
          description: Data profil berhasil diambil
          content:
            application/json:
              example:
                status: success
                message: Data profil berhasil diambil.
                data:
                  user:
                    id: 3
                    name: Budi Santoso
                    email: budi@example.com
                    role: pelapor
                    created_at: "2026-03-10 09:00:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "404":
          $ref: '#/components/responses/NotFound'

  # ── Update Profile ──────────────────────────────────────────────────────
  /api/auth/profile:
    put:
      tags: [Auth]
      summary: Update nama dan email profil
      operationId: updateProfile
      security:
        - BearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [name, email]
              properties:
                name:
                  type: string
                  example: Budi Santoso Updated
                email:
                  type: string
                  format: email
                  example: budi.baru@example.com
      responses:
        "200":
          description: Profil berhasil diperbarui
          content:
            application/json:
              example:
                status: success
                message: Profil berhasil diperbarui.
                data:
                  user:
                    id: 3
                    name: Budi Santoso Updated
                    email: budi.baru@example.com
                    role: pelapor
                    created_at: "2026-03-10 09:00:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "409":
          description: Email sudah digunakan oleh akun lain
          content:
            application/json:
              example:
                status: error
                message: Email sudah digunakan oleh akun lain.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

  # ── Change Password ─────────────────────────────────────────────────────
  /api/auth/change-password:
    put:
      tags: [Auth]
      summary: Ganti password user yang sedang login
      operationId: changePassword
      security:
        - BearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [current_password, new_password]
              properties:
                current_password:
                  type: string
                  format: password
                  example: password123
                new_password:
                  type: string
                  format: password
                  minLength: 8
                  example: newpassword456
      responses:
        "200":
          description: Password berhasil diubah — client harus login ulang
          content:
            application/json:
              example:
                status: success
                message: Password berhasil diubah. Silakan login ulang.
                data: null
        "401":
          description: Token tidak valid atau password saat ini salah
          content:
            application/json:
              example:
                status: error
                message: Password saat ini salah.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

  # ════════════════════════════════════════════════════════════════════════════
  # FOUND ITEMS — Barang Temuan
  # ════════════════════════════════════════════════════════════════════════════

  /api/found-items:
    # ── GET /api/found-items ──────────────────────────────────────────────────
    get:
      tags: [Found Items]
      summary: Ambil daftar semua barang temuan (Termasuk yang selesai)
      description: |
        Mengambil semua daftar barang temuan tanpa kecuali (termasuk yang sudah berstatus `selesai`/diarsipkan).

        **Response berbeda berdasarkan role:**
        - `petugas` — mendapat data lengkap: nama, deskripsi, lokasi, foto, catatan, petugas_name, dll
        - `pelapor` — hanya mendapat: `id`, `nama_barang`, `waktu_temuan`, `status`

        Filter via query parameter (semua opsional):
        - `status` — filter berdasarkan status barang
        - `lokasi` — pencarian parsial nama lokasi *(hanya berlaku untuk petugas)*
        - `search` — pencarian parsial nama barang
      operationId: foundItemIndex
      security:
        - BearerAuth: []
      parameters:
        - in: query
          name: status
          schema:
            type: string
            enum: [tersimpan, dicocokkan, diserahkan, selesai]
          description: Filter berdasarkan status barang
          example: tersimpan
        - in: query
          name: lokasi
          schema:
            type: string
          description: Cari berdasarkan nama lokasi — hanya untuk petugas
          example: Manggarai
        - in: query
          name: search
          schema:
            type: string
          description: Cari berdasarkan nama barang (partial match)
          example: dompet
      responses:
        "200":
          description: |
            Daftar barang temuan berhasil diambil.
            Field yang dikembalikan berbeda berdasarkan role pemanggil.
          content:
            application/json:
              examples:
                petugas:
                  summary: Response untuk petugas (full data)
                  value:
                    status: success
                    message: Data barang temuan berhasil diambil.
                    data:
                      found_items:
                        - id: 1
                          petugas_id: 1
                          petugas_name: Admin Petugas
                          nama_barang: Dompet Hitam
                          deskripsi: Dompet kulit warna hitam berisi KTP
                          lokasi: Stasiun Manggarai, Peron 3
                          waktu_temuan: "2026-03-10 08:30:00"
                          foto_path: null
                          catatan_selesai: null
                          status: tersimpan
                          created_at: "2026-03-10 08:35:00"
                          updated_at: "2026-03-10 08:35:00"
                      total: 1
                pelapor:
                  summary: Response untuk pelapor (field terbatas)
                  value:
                    status: success
                    message: Data barang temuan berhasil diambil.
                    data:
                      found_items:
                        - id: 1
                          nama_barang: Dompet Hitam
                          waktu_temuan: "2026-03-10 08:30:00"
                          status: tersimpan
                      total: 1
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "422":
          description: Nilai parameter status tidak valid
          content:
            application/json:
              example:
                status: error
                message: "Nilai status tidak valid. Pilihan: tersimpan, dicocokkan, diserahkan, selesai."
                data: null

  /api/found-items/selesai:
    # ── GET /api/found-items/selesai ──────────────────────────────────────────
    get:
      tags: [Found Items]
      summary: Ambil daftar barang temuan yang SUDAH SELESAI
      description: |
        Menampilkan hanya barang yang sudah selesai diproses atau sudah diarsipkan (`deleted_at` tidak NULL).
        Format response sama dengan `/api/found-items`.
      operationId: foundItemSelesai
      security:
        - BearerAuth: []
      parameters:
        - in: query
          name: lokasi
          schema:
            type: string
          description: Cari berdasarkan nama lokasi — hanya untuk petugas
        - in: query
          name: search
          schema:
            type: string
          description: Cari berdasarkan nama barang
      responses:
        "200":
          description: Daftar barang selesai berhasil diambil.
          content:
            application/json:
              example:
                status: success
                message: Data barang temuan yang sudah selesai berhasil diambil.
                data:
                  found_items: []
                  total: 0

  /api/found-items/ongoing:
    # ── GET /api/found-items/ongoing ──────────────────────────────────────────
    get:
      tags: [Found Items]
      summary: Ambil daftar barang temuan yang MASIH PROSES (Ongoing)
      description: |
        Menampilkan barang yang masih aktif dan belum diarsipkan (`deleted_at` adalah NULL).
        Status yang termasuk: `tersimpan`, `dicocokkan`, `diserahkan`.
      operationId: foundItemOngoing
      security:
        - BearerAuth: []
      parameters:
        - in: query
          name: lokasi
          schema:
            type: string
          description: Cari berdasarkan nama lokasi — hanya untuk petugas
        - in: query
          name: search
          schema:
            type: string
          description: Cari berdasarkan nama barang
      responses:
        "200":
          description: Daftar barang ongoing berhasil diambil.
          content:
            application/json:
              example:
                status: success
                message: Data barang temuan yang sedang diproses berhasil diambil.
                data:
                  found_items: []
                  total: 0

    # ── POST /api/found-items ─────────────────────────────────────────────────
    post:
      tags: [Found Items]
      summary: Tambah barang temuan baru
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.

        Mendukung dua format request:
        - `application/json` — tanpa foto
        - `multipart/form-data` — dengan upload foto (field name: `foto`)

        **Aturan upload foto:**
        - Format: JPEG, PNG, WebP
        - Ukuran maksimal: 5 MB
      operationId: foundItemStore
      security:
        - BearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_temuan]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Dompet Hitam
                deskripsi:
                  type: string
                  nullable: true
                  example: Dompet kulit warna hitam berisi KTP
                lokasi:
                  type: string
                  maxLength: 200
                  example: Stasiun Manggarai, Peron 3
                waktu_temuan:
                  type: string
                  description: Format wajib YYYY-MM-DD HH:MM:SS
                  example: "2026-03-10 08:30:00"
          multipart/form-data:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_temuan]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Dompet Hitam
                deskripsi:
                  type: string
                  nullable: true
                  example: Dompet kulit warna hitam berisi KTP
                lokasi:
                  type: string
                  maxLength: 200
                  example: Stasiun Manggarai, Peron 3
                waktu_temuan:
                  type: string
                  example: "2026-03-10 08:30:00"
                foto:
                  type: string
                  format: binary
                  description: File foto (JPEG/PNG/WebP, maks 5 MB)
      responses:
        "201":
          description: Barang temuan berhasil ditambahkan
          content:
            application/json:
              example:
                status: success
                message: Barang temuan berhasil ditambahkan.
                data:
                  found_item:
                    id: 1
                    petugas_id: 1
                    petugas_name: Admin Petugas
                    nama_barang: Dompet Hitam
                    deskripsi: Dompet kulit warna hitam berisi KTP
                    lokasi: Stasiun Manggarai, Peron 3
                    waktu_temuan: "2026-03-10 08:30:00"
                    foto_path: null
                    status: tersimpan
                    created_at: "2026-03-10 08:35:00"
                    updated_at: "2026-03-10 08:35:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "422":
          $ref: '#/components/responses/ValidationError'

  /api/found-items/{id}:
    # ── GET /api/found-items/{id} ─────────────────────────────────────────────
    get:
      tags: [Found Items]
      summary: Ambil detail satu barang temuan
      description: |
        **Response berbeda berdasarkan role:**
        - `petugas` — data lengkap termasuk deskripsi, lokasi, foto, dan catatan
        - `pelapor` — hanya `id`, `nama_barang`, `waktu_temuan`, `status`
      operationId: foundItemShow
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          description: ID barang temuan
          example: 1
      responses:
        "200":
          description: Detail barang temuan berhasil diambil
          content:
            application/json:
              examples:
                petugas:
                  summary: Response untuk petugas (full data)
                  value:
                    status: success
                    message: Detail barang temuan berhasil diambil.
                    data:
                      found_item:
                        id: 1
                        petugas_id: 1
                        petugas_name: Admin Petugas
                        nama_barang: Dompet Hitam
                        deskripsi: Dompet kulit warna hitam berisi KTP
                        lokasi: Stasiun Manggarai, Peron 3
                        waktu_temuan: "2026-03-10 08:30:00"
                        foto_path: null
                        catatan_selesai: null
                        status: tersimpan
                        created_at: "2026-03-10 08:35:00"
                        updated_at: "2026-03-10 08:35:00"
                pelapor:
                  summary: Response untuk pelapor (field terbatas)
                  value:
                    status: success
                    message: Detail barang temuan berhasil diambil.
                    data:
                      found_item:
                        id: 1
                        nama_barang: Dompet Hitam
                        waktu_temuan: "2026-03-10 08:30:00"
                        status: tersimpan
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "404":
          description: Barang temuan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Barang temuan tidak ditemukan.
                data: null

    # ── PUT /api/found-items/{id} ─────────────────────────────────────────────
    put:
      tags: [Found Items]
      summary: Update data barang temuan
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.

        Mendukung dua format request:
        - `application/json` — tanpa ganti foto
        - `multipart/form-data` — dengan ganti foto baru

        Jika foto baru dikirim, foto lama akan otomatis dihapus dari server.
        Jika tidak dikirim foto baru, foto lama tetap dipertahankan.
      operationId: foundItemUpdate
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_temuan, status]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Dompet Hitam Updated
                deskripsi:
                  type: string
                  nullable: true
                  example: Dompet kulit warna hitam berisi KTP dan kartu ATM
                lokasi:
                  type: string
                  maxLength: 200
                  example: Stasiun Manggarai, Peron 3
                waktu_temuan:
                  type: string
                  description: Format wajib YYYY-MM-DD HH:MM:SS
                  example: "2026-03-10 08:30:00"
                status:
                  type: string
                  enum: [tersimpan, dicocokkan, diserahkan]
                  description: Status selesai tidak dapat di-set manual — gunakan endpoint PATCH /archive
                  example: tersimpan
          multipart/form-data:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_temuan, status]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Dompet Hitam Updated
                deskripsi:
                  type: string
                  nullable: true
                lokasi:
                  type: string
                  maxLength: 200
                waktu_temuan:
                  type: string
                  example: "2026-03-10 08:30:00"
                status:
                  type: string
                  enum: [tersimpan, dicocokkan, diserahkan]
                foto:
                  type: string
                  format: binary
                  description: Foto baru opsional (JPEG/PNG/WebP, maks 5 MB)
      responses:
        "200":
          description: Barang temuan berhasil diperbarui
          content:
            application/json:
              example:
                status: success
                message: Barang temuan berhasil diperbarui.
                data:
                    found_item:
                    id: 1
                    petugas_id: 1
                    petugas_name: Admin Petugas
                    nama_barang: Dompet Hitam Updated
                    deskripsi: Dompet kulit warna hitam berisi KTP dan kartu ATM
                    lokasi: Stasiun Manggarai, Peron 3
                    waktu_temuan: "2026-03-10 08:30:00"
                    foto_path: null
                    catatan_selesai: null
                    status: tersimpan
                    created_at: "2026-03-10 08:35:00"
                    updated_at: "2026-03-10 09:00:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "404":
          description: Barang temuan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Barang temuan tidak ditemukan.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

    # ── PATCH /api/found-items/{id}/archive ───────────────────────────────────
  /api/found-items/{id}/archive:
    patch:
      tags: [Found Items]
      summary: Arsipkan barang temuan (selesaikan kasus)
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.

        Menggantikan fungsi DELETE. Barang **tidak dihapus** dari database melainkan:
        - `status` diubah menjadi `selesai`
        - `deleted_at` dicatat (soft delete)
        - `catatan_selesai` dapat diisi sebagai keterangan

        **Akan ditolak (409) jika:**
        - Barang sudah berstatus `selesai` sebelumnya
        - Barang sedang dalam pencocokan aktif (status pencocokan: `pending` / `diverifikasi`)

        Setelah diarsipkan, barang tidak akan muncul lagi di daftar.
      operationId: foundItemArchive
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      requestBody:
        required: false
        content:
          application/json:
            schema:
              type: object
              properties:
                catatan_selesai:
                  type: string
                  nullable: true
                  description: Keterangan opsional mengapa barang diselesaikan
                  example: "Barang telah dikembalikan ke pemilik pada 10 Maret 2026"
      responses:
        "200":
          description: Barang temuan berhasil diarsipkan
          content:
            application/json:
              example:
                status: success
                message: Barang temuan berhasil diselesaikan dan diarsipkan.
                data:
                  id: 1
                  status: selesai
                  catatan_selesai: "Barang telah dikembalikan ke pemilik pada 10 Maret 2026"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "404":
          description: Barang temuan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Barang temuan tidak ditemukan.
                data: null
        "409":
          description: Barang sudah selesai atau sedang dalam pencocokan aktif
          content:
            application/json:
              examples:
                sudah_selesai:
                  summary: Sudah diarsipkan sebelumnya
                  value:
                    status: error
                    message: Barang temuan ini sudah berstatus selesai.
                    data: null
                pencocokan_aktif:
                  summary: Sedang dalam pencocokan aktif
                  value:
                    status: error
                    message: Barang temuan tidak dapat diselesaikan karena sedang dalam proses pencocokan aktif.
                    data: null

  # ════════════════════════════════════════════════════════════════════════════
  # LOST REPORTS — Laporan Kehilangan
  # ════════════════════════════════════════════════════════════════════════════

  /api/lost-reports:
    # ── GET /api/lost-reports ─────────────────────────────────────────────────
    get:
      tags: [Lost Reports]
      summary: Ambil daftar laporan kehilangan
      description: |
        **Response berbeda berdasarkan role:**
        - `petugas` — mendapat semua laporan dari semua pelapor, lengkap dengan `pelapor_name` dan `pelapor_email`
        - `pelapor` — hanya mendapat laporan **miliknya sendiri**, tanpa field pelapor_name & pelapor_email

        Filter via query parameter (semua opsional):
        - `status` — filter berdasarkan status laporan
        - `lokasi` — pencarian parsial nama lokasi *(hanya berlaku untuk petugas)*
        - `search` — pencarian parsial nama barang
      operationId: lostReportIndex
      security:
        - BearerAuth: []
      parameters:
        - in: query
          name: status
          schema:
            type: string
            enum: [menunggu, dicocokkan, selesai, ditutup]
          description: Filter berdasarkan status laporan
          example: menunggu
        - in: query
          name: lokasi
          schema:
            type: string
          description: Cari berdasarkan nama lokasi — hanya untuk petugas
          example: Manggarai
        - in: query
          name: search
          schema:
            type: string
          description: Cari berdasarkan nama barang (partial match)
          example: tas
      responses:
        "200":
          description: Daftar laporan kehilangan berhasil diambil
          content:
            application/json:
              examples:
                petugas:
                  summary: Response untuk petugas (semua laporan + info pelapor)
                  value:
                    status: success
                    message: Data laporan kehilangan berhasil diambil.
                    data:
                      lost_reports:
                        - id: 1
                          pelapor_id: 3
                          pelapor_name: Budi Santoso
                          pelapor_email: budi@example.com
                          nama_barang: Tas Ransel Biru
                          deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop
                          lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota
                          waktu_hilang: "2026-03-10 07:15:00"
                          status: menunggu
                          created_at: "2026-03-10 08:00:00"
                          updated_at: "2026-03-10 08:00:00"
                      total: 1
                pelapor:
                  summary: Response untuk pelapor (hanya laporan miliknya)
                  value:
                    status: success
                    message: Data laporan kehilangan berhasil diambil.
                    data:
                      lost_reports:
                        - id: 1
                          pelapor_id: 3
                          nama_barang: Tas Ransel Biru
                          deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop
                          lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota
                          waktu_hilang: "2026-03-10 07:15:00"
                          status: menunggu
                          created_at: "2026-03-10 08:00:00"
                          updated_at: "2026-03-10 08:00:00"
                      total: 1
        "401":
          $ref: '#/components/responses/Unauthorized'
        "422":
          description: Nilai parameter status tidak valid
          content:
            application/json:
              example:
                status: error
                message: "Nilai status tidak valid. Pilihan: menunggu, dicocokkan, selesai, ditutup."
                data: null

    # ── POST /api/lost-reports ────────────────────────────────────────────────
    post:
      tags: [Lost Reports]
      summary: Buat laporan kehilangan baru
      description: |
        Dapat diakses oleh **petugas** dan **pelapor**.

        `pelapor_id` diambil otomatis dari JWT token — tidak perlu dikirim di body.
        Status awal selalu `menunggu`.
      operationId: lostReportStore
      security:
        - BearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_hilang]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Tas Ransel Biru
                deskripsi:
                  type: string
                  nullable: true
                  example: Tas ransel merk Eiger warna biru navy berisi laptop
                lokasi:
                  type: string
                  maxLength: 200
                  example: KRL Commuter Line jurusan Bogor-Jakarta Kota
                waktu_hilang:
                  type: string
                  description: Format wajib YYYY-MM-DD HH:MM:SS
                  example: "2026-03-10 07:15:00"
      responses:
        "201":
          description: Laporan kehilangan berhasil dibuat
          content:
            application/json:
              example:
                status: success
                message: Laporan kehilangan berhasil dibuat.
                data:
                  lost_report:
                    id: 1
                    pelapor_id: 3
                    pelapor_name: Budi Santoso
                    pelapor_email: budi@example.com
                    nama_barang: Tas Ransel Biru
                    deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop
                    lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota
                    waktu_hilang: "2026-03-10 07:15:00"
                    status: menunggu
                    created_at: "2026-03-10 08:00:00"
                    updated_at: "2026-03-10 08:00:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "422":
          $ref: '#/components/responses/ValidationError'

  /api/lost-reports/{id}:
    # ── GET /api/lost-reports/{id} ────────────────────────────────────────────
    get:
      tags: [Lost Reports]
      summary: Ambil detail satu laporan kehilangan
      description: |
        **Aturan akses:**
        - `petugas` — dapat melihat laporan siapapun, mendapat `pelapor_name` & `pelapor_email`
        - `pelapor` — hanya dapat melihat laporan **miliknya sendiri** (404 jika bukan miliknya)
      operationId: lostReportShow
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          description: ID laporan kehilangan
          example: 1
      responses:
        "200":
          description: Detail laporan kehilangan berhasil diambil
          content:
            application/json:
              examples:
                petugas:
                  summary: Response untuk petugas (full data + info pelapor)
                  value:
                    status: success
                    message: Detail laporan kehilangan berhasil diambil.
                    data:
                      lost_report:
                        id: 1
                        pelapor_id: 3
                        pelapor_name: Budi Santoso
                        pelapor_email: budi@example.com
                        nama_barang: Tas Ransel Biru
                        deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop
                        lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota
                        waktu_hilang: "2026-03-10 07:15:00"
                        status: menunggu
                        created_at: "2026-03-10 08:00:00"
                        updated_at: "2026-03-10 08:00:00"
                pelapor:
                  summary: Response untuk pelapor (laporan miliknya)
                  value:
                    status: success
                    message: Detail laporan kehilangan berhasil diambil.
                    data:
                      lost_report:
                        id: 1
                        pelapor_id: 3
                        nama_barang: Tas Ransel Biru
                        deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop
                        lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota
                        waktu_hilang: "2026-03-10 07:15:00"
                        status: menunggu
                        created_at: "2026-03-10 08:00:00"
                        updated_at: "2026-03-10 08:00:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "404":
          description: Laporan tidak ditemukan (atau bukan milik pelapor)
          content:
            application/json:
              example:
                status: error
                message: Laporan kehilangan tidak ditemukan.
                data: null

    # ── PUT /api/lost-reports/{id} ────────────────────────────────────────────
    put:
      tags: [Lost Reports]
      summary: Update laporan kehilangan
      description: |
        **Aturan akses:**
        - `petugas` — dapat update semua laporan, semua status tersedia: `menunggu`, `dicocokkan`, `selesai`, `ditutup`
        - `pelapor` — hanya dapat update laporan **miliknya sendiri**, status terbatas: `menunggu` atau `ditutup`

        **Laporan yang sudah berstatus `selesai` atau `ditutup` tidak dapat diubah lagi (409).**
      operationId: lostReportUpdate
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [nama_barang, lokasi, waktu_hilang, status]
              properties:
                nama_barang:
                  type: string
                  maxLength: 150
                  example: Tas Ransel Biru (Update)
                deskripsi:
                  type: string
                  nullable: true
                  example: Tas ransel merk Eiger warna biru navy berisi laptop dan charger
                lokasi:
                  type: string
                  maxLength: 200
                  example: KRL Commuter Line jurusan Bogor-Jakarta Kota, gerbong 3
                waktu_hilang:
                  type: string
                  description: Format wajib YYYY-MM-DD HH:MM:SS
                  example: "2026-03-10 07:15:00"
                status:
                  type: string
                  description: |
                    Petugas: menunggu | dicocokkan | selesai | ditutup
                    Pelapor: menunggu | ditutup
                  example: menunggu
      responses:
        "200":
          description: Laporan kehilangan berhasil diperbarui
          content:
            application/json:
              example:
                status: success
                message: Laporan kehilangan berhasil diperbarui.
                data:
                  lost_report:
                    id: 1
                    pelapor_id: 3
                    pelapor_name: Budi Santoso
                    pelapor_email: budi@example.com
                    nama_barang: Tas Ransel Biru (Update)
                    deskripsi: Tas ransel merk Eiger warna biru navy berisi laptop dan charger
                    lokasi: KRL Commuter Line jurusan Bogor-Jakarta Kota, gerbong 3
                    waktu_hilang: "2026-03-10 07:15:00"
                    status: menunggu
                    created_at: "2026-03-10 08:00:00"
                    updated_at: "2026-03-10 09:30:00"
        "401":
          $ref: '#/components/responses/Unauthorized'
        "404":
          description: Laporan tidak ditemukan (atau bukan milik pelapor)
          content:
            application/json:
              example:
                status: error
                message: Laporan kehilangan tidak ditemukan.
                data: null
        "409":
          description: Laporan sudah selesai atau ditutup — tidak dapat diubah
          content:
            application/json:
              example:
                status: error
                message: Laporan yang sudah selesai atau ditutup tidak dapat diubah.
                data: null
        "422":
          $ref: '#/components/responses/ValidationError'

    # ── DELETE /api/lost-reports/{id} ─────────────────────────────────────────
    delete:
      tags: [Lost Reports]
      summary: Hapus laporan kehilangan (hard delete)
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.

        Laporan akan **dihapus permanen** dari database.

        **Akan ditolak (409) jika:**
        - Laporan sedang dalam pencocokan aktif (status pencocokan: `pending` / `diverifikasi`)
      operationId: lostReportDelete
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          description: ID laporan kehilangan yang akan dihapus
          example: 1
      responses:
        "200":
          description: Laporan berhasil dihapus
          content:
            application/json:
              example:
                status: success
                message: Laporan kehilangan berhasil dihapus.
                data:
                  id: 1
        "401":
          $ref: '#/components/responses/Unauthorized'
        "403":
          $ref: '#/components/responses/Forbidden'
        "404":
          description: Laporan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Laporan kehilangan tidak ditemukan.
                data: null
        "409":
          description: Laporan sedang dalam pencocokan aktif — tidak dapat dihapus
          content:
            application/json:
              example:
                status: error
                message: Laporan tidak dapat dihapus karena sedang dalam proses pencocokan aktif.
                data: null


  # ----------------------------------------------------------------------------
  # MATCHING & CLAIMS � Pencocokan dan Klaim
  # ----------------------------------------------------------------------------

  /api/matches:
    # -- GET /api/matches ------------------------------------------------------
    get:
      tags: [Matches]
      summary: Ambil daftar seluruh pencocokan barang
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.
        Mendukung filter status pencocokan.
      operationId: matchIndex
      security:
        - BearerAuth: []
      parameters:
        - in: query
          name: status
          schema:
            type: string
            enum: [pending, diverifikasi, selesai, dibatalkan]
          description: Filter berdasarkan status pencocokan
          example: pending
      responses:
        "200":
          description: Daftar pencocokan berhasil diambil
          content:
            application/json:
              example:
                status: success
                message: Data pencocokan berhasil diambil.
                data:
                  matches:
                    - id: 1
                      barang_temuan_id: 1
                      laporan_id: 1
                      petugas_id: 1
                      status: pending
                      catatan: null
                      waktu_serah: null
                      created_at: "2026-03-10 09:00:00"
                      updated_at: "2026-03-10 09:00:00"
                      barang_temuan_nama: Dompet Hitam
                      laporan_nama: Dompet Hitam
                      petugas_name: Admin Petugas
                      pelapor_name: Budi Santoso
                      pelapor_email: budi@example.com
                  total: 1
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "422":
          description: Nilai parameter status tidak valid
          content:
            application/json:
              example:
                status: error
                message: "Nilai status tidak valid. Pilihan: pending, diverifikasi, selesai, dibatalkan."
                data: null

    # -- POST /api/matches -----------------------------------------------------
    post:
      tags: [Matches]
      summary: Buat pencocokan baru antara barang temuan dan laporan kehilangan
      description: |
        Hanya **petugas** yang dapat mengakses endpoint ini.
        Mengubah status barang dan laporan menjadi `dicocokkan`.
        Status pencocokan awal adalah `pending`.
      operationId: matchItem
      security:
        - BearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [barang_temuan_id, laporan_id]
              properties:
                barang_temuan_id:
                  type: integer
                  example: 1
                laporan_id:
                  type: integer
                  example: 1
      responses:
        "201":
          description: Pencocokan berhasil dibuat
          content:
            application/json:
              example:
                status: success
                message: Barang temuan dan laporan kehilangan berhasil dicocokkan (status: pending).
                data:
                  match:
                    id: 1
                    barang_temuan_id: 1
                    laporan_id: 1
                    petugas_id: 1
                    status: pending
                    catatan: null
                    waktu_serah: null
                    created_at: "2026-03-10 09:00:00"
                    updated_at: "2026-03-10 09:00:00"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          description: Barang atau laporan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Barang temuan tidak ditemukan.
                data: null
        "409":
          description: Barang/laporan sudah selesai, atau sudah ada pencocokan aktif
          content:
            application/json:
              example:
                status: error
                message: Pencocokan untuk barang dan laporan ini sudah ada (aktif).
                data: null
        "422":
          $ref: "#/components/responses/ValidationError"

  /api/matches/{id}:
    # -- GET /api/matches/{id} -------------------------------------------------
    get:
      tags: [Matches]
      summary: Ambil detail pencocokan
      description: |
        **Aturan akses:**
        - `petugas` � dapat melihat semua pencocokan
        - `pelapor` � hanya dapat melihat pencocokan jika laporannya terkait
      operationId: matchShow
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      responses:
        "200":
          description: Detail pencocokan berhasil diambil
          content:
            application/json:
              example:
                status: success
                message: Detail pencocokan berhasil diambil.
                data:
                  match:
                    id: 1
                    barang_temuan_id: 1
                    laporan_id: 1
                    petugas_id: 1
                    status: pending
                    catatan: null
                    waktu_serah: null
                    created_at: "2026-03-10 09:00:00"
                    updated_at: "2026-03-10 09:00:00"
                    barang_temuan_nama: Dompet Hitam
                    laporan_nama: Dompet Hitam
                    petugas_name: Admin Petugas
                    pelapor_name: Budi Santoso
                    pelapor_email: budi@example.com
                    pelapor_id: 3
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          description: Akses ditolak (bukan milik pelapor)
          content:
            application/json:
              example:
                status: error
                message: Akses ditolak. Anda tidak berhak melihat pencocokan ini.
                data: null
        "404":
          description: Pencocokan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Data pencocokan tidak ditemukan.
                data: null

  /api/matches/{id}/verify:
    # -- PUT /api/matches/{id}/verify ------------------------------------------
    put:
      tags: [Matches]
      summary: Verifikasi klaim pencocokan
      description: |
        Hanya **petugas**. Mengubah status pencocokan dari `pending` menjadi `diverifikasi`.
      operationId: matchVerify
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      requestBody:
        required: false
        content:
          application/json:
            schema:
              type: object
              properties:
                catatan:
                  type: string
                  example: "KTP sesuai dengan deskripsi pelapor"
      responses:
        "200":
          description: Klaim berhasil diverifikasi
          content:
            application/json:
              example:
                status: success
                message: Pencocokan berhasil diverifikasi (status: diverifikasi).
                data:
                  match:
                    id: 1
                    status: diverifikasi
                    catatan: "KTP sesuai dengan deskripsi pelapor"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          description: Pencocokan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Data pencocokan tidak ditemukan.
                data: null
        "409":
          description: Status bukan pending
          content:
            application/json:
              example:
                status: error
                message: Hanya pencocokan berstatus pending yang dapat diverifikasi.
                data: null

  /api/matches/{id}/handover:
    # -- PUT /api/matches/{id}/handover ----------------------------------------
    put:
      tags: [Matches]
      summary: Catat penyerahan barang ke pelapor
      description: |
        Hanya **petugas**.
        Mengubah status pencocokan dari `diverifikasi` menjadi `selesai`.
        Mencatat `waktu_serah`.
        Mengubah status barang temuan menjadi `diserahkan`.
        Mengubah status laporan kehilangan menjadi `selesai`.
      operationId: matchHandover
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          example: 1
      requestBody:
        required: false
        content:
          application/json:
            schema:
              type: object
              properties:
                catatan:
                  type: string
                  example: "Diserahkan langsung kepada Budi Santoso di Stasiun Manggarai"
      responses:
        "200":
          description: Barang berhasil diserahkan
          content:
            application/json:
              example:
                status: success
                message: Barang berhasil diserahkan kepada pelapor. Pencocokan selesai.
                data:
                  match:
                    id: 1
                    status: selesai
                    waktu_serah: "2026-03-10 09:30:00"
                    catatan: "KTP sesuai. Diserahkan langsung kepada Budi Santoso di Stasiun Manggarai"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          description: Pencocokan tidak ditemukan
          content:
            application/json:
              example:
                status: error
                message: Data pencocokan tidak ditemukan.
                data: null
        "409":
          description: Status bukan diverifikasi
          content:
            application/json:
              example:
                status: error
                message: Hanya pencocokan berstatus diverifikasi yang dapat diserahkan.
                data: null

