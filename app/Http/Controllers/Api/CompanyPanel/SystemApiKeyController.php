<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\SystemApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $keys = SystemApiKey::orderBy('service_name')
            ->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'service_name' => $k->service_name,
                'is_active' => $k->is_active,
                'has_secret' => $k->secret_key !== null,
                'metadata' => $k->metadata,
                'created_at' => $k->created_at,
                'updated_at' => $k->updated_at,
            ]);

        return response()->json(['success' => true, 'data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $request->validate([
            'service_name' => 'required|string|max:50|unique:system_api_keys,service_name',
            'api_key' => 'required|string',
            'secret_key' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $key = SystemApiKey::create([
            'service_name' => $request->service_name,
            'api_key' => $request->api_key,
            'secret_key' => $request->secret_key,
            'metadata' => $request->metadata,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $key->id,
                'service_name' => $key->service_name,
                'is_active' => $key->is_active,
            ],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $key = SystemApiKey::findOrFail($id);

        $request->validate([
            'api_key' => 'sometimes|string',
            'secret_key' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ]);

        $data = $request->only('is_active', 'metadata');
        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }
        if ($request->has('secret_key')) {
            $data['secret_key'] = $request->secret_key;
        }
        $data['updated_by'] = $request->user()->id;

        $key->update($data);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $key->id,
                'service_name' => $key->service_name,
                'is_active' => $key->is_active,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->requireSuperAdmin($request);

        SystemApiKey::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'API key silindi.']);
    }

    public function test(Request $request, string $id): JsonResponse
    {
        $this->requireSuperAdmin($request);

        $key = SystemApiKey::findOrFail($id);

        if (!$key->is_active) {
            return response()->json(['success' => false, 'message' => 'API key aktif değil.'], 422);
        }

        // Basic connectivity test based on service type
        $result = match ($key->service_name) {
            'iyzico' => $this->testIyzico($key),
            'parasut' => $this->testParasut($key),
            default => ['success' => true, 'message' => 'Bağlantı testi bu servis için desteklenmiyor.'],
        };

        return response()->json($result);
    }

    private function testIyzico(SystemApiKey $key): array
    {
        return ['success' => true, 'message' => 'İyzico API key mevcut.'];
    }

    private function testParasut(SystemApiKey $key): array
    {
        $meta = $key->metadata ?? [];
        $clientId = $key->api_key;
        $clientSecret = $key->secret_key;
        $username = $meta['username'] ?? '';
        $password = $meta['password'] ?? '';
        $companyId = $meta['company_id'] ?? '';

        if (!$clientId || !$clientSecret || !$username || !$password || !$companyId) {
            return ['success' => false, 'message' => 'Eksik bilgi: client_id, client_secret, username, password ve company_id gerekli.'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(10)
                ->post('https://api.parasut.com/oauth/token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'username' => $username,
                    'password' => $password,
                    'grant_type' => 'password',
                    'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Paraşüt bağlantısı başarılı. Token alındı.'];
            }

            return ['success' => false, 'message' => 'Paraşüt yanıtı: ' . ($response->json('error_description') ?? 'HTTP ' . $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Bağlantı hatası: ' . $e->getMessage()];
        }
    }

    private function requireSuperAdmin(Request $request): void
    {
        if ($request->user()->company_panel_role !== 'super_admin') {
            abort(403, 'Bu işlem için super admin yetkisi gerekli.');
        }
    }
}
