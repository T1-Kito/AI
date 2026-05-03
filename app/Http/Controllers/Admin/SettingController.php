<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'baseUrl' => config('services.upstream_api.base_url'),
            'apiKeySet' => filled(config('services.upstream_api.key')),
            'markup' => env('TOZPIE_MARKUP_PERCENT', 15),
            'bankTokenSet' => filled(config('services.bank_webhook.token')),
            'maintenance' => filter_var(env('SHOP_MAINTENANCE_MODE', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function updateApi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'base_url' => ['required', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'markup' => ['required', 'integer', 'min:0', 'max:500'],
            'bank_token' => ['nullable', 'string', 'max:255'],
            'maintenance' => ['nullable', 'boolean'],
        ]);

        $this->writeEnvValue('UPSTREAM_API_BASE_URL', $data['base_url']);

        if (! empty($data['api_key'])) {
            $this->writeEnvValue('UPSTREAM_API_KEY', $data['api_key']);
        }

        if (! empty($data['bank_token'])) {
            $this->writeEnvValue('BANK_WEBHOOK_TOKEN', $data['bank_token']);
        }

        $this->writeEnvValue('TOZPIE_MARKUP_PERCENT', (string) $data['markup']);
        $this->writeEnvValue('SHOP_MAINTENANCE_MODE', ! empty($data['maintenance']) ? 'true' : 'false');

        Artisan::call('config:clear');

        return back()->with('success', 'Da luu cau hinh he thong.');
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $path = base_path('.env');
        $content = File::exists($path) ? File::get($path) : '';
        $escaped = str_replace('\\', '\\\\', str_replace('"', '\"', $value));
        $line = $key . '="' . $escaped . '"';

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content);
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        File::put($path, $content);
    }
}
