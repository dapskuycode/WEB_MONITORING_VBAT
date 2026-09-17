<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email'); // super_admin, owner, sponsor, student
            $table->date('birth_date')->nullable()->after('role');
            $table->string('gender')->nullable()->after('birth_date'); // male, female, other
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete()->after('gender');
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete()->after('province_id');
            $table->string('phone')->nullable()->after('city_id');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->text('address')->nullable()->after('whatsapp');
            $table->boolean('profile_completed')->default(false)->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn([
                'role',
                'birth_date',
                'gender',
                'province_id',
                'city_id',
                'phone',
                'whatsapp',
                'address',
                'profile_completed',
            ]);
        });
    }
};
