# Backlog — CDMP Exam Simulator

Backlog implementasi MVP yang diturunkan dari `CDMP_Exam_Simulator_PRD.md`, `CDMP_Exam_Simulator_DESIGN.md`, `CDMP_Exam_Simulator_ARCHITECTURE.md`, dan `questions_ready.json`.

**Catatan status:** daftar ini adalah pemecahan pekerjaan dan kriteria penerimaan, bukan laporan status kode. Tandai task setelah implementasi terkait ditinjau dan kriterianya terpenuhi.

## Milestone 1 — Fondasi data

### DATA-01 — Validasi kontrak bank soal

Periksa JSON sebelum impor: harus berupa array berisi 193 soal siap pakai; `id` unik dan tidak kosong; pertanyaan dan minimal dua opsi tidak kosong; `correct_index` integer dalam rentang opsi; `correct_answer` cocok dengan opsi yang ditunjuk. Pertahankan teks sumber, indeks opsi, dan metadata provenance tanpa mengarang atau memperbaiki kunci.

**Selesai bila:** file yang valid lolos; JSON rusak, ID duplikat, indeks salah, atau teks kunci yang tidak cocok ditolak dengan identitas soal yang bermasalah.

### DATA-02 — Skema bank soal dan relasi

Buat migrasi dan model untuk `questions` serta `question_options`, termasuk unique key `source_id`, unique `(question_id, source_index)`, relasi, indeks, dan field provenance dari sumber.

**Selesai bila:** migrasi berjalan pada MySQL; setiap soal dapat memiliki opsi berindeks mulai dari nol dan tepat satu opsi benar.

### DATA-03 — Seeder yang aman dan idempotent

Tempatkan sumber di `database/data/questions_ready.json`; impor hanya soal `ready_source_key` dengan `needs_key_review=false`; gunakan `source_id` untuk upsert dan sinkronkan opsi dalam transaksi. Laporkan jumlah input, inserted, updated, unchanged, dan rejected. Jangan memasukkan soal review queue.

**Selesai bila:** impor pertama menghasilkan 193 soal; impor ulang tidak membuat duplikat; kegagalan validasi tidak meninggalkan impor parsial.

### DATA-04 — Skema percobaan dan snapshot

Buat tabel/model untuk `exam_attempts`, `exam_attempt_questions`, dan `exam_answers`. Snapshot harus menyimpan teks soal, opsi beserta urutan tampil dan identitas sumber, kunci jawaban untuk pemakaian server, serta metadata sumber. Jawaban dan flag terikat unik ke satu soal dalam percobaan.

**Selesai bila:** hasil dan review historis tetap dapat dibaca setelah bank soal diedit atau dihapus; kunci jawaban snapshot tidak dikirim ke browser saat ujian aktif.

## Milestone 2 — Mesin ujian

### EXAM-01 — Membuat percobaan Full Exam dan Practice

Implementasikan aturan Full Exam 100 soal dengan preset ESL 110 menit dan standard 90 menit; Practice 20 atau 50 soal dengan timer pilihan atau tanpa timer. Pilih soal eligible secara acak tanpa duplikasi dan tetapkan urutan serta snapshot dalam satu transaksi.

**Selesai bila:** jumlah soal dan deadline tersimpan di server; ujian tidak dimulai jika bank eligible kurang dari jumlah yang diminta.

### EXAM-02 — Urutan opsi yang konsisten

Acak urutan opsi bila dipakai, tetapi pertahankan `source_index` pada setiap opsi dan gunakan identitas itu untuk menyimpan serta menilai jawaban.

**Selesai bila:** pengacakan tampilan tidak mengubah jawaban benar atau hasil penilaian.

### EXAM-03 — Simpan jawaban dan flag

Sediakan penyimpanan jawaban, penghapusan jawaban, dan flag per posisi berdasarkan snapshot percobaan. Validasi bahwa indeks opsi memang ada di snapshot; serialisasikan perubahan bersamaan dengan penguncian percobaan dan beri respons status yang jelas untuk tersimpan, invalid, finalized, atau expired.

**Selesai bila:** jawaban dan flag bertahan setelah refresh; klien hanya menampilkan status tersimpan setelah server mengonfirmasi.

### EXAM-04 — Timer dan batas waktu di server

Gunakan `started_at` dan `expires_at` UTC dari server sebagai sumber kebenaran. Rekonsiliasi expiration saat membuka ujian, menyimpan jawaban, submit, dan membaca dashboard/history/result. Tolak perubahan terlambat dan finalisasi percobaan yang kedaluwarsa.

**Selesai bila:** mengubah jam/browser timer tidak memperpanjang ujian; percobaan tanpa timer hanya berlaku untuk Practice.

### EXAM-05 — Submit dan penilaian

Implementasikan submit manual satu arah dan finalisasi otomatis saat deadline. Hitung benar, salah, tidak dijawab, dan persentase terhadap seluruh soal; gunakan kunci dari snapshot. Buat operasi finalisasi transaksional dan idempotent.

**Selesai bila:** submit berulang mengembalikan hasil tersimpan yang sama; unanswered bernilai nol; total benar+salah+unanswered sama dengan jumlah soal.

## Milestone 3 — Antarmuka belajar

### UI-01 — Dashboard dan riwayat ringkas

Buat dashboard editorial yang tenang sesuai design: identitas simulator dan disclaimer bukan ujian resmi DAMA, CTA memulai, resume jika ada percobaan aktif, serta ringkasan percobaan terakhir/empty state.

**Selesai bila:** pengguna dapat memulai atau melanjutkan belajar tanpa elemen dashboard dekoratif yang tidak perlu.

### UI-02 — Setup ujian

Buat pilihan mode, jumlah soal, preset/durasi, dan ringkasan aturan deadline, navigasi, flag, serta review setelah submit. Tampilkan pesan yang jelas bila jumlah soal eligible tidak cukup.

**Selesai bila:** konfigurasi yang dikirim sesuai aturan server dan pilihan default ESL adalah 110 menit.

### UI-03 — Layar ujian dan navigator

Buat tampilan soal, pilihan jawaban, indeks, timer, status tersimpan, flag, navigasi sebelumnya/berikutnya, dan palette soal dengan status answered/unanswered/flagged. Tambahkan konfirmasi submit dengan jumlah soal belum dijawab.

**Selesai bila:** nyaman untuk sesi panjang, tetap usable di layar kecil, dan tidak menampilkan kunci atau penjelasan sebelum submit.

### UI-04 — Hasil dan review

Tampilkan skor, waktu, completion, jumlah benar/salah/kosong, serta review read-only berisi jawaban pengguna, jawaban sumber, penjelasan yang tersedia, nomor soal asli, dan halaman PDF. Tampilkan 60%, 70%, dan 80% sebagai referensi belajar saja.

**Selesai bila:** tidak membuat domain analytics atau klaim prediksi kelulusan yang tidak didukung sumber.

### UI-05 — History dan resume

Buat daftar percobaan aktif dan selesai; percobaan aktif dapat dilanjutkan, sedangkan percobaan selesai membuka hasil read-only.

**Selesai bila:** status serta tautan tiap percobaan konsisten dengan lifecycle di server.

### UI-06 — Aksesibilitas dan responsive polish

Terapkan token warna, tipografi, lebar baca, spacing, focus ring, kontras, dan tata letak desktop/mobile sesuai DESIGN. Status tidak boleh dibedakan dengan warna saja; kontrol utama harus dapat dipakai dengan keyboard.

**Selesai bila:** fokus keyboard terlihat, teks terbaca, dan navigator sticky tidak menutupi konten di mobile.

## Milestone 4 — Keamanan dan penerimaan

### QA-01 — Tes importer dan integritas data

Buat tes untuk jumlah 193, impor ulang, ID duplikat, JSON/field invalid, correct index tidak valid, mismatch `correct_answer`, dan rollback bila ada rekaman malformed.

### QA-02 — Tes lifecycle dan timer

Buat feature tests untuk Full Exam 100 soal, dua preset durasi, Practice 20/50 timed/untimed, snapshot/urutan, simpan dan hapus jawaban, flag, expiration, late write, submit idempotent, dan konsistensi hitungan hasil.

### QA-03 — Audit kebocoran kunci

Periksa HTML, Alpine state, JSON response, dan model serialization pada ujian aktif. Pastikan `is_correct`, `correct_index`, `correct_option_source_index`, serta penjelasan/korektness tidak terekspos sebelum submit.

**Selesai bila:** pemeriksaan payload aktif tidak menemukan field kunci atau penjelasan; review baru menampilkannya setelah finalisasi.

### QA-04 — Uji penerimaan manual

Ikuti skenario PRD: seed dua kali, mulai ESL/standard, jawab/flag/navigasi/refresh, submit dengan jawaban kosong, expiration, buka result/history, dan re-import tanpa mengubah review historis.

### QA-05 — Tinjau autentikasi sebelum akses jaringan

Tentukan apakah aplikasi hanya localhost. Jika dapat diakses lewat jaringan, pasang single-user authentication dan otorisasi untuk seluruh route percobaan sebelum deployment; pastikan konfigurasi produksi memakai `APP_DEBUG=false`.

## Urutan dependensi

`DATA-01` → `DATA-02` → `DATA-03` → `DATA-04` → `EXAM-01`/`EXAM-02` → `EXAM-03`/`EXAM-04` → `EXAM-05` → `UI-01`–`UI-05` → `UI-06` → `QA-01`–`QA-05`.

## Di luar MVP

Registrasi publik, pembayaran, admin CRUD/upload, AI-generated questions, koreksi otomatis kunci yang disengketakan, terjemahan, dan analitik domain tanpa klasifikasi domain yang diaudit.
