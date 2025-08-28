<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $group = $request->get('group', 'all');

        $query = AppSetting::active()->with(['creator', 'updater']);

        if ($group !== 'all') {
            $query->where('group', $group);
        }

        $settings = $query->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $groups = AppSetting::active()
            ->distinct('group')
            ->pluck('group')
            ->sort()
            ->values();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'groups' => $groups,
            'currentGroup' => $group,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $groups = AppSetting::active()
            ->distinct('group')
            ->pluck('group')
            ->sort()
            ->values();

        return Inertia::render('Settings/Create', [
            'groups' => $groups,
            'types' => $this->getSettingTypes(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:app_settings,key|max:255',
            'value' => 'nullable|string',
            'type' => 'required|in:string,integer,boolean,json,float',
            'group' => 'required|string|max:50',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
            'validation_rules' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $data = $validated;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        AppSetting::create($data);

        return redirect()
            ->route('settings.index')
            ->with('success', 'Setting berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AppSetting $setting): Response
    {
        $setting->load(['creator', 'updater']);

        return Inertia::render('Settings/Show', [
            'setting' => $setting,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppSetting $setting): Response
    {
        $groups = AppSetting::active()
            ->distinct('group')
            ->pluck('group')
            ->sort()
            ->values();

        return Inertia::render('Settings/Edit', [
            'setting' => $setting,
            'groups' => $groups,
            'types' => $this->getSettingTypes(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AppSetting $setting)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:app_settings,key,' . $setting->id,
            'value' => 'nullable|string',
            'type' => 'required|in:string,integer,boolean,json,float',
            'group' => 'required|string|max:50',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
            'validation_rules' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $setting->update($data);

        return redirect()
            ->route('settings.index')
            ->with('success', 'Setting berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppSetting $setting)
    {
        $setting->delete();

        return redirect()
            ->route('settings.index')
            ->with('success', 'Setting berhasil dihapus.');
    }

    /**
     * Update setting value quickly
     */
    public function updateValue(Request $request, AppSetting $setting): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Apply custom validation rules if exists
        if ($setting->validation_rules) {
            $customValidator = Validator::make($request->all(), [
                'value' => $setting->validation_rules,
            ]);

            if ($customValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $customValidator->errors(),
                ], 422);
            }
        }

        // Format value based on type
        $value = $request->value;
        if ($setting->type === 'json') {
            $value = json_encode($value);
        } elseif ($setting->type === 'boolean') {
            $value = $value ? 'true' : 'false';
        }

        $setting->update([
            'value' => $value,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $setting->fresh(),
            'formatted_value' => $setting->formatted_value,
        ]);
    }

    /**
     * Get public settings for frontend
     */
    public function getPublic(): JsonResponse
    {
        $settings = AppSetting::getAllGrouped(true);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get settings for specific group
     */
    public function getGroup(string $group): JsonResponse
    {
        $settings = AppSetting::getGroup($group, true);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): JsonResponse
    {
        AppSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache berhasil dibersihkan.',
        ]);
    }

    /**
     * Bulk update settings
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:app_settings,key',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = [];
        $errors = [];

        foreach ($request->settings as $settingData) {
            try {
                $setting = AppSetting::where('key', $settingData['key'])->first();

                if (!$setting) {
                    $errors[] = "Setting with key '{$settingData['key']}' not found.";
                    continue;
                }

                // Apply custom validation if exists
                if ($setting->validation_rules) {
                    $customValidator = Validator::make(['value' => $settingData['value']], [
                        'value' => $setting->validation_rules,
                    ]);

                    if ($customValidator->fails()) {
                        $errors[] = "Setting '{$setting->label}': " . implode(', ', $customValidator->errors()->all());
                        continue;
                    }
                }

                // Format value based on type
                $value = $settingData['value'];
                if ($setting->type === 'json') {
                    $value = json_encode($value);
                } elseif ($setting->type === 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                $setting->update([
                    'value' => $value,
                    'updated_by' => Auth::id(),
                ]);

                $updated[] = $setting->key;
            } catch (\Exception $e) {
                $errors[] = "Error updating setting '{$settingData['key']}': " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some settings could not be updated.',
                'errors' => $errors,
                'updated' => $updated,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'All settings updated successfully.',
            'updated' => $updated,
        ]);
    }

    /**
     * Get available setting types
     */
    private function getSettingTypes(): array
    {
        return [
            'string' => 'Text',
            'integer' => 'Number (Integer)',
            'float' => 'Number (Decimal)',
            'boolean' => 'Boolean (True/False)',
            'json' => 'JSON Object',
        ];
    }
}
