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

