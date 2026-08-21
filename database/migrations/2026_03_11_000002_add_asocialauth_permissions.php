<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an 'asocialauth' permission and assigns it to the admin role (role_id=1).
 *
 * This permission gates the Social Auth settings page in the manager.
 * It does not affect the login buttons, which are visible to everyone.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions_groups') || !Schema::hasTable('permissions')) {
            return;
        }

        $groupId = $this->getOrCreateGroup();
        $this->upsertPermission($groupId);
        $this->assignPermissionToAdmin();
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role_id', 1)
                ->where('permission', 'asocialauth')
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('key', 'asocialauth')
                ->delete();
        }

        if (Schema::hasTable('permissions_groups')) {
            $group = DB::table('permissions_groups')->where('name', 'aSocialAuth')->first();

            if ($group) {
                $hasOtherPermissions = Schema::hasTable('permissions')
                    && DB::table('permissions')->where('group_id', $group->id)->exists();

                if (!$hasOtherPermissions) {
                    DB::table('permissions_groups')->where('id', $group->id)->delete();
                }
            }
        }
    }

    protected function getOrCreateGroup(): int
    {
        $group = DB::table('permissions_groups')->where('name', 'aSocialAuth')->first();

        if ($group) {
            return $group->id;
        }

        try {
            return DB::table('permissions_groups')->insertGetId([
                'name'       => 'aSocialAuth',
                'lang_key'   => 'aSocialAuth::global.permissions_group',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            $this->fixPostgresSequence('permissions_groups');

            try {
                return DB::table('permissions_groups')->insertGetId([
                    'name'       => 'aSocialAuth',
                    'lang_key'   => 'aSocialAuth::global.permissions_group',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e2) {
                $group = DB::table('permissions_groups')->where('name', 'aSocialAuth')->first();
                if ($group) {
                    return $group->id;
                }
                throw $e2;
            }
        }
    }

    protected function upsertPermission(int $groupId): void
    {
        $exists = DB::table('permissions')->where('key', 'asocialauth')->first();

        if ($exists) {
            DB::table('permissions')
                ->where('key', 'asocialauth')
                ->update([
                    'name'       => 'Manage Social Auth',
                    'lang_key'   => 'aSocialAuth::global.permission_access',
                    'group_id'   => $groupId,
                    'disabled'   => 0,
                    'updated_at' => now(),
                ]);
        } else {
            try {
                DB::table('permissions')->insert([
                    'key'        => 'asocialauth',
                    'name'       => 'Manage Social Auth',
                    'lang_key'   => 'aSocialAuth::global.permission_access',
                    'group_id'   => $groupId,
                    'disabled'   => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                // Race condition – permission was inserted concurrently.
                DB::table('permissions')
                    ->where('key', 'asocialauth')
                    ->update([
                        'name'       => 'Manage Social Auth',
                        'lang_key'   => 'aSocialAuth::global.permission_access',
                        'group_id'   => $groupId,
                        'disabled'   => 0,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    protected function assignPermissionToAdmin(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        $permission = DB::table('permissions')->where('key', 'asocialauth')->first();
        if (!$permission) {
            return;
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', 1)
            ->where('permission', 'asocialauth')
            ->exists();

        if (!$exists) {
            try {
                DB::table('role_permissions')->insert([
                    'role_id'    => 1,
                    'permission' => 'asocialauth',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                // Already exists – fine.
            }
        }
    }

    protected function fixPostgresSequence(string $table): void
    {
        try {
            $fullTable = DB::getTablePrefix() . $table;
            $maxId     = DB::table($table)->max('id') ?? 0;
            DB::statement("SELECT setval(pg_get_serial_sequence('{$fullTable}', 'id'), " . ($maxId + 1) . ', false)');
        } catch (\Exception $e) {
            // Not PostgreSQL or insufficient permissions – ignore.
        }
    }
};
