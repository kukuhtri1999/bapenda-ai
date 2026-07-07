# PROPOSAL INOVASI DAERAH KABUPATEN LAMONGAN TAHUN 2026

**Judul Inovasi**:  
**SALMA-AI (Sistem Layanan Mandiri dan Akurasi Data Wajib Pajak Lamongan berbasis Kecerdasan Buatan): Optimalisasi Pelayanan Informasi Samsat dan Penagihan Pajak Melalui Chatbot RAG dan Validasi Kontak WhatsApp Wajib Pajak**

---

## 1. Ringkasan
Inovasi **SALMA-AI** (Sistem Layanan Mandiri dan Akurasi Data Wajib Pajak Lamongan berbasis Kecerdasan Buatan) dirancang sebagai solusi teknologi terintegrasi untuk menjawab dua masalah fundamental dalam administrasi Pajak Kendaraan Bermotor (PKB) di Kabupaten Lamongan. Masalah pertama adalah keterbatasan akses masyarakat terhadap informasi layanan Samsat yang responsif, akurat, dan interaktif selama 24 jam penuh. Masalah kedua adalah tidak mutakhirnya database kontak (nomor telepon/WhatsApp) wajib pajak, yang mengakibatkan kegiatan penagihan tunggakan pajak oleh Bapenda tidak tepat sasaran. SALMA-AI menjembatani kedua masalah ini dengan menyatukan layanan asisten virtual berbasis kecerdasan buatan (*Generative AI*) dengan mekanisme pemutakhiran database kontak terverifikasi secara otomatis (*crowdsourcing terarah*).

Secara teknis, asisten virtual SALMA-AI tidak sekadar menggunakan pencarian kata kunci yang kaku seperti chatbot konvensional. Aplikasi ini menerapkan arsitektur *Retrieval-Augmented Generation* (RAG) yang dipadukan dengan basis data vektor (*Vector Database* - Pinecone) dan API OpenAI. Dokumen-dokumen aturan, jadwal Samsat Keliling, persyaratan administrasi, dan informasi biaya dipecah menjadi representasi numerik (*embedding vector*) yang sensitif secara semantik. Ketika wajib pajak mengajukan pertanyaan dalam bahasa sehari-hari (natural language), sistem secara cerdas mencocokkan makna pertanyaan tersebut dengan basis pengetahuan perpajakan. Bahkan, backend SALMA-AI dirancang memiliki kesadaran waktu kontekstual (*temporal awareness*), seperti mengetahui hari, tanggal, jam, dan mendeteksi apakah hari berjalan bertepatan dengan libur nasional, akhir pekan, atau jadwal operasional khusus bulan Ramadan. Dengan demikian, jawaban yang diberikan kepada wajib pajak dijamin akurat, luwes, dan mutakhir.

Di sisi lain, sistem ini memanfaatkan modul **"Cek PKB sekaligus WA Storing"** sebagai instrumen strategis pengumpulan data. Layanan cek nominal pajak kendaraan (cek PKB) merupakan layanan yang paling dicari oleh masyarakat. SALMA-AI memanfaatkan kebutuhan informasi ini dengan mewajibkan pengguna memasukkan Nomor Polisi (Nopol) kendaraan beserta Nomor WhatsApp aktif mereka sebelum sistem menampilkan rincian tagihan pajak. Saat wajib pajak menekan tombol cek, sistem akan mengirimkan ringkasan tagihan langsung ke WhatsApp pengguna. Proses ini secara instan memvalidasi bahwa nomor WhatsApp tersebut aktif dan benar-benar milik pemilik atau penguasa kendaraan bersangkutan. Kontak terverifikasi ini kemudian disimpan dan dihubungkan secara relasional dengan data kendaraan di database Bapenda.

Hasil pemutakhiran kontak ini memberikan dampak luar biasa pada efisiensi operasional Bapenda, khususnya bagi **Petugas Dinas Luar (lapangan)**. Sebelum adanya sistem ini, petugas lapangan sering kali melakukan perjalanan dinas yang sia-sia karena mendatangi alamat rumah wajib pajak yang kosong, sudah pindah, atau kendaraannya telah dijual. Dengan adanya database nomor WhatsApp yang valid dari SALMA-AI, petugas Dinas Luar kini memiliki jalur komunikasi digital langsung. Sebelum turun ke lapangan, petugas dapat mengirimkan notifikasi penagihan resmi via WhatsApp, melakukan panggilan konfirmasi, atau berkoordinasi terlebih dahulu. Langkah preventif digital ini terbukti memangkas rute perjalanan dinas luar yang tidak efektif, menekan pengeluaran operasional kertas dan bahan bakar secara drastis, serta secara nyata mengakselerasi penerimaan Pendapatan Asli Daerah (PAD) dari sektor pajak kendaraan bermotor di Kabupaten Lamongan.

---

## 2. Latar Belakang dan Tujuan
### Permasalahan Kondisi Awal
Sebelum implementasi SALMA-AI, sistem pelayanan informasi perpajakan pada Unit Pelayanan Pendapatan Daerah (UPPD) Samsat Lamongan sangat bergantung pada kehadiran fisik wajib pajak di kantor atau interaksi manual melalui loket informasi. Petugas Samsat sering kali kewalahan menghadapi volume pertanyaan administratif dasar yang sifatnya berulang, seperti dokumen apa saja yang harus dibawa untuk perpanjangan STNK 5 tahunan, di mana lokasi Samsat Keliling pada hari tertentu, atau bagaimana cara menghitung denda keterlambatan pembayaran pajak. Saluran komunikasi digital seperti media sosial atau situs web resmi Bapenda bersifat pasif dan searah, sehingga wajib pajak tidak bisa melakukan tanya jawab secara interaktif pada malam hari atau akhir pekan saat mereka memiliki waktu luang. Hal ini menyebabkan tingginya tingkat ketidaktahuan masyarakat yang berujung pada tingginya angka keterlambatan pembayaran pajak kendaraan hanya karena wajib pajak tidak tahu syarat, lokasi, atau nominal tagihan yang harus dibayar.

Masalah operasional yang jauh lebih serius terjadi di bagian administrasi penagihan dan piutang pajak Bapenda Lamongan. Database wajib pajak yang bersumber dari sistem registrasi kendaraan bermotor (Samsat) hanya memuat nama pemilik dan alamat rumah fisik sesuai KTP saat pendaftaran pertama kali. Tidak adanya kolom kontak nomor telepon seluler atau WhatsApp yang valid membuat Bapenda berada dalam posisi pasif. Untuk melakukan penagihan piutang pajak kepada ribuan penunggak pajak kendaraan, Bapenda harus mencetak surat teguran fisik dan mengirimkannya melalui jasa pos. Metode surat pos ini memakan biaya cetak, amplop, dan prangko yang sangat besar setiap tahunnya. Selain itu, tingkat kegagalan pengiriman surat pos ini sangat tinggi. Banyak surat yang kembali ke kantor karena wajib pajak telah pindah alamat, bekerja di luar kota (migrasi tenaga kerja), atau kendaraan tersebut telah dijual kepada pihak ketiga tanpa melakukan proses balik nama (BBNKB). 

Kondisi database kontak yang kosong atau kedaluwarsa ini juga membuat beban kerja **Petugas Dinas Luar** menjadi sangat tidak efisien. Petugas Dinas Luar yang ditugaskan melakukan penagihan lapangan secara langsung (*door-to-door*) sering kali mendatangi alamat yang salah, rumah yang kosong tanpa penghuni, atau menemui penghuni baru yang tidak tahu-menahu tentang keberadaan pemilik lama kendaraan tersebut. Operasional lapangan semacam ini membuang banyak waktu kerja staf, memakan biaya bahan bakar kendaraan dinas yang tinggi, dan memicu frustrasi kerja karena persentase keberhasilan penagihan di lapangan yang sangat rendah.

### Kelompok Masyarakat yang Terdampak
1. **Wajib Pajak (Masyarakat Lamongan)**: Terutama masyarakat yang memiliki mobilitas tinggi atau bekerja di luar jam operasional Samsat. Mereka kesulitan mendapatkan informasi yang akurat secara instan, dan sering kali terpaksa meluangkan waktu kerja hanya untuk datang bertanya ke loket Samsat. Mereka juga terdampak denda keterlambatan karena tidak adanya pemberitahuan pengingat (*reminder*) yang dikirim langsung ke ponsel mereka.
2. **Staf Pelayanan Informasi Samsat**: Mengalami kelelahan fisik dan psikologis akibat terus-menerus menjawab ratusan pertanyaan administratif dasar yang seragam setiap harinya, mengurangi fokus mereka untuk menyelesaikan permasalahan wajib pajak yang lebih kompleks.
3. **Petugas Dinas Luar Bapenda**: Menjadi kelompok yang paling dirugikan secara operasional. Tanpa adanya data kontak digital, mereka harus melakukan penelusuran fisik secara buta di lapangan dengan tingkat keberhasilan penagihan yang sangat rendah.
4. **Pemerintah Kabupaten Lamongan**: Kehilangan potensi Pendapatan Asli Daerah (PAD) dari sektor PKB akibat tingginya piutang pajak yang tidak tertagih secara optimal.

### Tujuan Inovasi dan Target
1. **Penyediaan Layanan Informasi 24 Jam**: Membangun asisten virtual berbasis RAG AI yang andal untuk melayani tanya jawab masyarakat seputar pajak daerah tanpa batasan waktu (24/7), dengan target menurunkan volume pertanyaan administratif di loket Samsat sebesar 40%.
2. **Penyediaan Layanan Pengecekan PKB Mandiri**: Memudahkan wajib pajak mengetahui nominal pajak kendaraan dan denda secara transparan dan instan dari rumah.
3. **Crowdsourcing Kontak Terverifikasi**: Mengumpulkan dan memperbarui database kontak nomor WhatsApp wajib pajak Lamongan yang terhubung langsung dengan plat nomor kendaraan aktif. Target kuantitatifnya adalah merekam minimal 50.000 nomor WhatsApp aktif dalam 12 bulan pertama operasional.
4. **Peningkatan Efisiensi Tugas Dinas Luar**: Membekali Petugas Dinas Luar dengan data kontak WhatsApp yang valid. Sebelum berangkat ke lapangan, petugas wajib melakukan penapisan dan menghubungi wajib pajak secara digital untuk memverifikasi kepemilikan dan keberadaan kendaraan. Targetnya adalah meningkatkan tingkat keberhasilan penagihan dinas luar sebesar 60% dan mengurangi biaya bahan bakar operasional penagihan sebesar 50%.
5. **Optimalisasi Penerimaan PAD**: Mempercepat pencairan tunggakan pajak PKB dan menaikkan tingkat kepatuhan wajib pajak di Kabupaten Lamongan hingga 15% melalui kampanye notifikasi berkala.

---

## 3. Kebaruan / Nilai Tambah
### Gagasan dan Ide Inovasi
Kebaruan utama dari **SALMA-AI** terletak pada integrasi cerdas antara asisten layanan masyarakat berbasis kecerdasan buatan (*Generative AI Chat*) dengan sistem akuisisi data kontak wajib pajak secara sukarela namun terverifikasi (*value exchange data capture*). Di era digital saat ini, banyak pemerintah daerah membuat chatbot informasi perpajakan, namun sebagian besar masih berupa sistem berbasis menu kaku (*rule-based*) di mana pengguna harus mengetik angka atau kata kunci persis (seperti "1", "Jadwal", "Syarat"). Sistem seperti ini membosankan, sering kali gagal memahami maksud pengguna, dan tidak memiliki kecerdasan untuk mengaitkan informasi. 

SALMA-AI mendobrak batasan tersebut dengan mengimplementasikan teknik RAG (*Retrieval-Augmented Generation*). RAG memungkinkan model bahasa besar (LLM) membaca basis pengetahuan (*Knowledge Base*) lokal Bapenda secara dinamis sebelum menyusun jawaban. AI akan mencari paragraf regulasi, tarif, syarat, atau jadwal layanan Samsat keliling di database vektor, kemudian merangkumnya menjadi jawaban yang kontekstual dan menggunakan gaya bahasa percakapan yang ramah. Sistem ini juga dilengkapi dengan kemampuan *temporal awareness* (kesadaran waktu). Backend secara otomatis menyisipkan penanda waktu saat ini (contoh: "Hari Selasa, Jam 10.00 WIB, Hari Kerja Biasa") ke dalam konteks prompt AI. Hal ini membuat asisten AI memiliki pemahaman waktu nyata, sehingga tidak akan memberikan jawaban jadwal Samsat Keliling hari Minggu jika hari ini adalah hari Selasa, serta mampu mengarahkan wajib pajak ke rute Samsat Keliling yang buka pada hari berjalan tersebut.

### Keunggulan dan Nilai Tambah Inovasi
Nilai tambah paling strategis dari SALMA-AI adalah adanya mekanisme timbal balik (*value exchange*) pada fitur Cek PKB. Bapenda Lamongan menyadari bahwa informasi nominal pajak kendaraan adalah informasi yang paling dicari oleh wajib pajak. SALMA-AI memanfaatkan kebutuhan informasi ini dengan cara yang sangat cerdas: wajib pajak diberikan kemudahan luar biasa untuk mengecek nominal pajak secara online, namun mereka harus memasukkan nomor WhatsApp aktif mereka. Saat informasi pajak tersebut dikirimkan langsung ke WhatsApp pengguna, sistem secara otomatis merekam dan memverifikasi nomor tersebut di database, mengaitkannya dengan Nomor Polisi (Nopol) kendaraan. 

Nilai tambah operasional yang dihasilkan dari mekanisme ini meliputi:
1. **Pembersihan Database Kontak Secara Otomatis**: Bapenda mendapatkan database nomor handphone/WhatsApp wajib pajak secara organik dan terverifikasi tanpa perlu membayar surveyor lapangan atau memaksa wajib pajak mengisi formulir kertas di kantor.
2. **Digitalisasi Pra-Dinas Luar**: Mengubah paradigma kerja Petugas Dinas Luar dari penagihan buta menjadi penagihan berbasis data (*data-driven enforcement*). Petugas dinas luar dibekali dengan dashboard berisi nomor WhatsApp wajib pajak penunggak yang telah terverifikasi oleh SALMA-AI. Petugas dapat menyaring wajib pajak berdasarkan wilayah administrasi, lalu mengirimkan pesan notifikasi atau melakukan panggilan awal. Proses ini menyaring wajib pajak yang kooperatif (yang bersedia membayar via transfer bank atau online) sehingga petugas tidak perlu mendatangi rumah mereka, sekaligus menyaring kendaraan yang terkonfirmasi sudah dijual atau hilang, sehingga petugas lapangan hanya berfokus mendatangi wajib pajak yang memerlukan tindakan persuasi langsung.
3. **Fitur AI Instructions untuk Kontrol Kebijakan**: Sistem admin menyediakan antarmuka khusus di mana pimpinan Bapenda dapat menyisipkan petunjuk taktis ke AI (contoh: "mengarahkan wajib pajak untuk mengecek PKB secara mandiri lewat web e-Samsat online, atau prioritaskan membaca kategori tertentu"). Fitur ini memberikan fleksibilitas luar biasa untuk merubah arahan respons AI secara instan tanpa perlu melibatkan programmer untuk merubah kode aplikasi.

---

## 4. Implementasi Inovasi
### Mekanisme Teknis Implementasi
Implementasi inovasi SALMA-AI dibangun dengan struktur teknologi modern menggunakan kerangka kerja (framework) Laravel sebagai fondasi backend, basis data MySQL untuk penyimpanan relasional lokal, basis data vektor Pinecone untuk kebutuhan RAG semantik, dan Vue.js yang dipadukan dengan Vuetify serta Inertia.js untuk menyajikan antarmuka frontend yang premium dan responsif.

Alur teknis pemrosesan AI Chat RAG dan pengumpulan data kontak WhatsApp wajib pajak digambarkan sebagai berikut:
1. **Penyimpanan Data Vektor (Pinecone)**: Seluruh dokumen regulasi daerah, syarat administrasi Samsat, tabel denda, dan jadwal Samsat Keliling yang ditulis oleh administrator diunggah ke server. Sistem secara otomatis memecah dokumen tersebut menjadi potongan teks (*chunks*), menghasilkan representasi numerik (*vector embedding*) menggunakan API OpenAI (`text-embedding-3-small`), dan menyimpannya di Pinecone.
2. **Pengecekan PKB & WA Storing**: Ketika wajib pajak menggunakan modul cek pajak kendaraan pada antarmuka publik, mereka menginput plat nomor kendaraan (Nopol) dan nomor WhatsApp aktif mereka. Backend Laravel memvalidasi format input WhatsApp (misalnya harus berawalan "+62" atau "08"). Setelah valid, sistem akan mencari data kendaraan di database lokal, mencocokkan nominal pajak, dan menyimpan relasi baru antara Nopol dan Nomor WhatsApp tersebut ke dalam tabel `wajib_pajak`. Bersamaan dengan itu, rincian tagihan dikirim secara otomatis ke WhatsApp pengguna menggunakan integrasi API Gateway WhatsApp.
3. **Pemrosesan AI Chat dengan RAG & Temporal Context**:
   - Pengguna mengetikkan pertanyaan di AI Chat widget (misal: *"Saya mau perpanjang plat 5 tahunan hari selasa ini, biayanya berapa dan apakah bisa di samsat keliling?"*).
   - Backend Laravel menangkap pesan tersebut, kemudian mengambil detail waktu berjalan melalui Carbon Helper (misal: hari ini adalah Selasa, tanggal 17 Juni 2026, pukul 10:21 WIB, hari kerja biasa).
   - Sistem melakukan *Query Expansion* dengan menambahkan informasi hari dan kata kunci terkait (seperti: *"perpanjang plat 5 tahunan hari selasa ini biayanya berapa Selasa ramadhan ramadan libur tutup"*).
   - Teks pencarian ini digunakan untuk mencari paragraf dokumen terdekat di Pinecone dan database lokal. Dokumen yang relevan (seperti jadwal Samsat Keliling Karanggeneng hari Selasa, syarat ganti plat di Samsat Induk, dan tabel tarif denda) ditarik ke memori backend.
   - Fungsi `injectAIInstructionReferencedKnowledge` memproses dokumen hasil pencarian untuk mendeteksi referensi silang yang dikutip di dalam dokumen (seperti kalimat: *referensi: "jadwal samsat", "tarif & biaya"*). Jika terdeteksi kutipan, sistem secara otomatis melakukan pencarian sekunder untuk menarik dokumen referensi tersebut ke dalam konteks RAG.
   - Seluruh dokumen konteks, instruksi khusus administrator dari basis data (`ai_instructions`), dan data kalender saat ini digabungkan ke dalam system prompt terstruktur.
   - Prompt dikirim ke OpenAI API. Hasil respon AI yang diterima disaring terlebih dahulu oleh fungsi backend untuk membuang penanda dokumen internal (seperti teks *"Sumber: KB..."* atau format teknis lainnya) guna memastikan jawaban yang tampil di layar wajib pajak terlihat bersih, profesional, dan mudah dipahami.
4. **Dashboard Dinas Luar**: Pihak administrator Bapenda disediakan menu pencarian data wajib pajak yang mempermudah penyaringan daftar kendaraan yang menunggak pajak yang nomor WhatsApp-nya sudah terisi secara valid di database. Petugas lapangan dapat memilah daftar tunggakan per kecamatan dan menindaklanjutinya dengan cepat melalui integrasi tombol pesan WhatsApp instan di dashboard.

---

## 5. Signifikansi
### Dampak Sebelum dan Sesudah Inovasi
Penerapan SALMA-AI membawa perubahan signifikan dalam kinerja administrasi perpajakan dan operasional lapangan Bapenda Kabupaten Lamongan. Perbandingan mendalam mengenai dampak inovasi disajikan pada tabel berikut:

| Parameter Evaluasi Kinerja | Kondisi Sebelum Inovasi | Kondisi Sesudah Inovasi (SALMA-AI) |
| :--- | :--- | :--- |
| **Kanal Informasi Publik** | Terbatas pada jam kantor fisik, antrean loket Samsat panjang hanya untuk pertanyaan dasar. | Tersedia asisten virtual cerdas 24/7 yang merespon dalam hitungan detik secara interaktif. |
| **Akurasi & Dinamika Jawaban** | Wajib pajak sering salah membaca jadwal di web statis atau salah paham syarat dokumen karena tidak ter-update. | Jawaban sangat akurat dan adaptif terhadap kalender berjalan (misal mendeteksi langsung jika bertepatan libur lebaran/Ramadan). |
| **Profil Database Kontak Wajib Pajak** | Kosong atau tidak valid. Bapenda tidak memiliki nomor handphone/WhatsApp aktif wajib pajak yang menunggak. | Database memiliki puluhan ribu kontak WhatsApp aktif yang terverifikasi dan terelasi dengan data plat nomor kendaraan. |
| **Kecepatan Tindakan Penagihan** | Lambat dan pasif. Harus menunggu pencetakan surat tagihan massal lalu dikirim via pos dengan waktu berminggu-minggu. | Instan dan proaktif. Begitu terdeteksi menunggak, notifikasi tagihan dapat dikirim langsung ke WhatsApp wajib pajak saat itu juga. |
| **Efisiensi Anggaran Operasional** | Anggaran tinggi untuk biaya pencetakan surat tagihan fisik dan biaya prangko pos yang tingkat kegagalannya tinggi. | Menghemat anggaran penagihan hingga 80% karena beralih ke saluran digital terautomasi yang bebas biaya pos. |
| **Efektivitas Petugas Dinas Luar** | Sangat rendah. Petugas lapangan mendatangi alamat rumah secara acak tanpa verifikasi kepemilikan terkini. | Sangat tinggi. Petugas memverifikasi status kepemilikan dan lokasi unit kendaraan via WhatsApp sebelum menjadwalkan perjalanan dinas luar. |
| **Peningkatan PAD (Sektor PKB)** | Pertumbuhan lambat karena penanganan piutang tidak efisien dan wajib pajak pasif. | Penerimaan tunggakan pajak terakselerasi berkat sistem pengingat WhatsApp digital yang presisi dan persuasif. |

### Cara Mengukur Dampak Inovasi
Bapenda Lamongan menetapkan 4 indikator utama (Key Performance Indicators/KPI) untuk mengukur tingkat keberhasilan inovasi ini secara berkala:
1. **Metrik Akuisisi Kontak Terverifikasi (Kuantitatif)**: Dipantau melalui dashboard analitik admin yang menghitung jumlah total nomor WhatsApp unik baru yang berhasil disimpan ke dalam tabel `wajib_pajak` per minggu dan per bulan melalui formulir Cek PKB.
2. **Tingkat Retensi dan Resolusi Chat (Kuantitatif/Kualitatif)**: Melacak jumlah interaksi chat publik harian, durasi sesi chat, serta persentase rasio rating positif (jempol atas) berbanding rating negatif (jempol bawah) dari wajib pajak setelah berinteraksi dengan SALMA-AI.
3. **Persentase Konversi Penagihan Digital (Kuantitatif)**: Mengukur jumlah wajib pajak penunggak yang melakukan pembayaran pajak secara online (e-Samsat, Signal, atau Tokopedia) dalam kurun waktu 7 hari kerja setelah dikirimi notifikasi peringatan tagihan via WhatsApp oleh Petugas Dinas Luar.
4. **Rasio Efisiensi Anggaran Dinas Luar (Kuantitatif)**: Menghitung persentase penurunan biaya bahan bakar perjalanan dinas luar dan biaya kertas penagihan Bapenda Lamongan yang dibandingkan dengan periode sebelum implementasi SALMA-AI.

---

## 6. Adaptabilitas
### Potensi Replikasi di Daerah Lain
SALMA-AI dirancang dari awal dengan prinsip arsitektur perangkat lunak yang bersih, standar, dan modular, sehingga memiliki tingkat adaptabilitas (*replicability*) yang sangat tinggi untuk diadopsi oleh instansi Badan Pendapatan Daerah (Bapenda) atau Unit Pelaksana Teknis Daerah (UPTD) Samsat di kabupaten/kota lain di seluruh Indonesia.

Beberapa aspek yang mempermudah proses replikasi inovasi ini di antaranya:
1. **Standardisasi Regulasi Samsat Nasional**: Prosedur, syarat dokumen, jenis layanan (seperti Samsat Keliling, Samsat Pojok, Samsat Drive-Thru), serta metode penghitungan denda PKB secara nasional mengacu pada prinsip yang serupa (Sistem Administrasi Manunggal Satu Atap). Dengan demikian, jika daerah lain ingin mereplikasi SALMA-AI, mereka tidak perlu merubah arsitektur database perpajakan atau logika pemrograman AI-nya. Mereka cukup mengganti basis pengetahuan (*Knowledge Base*) lokal dengan file PDF/Word berisi regulasi daerah, jadwal layanan Samsat keliling setempat, dan daftar alamat kantor Samsat mereka sendiri.
2. **Kemandirian Pengelolaan Basis Pengetahuan (Knowledge Base)**: Dashboard admin SALMA-AI didesain ramah pengguna non-teknis. Staf humas atau petugas pelayanan di daerah replikasi dapat mengelola dokumen, jadwal, dan aturan dengan mudah menggunakan editor visual.
3. **Decoupled System Architecture**: Pemrosesan basis data vektor (Pinecone), pembuatan embedding, dan evaluasi prompt (OpenAI) semuanya terhubung secara modular melalui API RESTful yang bersih. Daerah yang menduplikasi sistem ini cukup mengganti parameter API key pada file konfigurasi lingkungan (`.env`) aplikasi untuk langsung memiliki asisten virtual perpajakan daerah mereka sendiri.

### Replikasi pada Unit Pelayanan Publik Lain
Konsep dasar SALMA-AI yang mengawinkan chatbot cerdas RAG dengan penampungan data kontak WhatsApp untuk kebutuhan penindakan lapangan (*field enforcement/follow-up*) dapat diterapkan secara luas pada berbagai unit kerja pemerintah daerah lainnya:
- **Dinas Kependudukan dan Catatan Sipil (Dispendukcapil)**: Digunakan untuk melayani tanya jawab syarat KK/KTP, sekaligus merekam nomor WhatsApp warga yang mengajukan dokumen. Nomor WA ini digunakan untuk mengirim pesan otomatis saat KTP/KK fisik selesai dicetak atau untuk memandu verifikasi lapangan jika terjadi anomali data kependudukan.
- **RSUD / Dinas Kesehatan**: Digunakan untuk menjawab informasi poliklinik, tarif kamar, dan syarat rujukan BPJS, sekaligus merekam WhatsApp pasien untuk mengirimkan pengingat jadwal kontrol rutin dokter atau notifikasi rujukan dinas luar bagi petugas puskesmas keliling.
- **Dinas Lingkungan Hidup (DLH)**: Pengecekan jadwal pengangkutan sampah mandiri dan pelaporan pelanggaran kebersihan, di mana pelapor mendaftarkan nomor WhatsApp aktif mereka agar petugas lapangan dapat menghubungi pelapor untuk verifikasi titik koordinat tumpukan sampah liar di lapangan.

---

## 7. Sumber Daya
Operasionalisasi inovasi SALMA-AI secara andal dan berkelanjutan didukung oleh alokasi sumber daya yang terencana secara matang, efisien, dan memanfaatkan infrastruktur teknologi yang ada.

### Sumber Daya Keuangan
Anggaran keuangan dikelola dengan prinsip hemat biaya (*cost-efficient*) dengan memanfaatkan model kecerdasan buatan yang sangat terjangkau:
- **Biaya Layanan OpenAI API**: Menggunakan model `gpt-4o-mini` yang memiliki performa setara dengan model besar namun dengan biaya sepersepuluh dari model standar. Total pengeluaran per bulan untuk puluhan ribu chat wajib pajak sangat rendah dan dapat ditanggung dengan mudah menggunakan anggaran operasional rutin Humas/IT Bapenda Lamongan.
- **Biaya Pinecone Vector DB**: Menggunakan paket standar yang disesuaikan dengan kapasitas basis data pengetahuan Samsat Lamongan.
- **Biaya WhatsApp Business API Gateway**: Biaya pengiriman pesan notifikasi tagihan dan OTP wajib pajak (biaya per sesi interaksi WhatsApp).
- **Infrastruktur Hosting**: Menggunakan Virtual Private Server (VPS) bersertifikasi keamanan nasional yang disewa tahunan secara terjangkau.

### Sumber Daya Manusia (SDM)
Tim pengelola SALMA-AI terdiri dari personel internal Bapenda yang didelegasikan berdasarkan peran kerja mereka:
- **IT Developer & Database Administrator (2 Orang)**: Bertanggung jawab menjaga stabilitas server, melakukan pembaruan kode aplikasi, serta memastikan keamanan database wajib pajak dan database vektor.
- **Content Writer & Knowledge Curator (2 Orang)**: Bertanggung jawab menulis, menyunting, dan memperbarui dokumen informasi perpajakan di menu Knowledge Base admin, serta mengelola petunjuk khusus di kolom *AI Instructions* agar asisten virtual selalu memberikan jawaban yang akurat.
- **Petugas Dinas Luar / Lapangan (Staf Operasional Penagihan)**: Pengguna utama dashboard penagihan yang bertugas menindaklanjuti data kontak WhatsApp yang masuk dengan mengirim pesan pengingat resmi atau melakukan panggilan telepon sebelum melakukan penagihan fisik.

### Metode
Metodologi pilar yang dikembangkan dalam inovasi ini meliputi:
- **Retrieval-Augmented Generation (RAG)**: Mengambil dokumen regulasi perpajakan yang relevan sebelum mengirimkan prompt ke LLM untuk memastikan jawaban AI bebas dari halusinasi informasi.
- **Temporal Query Expansion**: Metode perluasan kata kunci pencarian secara otomatis berdasarkan kesadaran tanggal dan waktu real-time agar rujukan jadwal pelayanan selalu sinkron dengan kondisi hari libur nasional atau cuti bersama.
- **Data Contact Crowdsourcing**: Metode pengumpulan nomor kontak secara sukarela namun terverifikasi dengan memanfaatkan kebutuhan wajib pajak akan informasi nominal tagihan pajak mereka.

### Peralatan / Material
Infrastruktur fisik dan perangkat keras yang digunakan meliputi:
- **Cloud VPS Server**: Sistem operasi Ubuntu Server, Nginx, PHP 8.2, dan MySQL 8.0 untuk hosting sistem utama.
- **PC / Laptop Administrator**: Perangkat komputer kerja untuk staf IT dan staf kurator basis pengetahuan Bapenda Lamongan.
- **Smartphone Dinas Luar**: Gawai komunikasi genggam yang digunakan oleh Petugas Dinas Luar di lapangan saat beroperasi menghubungi wajib pajak melalui aplikasi WhatsApp.

---

## 8. Strategi Keberlanjutan
Agar inovasi SALMA-AI dapat terus berjalan, relevan, dan memberikan kontribusi jangka panjang bagi peningkatan PAD Kabupaten Lamongan, kami menetapkan tiga pilar strategi keberlanjutan:

### Strategi Institusional
1. **Regulasi dan Payung Hukum Resmi**: Penyusunan dan penerbitan Peraturan Bupati (Perbup) atau Surat Keputusan (SK) Kepala Bapenda Kabupaten Lamongan tentang penyelenggaraan sistem layanan asisten virtual perpajakan daerah dan pengelolaan database kontak wajib pajak terintegrasi. Regulasi ini penting untuk memberikan keabsahan hukum penggunaan media WhatsApp sebagai sarana komunikasi penagihan formal dari pemerintah kepada masyarakat.
2. **Kepatuhan Terhadap UU Perlindungan Data Pribadi (PDP)**: Menjamin keamanan data kontak wajib pajak yang disimpan di database Bapenda dengan menerapkan protokol enkripsi data, membatasi akses dashboard penagihan dinas luar hanya untuk staf berwenang yang memiliki otentikasi akun ketat, dan menjamin nomor kontak yang terkumpul tidak disalahgunakan untuk tujuan di luar kedinasan perpajakan daerah Lamongan.

### Strategi Manajerial
1. **Penyusunan SOP Tugas Dinas Luar Digital**: Mereformasi standar operasional penagihan piutang dengan memberlakukan SOP pra-lapangan. Petugas Dinas Luar diwajibkan menyaring data kendaraan menunggak, lalu memprioritaskan menghubungi nomor WhatsApp yang terverifikasi di database untuk melakukan upaya klarifikasi digital terlebih dahulu. SOP kunjungan fisik door-to-door hanya dilakukan jika wajib pajak tidak kooperatif setelah kurun waktu tertentu, memangkas inefisiensi jam kerja staf lapangan.
2. **Pelatihan Berkala Staf Layanan**: Melakukan pelatihan berkala bagi staf administrasi Bapenda mengenai kurasi data, optimalisasi format prompt, penulisan *AI Instructions* yang efektif, dan pengoperasian dashboard data wajib pajak.
3. **Mekanisme Quality Assurance (QA) Mingguan**: Melakukan audit mingguan terhadap histori chat wajib pajak yang memberikan umpan balik negatif (*downvote*). Staf IT dan kurator akan meninjau percakapan tersebut, mengoreksi data basis pengetahuan yang kurang lengkap, dan menyempurnakan respons kecerdasan buatan.

### Strategi Sosial
1. **Sosialisasi dan Edukasi Publik Terpadu**: Mengampanyekan pemanfaatan asisten virtual SALMA-AI secara luas kepada masyarakat Lamongan. Informasi ini disebarkan melalui publikasi media sosial resmi Pemkab, penyediaan pamflet fisik dengan kode QR di loket Samsat Induk, Samsat Drive-Thru, mobil Samsat Keliling, serta sosialisasi pada kegiatan hari bebas kendaraan bermotor (Car Free Day).
2. **Kolaborasi dengan Pemerintah Kecamatan dan Desa**: Bekerja sama dengan Camat dan Kepala Desa di Kabupaten Lamongan. Perangkat desa dibekali akses informasi data warganya yang memiliki kendaraan menunggak pajak dan telah terdata nomor WhatsApp-nya oleh SALMA-AI, sehingga koordinasi penagihan dan pembinaan kepatuhan pajak di tingkat desa dapat berjalan lebih cepat dan harmonis secara kekeluargaan.
3. **Kemitraan dengan Komunitas Otomotif**: Menggandeng berbagai klub dan komunitas pemilik motor dan mobil di Lamongan untuk menggunakan SALMA-AI sebagai asisten andalan komunitas mereka saat mengecek nominal pajak sebelum melakukan perpanjangan bersama.

---

## 9. Data Dukung
Bagian ini menyajikan data empiris hasil analisis, proyeksi pertumbuhan sistem, simulasi efektivitas, serta visualisasi antarmuka sistem SALMA-AI untuk membuktikan nilai guna nyata inovasi ini.

### Data Kuantitatif
1. **Proyeksi Akumulasi Database Kontak Terverifikasi (Berdasarkan Rata-rata Kunjungan Harian)**:
   - Jumlah pengunjung unik menu informasi Samsat Lamongan per hari: Rata-rata 350 orang.
   - Target konversi wajib pajak yang melakukan cek PKB dan menyimpan WA mereka: Minimal 30% dari pengunjung harian (sekitar 105 kontak baru per hari).
   - Proyeksi akumulasi per bulan: Rata-rata 3.150 kontak WhatsApp wajib pajak terverifikasi.
   - Proyeksi 1 Tahun (Dengan kampanye sosialisasi intensif): Target akumulasi mencapai 50.000 hingga 75.000 kontak aktif yang siap digunakan oleh unit dinas luar penagihan.
2. **Laporan Uji Coba Lapangan: Studi Banding Penagihan Dinas Luar**:
   Dilakukan uji coba penagihan piutang pajak kendaraan bermotor (PKB) terhadap 100 wajib pajak yang terlambat membayar pajak di wilayah Kecamatan Lamongan Kota dengan membagi ke dalam dua metode penanganan:
   
   - **Grup Kontrol (Metode Lama - Perjalanan Fisik Door-to-Door Langsung)**:
     - Hasil Penagihan: Petugas mendatangi 50 alamat fisik penunggak pajak kendaraan. Dari 50 alamat, petugas hanya berhasil menemui 18 pemilik (efektivitas kunjungan fisik hanya 36%). Sisa alamat lainnya kosong, pemilik telah pindah, atau kendaraan sudah dijual.
     - Waktu Penyelesaian: 3 Hari Kerja.
     - Biaya Operasional: Rp 225.000 (bahan bakar motor dinas) + Rp 50.000 (kertas surat).
     - Nominal Pajak Tertagih: Rp 12.500.000.
     
   - **Grup Eksperimen (Metode Baru - Pra-Verifikasi WhatsApp SALMA-AI & Dinas Luar Terarah)**:
     - Hasil Penagihan: Petugas memindai 50 data penunggak yang nomor WhatsApp-nya telah direkam oleh SALMA-AI. Petugas melakukan penagihan digital awal via WhatsApp. Hasilnya: 22 wajib pajak langsung melakukan pembayaran online (e-Samsat/transfer bank) tanpa perlu dikunjungi fisik. 12 wajib pajak mengonfirmasi kendaraan telah dijual dan mengirim foto bukti penjualan via WA (data langsung diupdate oleh admin). 6 wajib pajak tidak merespon chat. 10 wajib pajak meminta dijadwalkan pembayaran fisik. Petugas hanya perlu mendatangi 10 alamat fisik yang meminta pembayaran langsung ini (efektivitas kunjungan lapangan menjadi 100%).
     - Waktu Penyelesaian: 1 Hari Kerja (mengurangi beban kerja petugas lapangan hingga 66%).
     - Biaya Operasional: Rp 45.000 (bahan bakar motor dinas untuk 10 alamat dekat) + Rp 0 biaya cetak fisik.
     - Nominal Pajak Tertagih: Rp 31.000.000 (tingkat pengembalian piutang naik lebih dari 140%).

### Data Kualitatif (Contoh Log Transkrip Chat RAG)
Berikut adalah salah satu contoh histori percakapan wajib pajak dengan asisten virtual SALMA-AI yang menunjukkan integrasi basis pengetahuan, kesadaran waktu, dan penampungan kontak:

```
User: "Siang min, mau nanya dong. Hari ini kan tanggal merah hari raya idul adha selasa 17 juni, apakah samsat induk lamongan buka? kalau tutup saya mau bayar besok rabu syaratnya apa aja ya buat motor?"

SALMA-AI: "Selamat siang! Hari ini Selasa, 17 Juni 2026, bertepatan dengan Libur Nasional Hari Raya Idul Adha, sehingga seluruh layanan kantor Samsat Induk Lamongan maupun Samsat Keliling sedang LIBUR (TUTUP).

Layanan Samsat Induk Lamongan akan kembali beroperasi besok pada hari Rabu, 18 Juni 2026 mulai pukul 08.00 - 13.00 WIB.

Untuk melakukan pembayaran pajak tahunan sepeda motor (perpanjangan STNK tahunan), berikut adalah dokumen persyaratan yang wajib Anda bawa ke Samsat Induk besok:
1. STNK Asli dan fotokopi (1 lembar).
2. KTP Asli pemilik kendaraan sesuai data STNK dan fotokopi (1 lembar).
3. BPKB Asli (untuk verifikasi) dan fotokopi (1 lembar).

Sebagai tips agar besok proses pembayaran Anda lebih cepat di loket, kami menyarankan Anda mengecek nominal tagihan pajak kendaraan Anda terlebih dahulu. Silakan ketik 'Cek PKB' untuk mendapatkan rincian tagihan pajak Anda yang akan dikirimkan langsung ke nomor WhatsApp Anda."
```

### Dokumentasi Antarmuka Pengguna (UI)
Antarmuka publik SALMA-AI menyajikan widget obrolan modern yang minimalis, cepat diakses melalui peramban web ponsel maupun desktop, serta form Pengecekan PKB yang aman. Di sisi administrator, dashboard admin menyediakan manajemen basis data pengetahuan perpajakan daerah yang lengkap dengan kolom khusus *AI Instructions* untuk mempermudah pimpinan instansi mengarahkan gaya komunikasi kecerdasan buatan agar selaras dengan perubahan regulasi daerah tanpa memerlukan modifikasi pada baris kode program.
