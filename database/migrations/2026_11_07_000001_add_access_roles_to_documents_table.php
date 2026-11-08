<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->json('access_roles')->nullable()->after('handled_by');
        });

        if (! Schema::hasTable('document_user')) {
            return;
        }

        $rolesByDocument = DB::table('document_user')
            ->join('users', 'users.id', '=', 'document_user.user_id')
            ->select('document_user.document_id', 'users.role')
            ->distinct()
            ->get()
            ->groupBy('document_id');

        foreach ($rolesByDocument as $documentId => $rows) {
            $roles = $rows
                ->pluck('role')
                ->filter(fn ($role) => in_array($role, ['admin', 'employee'], true))
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($roles === [] || $roles === ['admin', 'employee']) {
                continue;
            }

            DB::table('documents')
                ->where('id', $documentId)
                ->update(['access_roles' => json_encode(array_values($roles))]);
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('access_roles');
        });
    }
};
