<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    // ─── Inertia page entry ─────────────────────────────────────────────────
    public function index()
    {
        return Inertia::render('UserManagement/Index');
    }

    // ─── API: list roles ─────────────────────────────────────────────────────
    public function getRoles()
    {
        return response()->json(Role::orderBy('name')->get(['id', 'name']));
    }

    // ─── API: stats (totals for header cards) ────────────────────────────────
    public function stats()
    {
        return response()->json([
            'total'    => User::withTrashed()->count(),
            'active'   => User::whereNull('deleted_at')->where('is_active', true)->count(),
            'inactive' => User::whereNull('deleted_at')->where('is_active', false)->count(),
            'deleted'  => User::onlyTrashed()->count(),
        ]);
    }

    // ─── API: paginated user list ─────────────────────────────────────────────
    public function apiIndex(Request $request)
    {
        $per    = min((int) $request->query('per_page', 15), 100);
        $q      = (string) $request->query('q', '');
        $role   = $request->query('role');
        $status = $request->query('status', 'all'); // all | active | inactive | deleted

        $query = User::query()->with('roles');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active') {
            $query->whereNull('deleted_at')->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->whereNull('deleted_at')->where('is_active', false);
        } else {
            $query->withTrashed();
        }

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('name', 'LIKE', "%{$q}%")
                  ->orWhere('email', 'LIKE', "%{$q}%");
            });
        }

        if ($role) {
            $query->role($role);
        }

        $users = $query->latest()->paginate($per);
        $users->getCollection()->transform(fn($u) => $this->formatUser($u));

        return response()->json($users);
    }

    // ─── API: show single user ────────────────────────────────────────────────
    public function apiShow($id)
    {
        $user = User::withTrashed()->with('roles')->findOrFail($id);
        return response()->json($this->formatUser($user));
    }

    // ─── API: create user ───────────────────────────────────────────────────
    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
            'role'                  => ['required', 'exists:roles,name'],
            'is_active'             => ['boolean'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'is_active'         => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'user'    => $this->formatUser($user),
        ], 201);
    }

    // ─── API: update user ────────────────────────────────────────────────────
    public function apiUpdate(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'      => ['required', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        $user->syncRoles([$validated['role']]);
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─── API: change password ────────────────────────────────────────────────
    public function changePassword(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Password berhasil diubah.']);
    }

    // ─── API: toggle active status ───────────────────────────────────────────
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat mengubah status akun sendiri.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => 'Status user berhasil diperbarui.',
            'is_active' => $user->is_active,
            'user'      => $this->formatUser($user->load('roles')),
        ]);
    }

    // ─── API: soft delete ────────────────────────────────────────────────────
    public function apiDestroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
    }

    // ─── API: restore ────────────────────────────────────────────────────────
    public function apiRestore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dipulihkan.',
            'user'    => $this->formatUser($user),
        ]);
    }

    // ─── API: permanent delete ───────────────────────────────────────────────
    public function apiForceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri.'], 403);
        }

        $user->forceDelete();

        return response()->json(['success' => true, 'message' => 'User dihapus permanen.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function formatUser(User $u): array
    {
        return [
            'id'                => $u->id,
            'name'              => $u->name,
            'email'             => $u->email,
            'email_verified_at' => $u->email_verified_at?->toIso8601String(),
            'is_active'         => (bool) $u->is_active,
            'profile_photo_url' => $u->profile_photo_url ?? null,
            'roles'             => $u->roles->pluck('name')->values()->toArray(),
            'created_at'        => $u->created_at?->toIso8601String(),
            'updated_at'        => $u->updated_at?->toIso8601String(),
            'deleted_at'        => $u->deleted_at?->toIso8601String(),
        ];
    }
}
