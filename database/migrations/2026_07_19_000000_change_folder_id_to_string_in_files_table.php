<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom folder_id sebelumnya uuid + foreign key ke tabel `folders`.
     * Tapi FE gak pernah bikin row di tabel `folders` (folder cuma virtual
     * path di Supabase Storage), jadi FE ngirim NAMA folder (string biasa)
     * ke /files/sync, bukan UUID. Ini bikin insert selalu gagal (FK violation)
     * setiap kali upload file ke dalam folder apapun.
     *
     * Fix: folder_id jadi string biasa yang nampung nama/path folder,
     * gak lagi terikat ke tabel `folders`.
     */
    public function up(): void
    {
        // 1. Hapus foreign key constraint dulu (gak bisa ubah tipe kolom
        //    selama masih ada FK yang nempel ke situ)
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        // 2. Ubah tipe kolom dari uuid (CHAR(36)) jadi VARCHAR(255)
        //    Pakai raw SQL biar gak butuh package doctrine/dbal
        DB::statement('ALTER TABLE `files` MODIFY `folder_id` VARCHAR(255) NULL');
    }

    /**
     * Rollback: balikin ke uuid + foreign key kayak semula.
     * CATATAN: kalau udah ada data folder_id yang isinya nama folder
     * (bukan uuid valid), rollback ini bakal gagal karena data lama
     * gak match sama tipe/constraint uuid. Kosongkan folder_id dulu
     * kalau mau rollback di data production.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `files` MODIFY `folder_id` CHAR(36) NULL');

        Schema::table('files', function (Blueprint $table) {
            $table->foreign('folder_id')->references('id')->on('folders')->onDelete('cascade');
        });
    }
};
