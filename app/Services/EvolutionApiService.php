<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected $baseUrl;
    protected $apiKey;
    protected $instance;

    public function __construct()
    {
        $this->baseUrl = config("services.evolution.url");
        $this->apiKey = config("services.evolution.key");
        $this->instance = config("services.evolution.instance");
    }

    /**
     * Send a text message via Evolution API
     *
     * @param string $phone Phone number to send message to
     * @param string $message Message content
     * @return array
     */
    public function sendMessage($phone, $message)
    {
        if (!$this->baseUrl || !$this->apiKey || !$this->instance) {
            $missing = [];
            if (!$this->baseUrl) $missing[] = 'EVOLUTION_API_URL';
            if (!$this->apiKey) $missing[] = 'EVOLUTION_API_KEY';
            if (!$this->instance) $missing[] = 'EVOLUTION_INSTANCE';
            
            Log::error("Evolution API configuration is missing", [
                'missing_variables' => $missing,
                'baseUrl' => $this->baseUrl ? 'set' : 'missing',
                'apiKey' => $this->apiKey ? 'set' : 'missing',
                'instance' => $this->instance ? 'set' : 'missing'
            ]);
            
            return [
                "success" => false,
                "message" => "إعدادات Evolution API غير موجودة. يرجى إضافة المتغيرات التالية في ملف .env: " . implode(', ', $missing)
            ];
        }

        try {
            Log::info("Attempting to send message via Evolution API", [
                'url' => $this->baseUrl . "/message/sendText/" . $this->instance,
                'phone' => $phone,
                'api_key_present' => !empty($this->apiKey),
                'instance' => $this->instance
            ]);

            // Evolution API might use different endpoint structures
            // Common formats: /message/sendText/{instance}, /wa/sendText/{instance}, or /api/{instance}/sendText
            $endpoints = [
                "/message/sendText/" . $this->instance,
                "/wa/sendText/" . $this->instance,
                "/api/" . $this->instance . "/sendText",
                "/" . $this->instance . "/sendText"
            ];

            // Different authorization header formats to try
            $authHeaders = [
                ["Authorization" => "Bearer " . $this->apiKey],
                ["Authorization" => $this->apiKey],
                ["apikey" => $this->apiKey],
                ["X-API-Key" => $this->apiKey]
            ];

            $success = false;
            foreach ($endpoints as $endpoint) {
                if ($success) break;

                foreach ($authHeaders as $authHeader) {
                    if ($success) break;

                    $headers = array_merge($authHeader, ["Content-Type" => "application/json"]);

                    $response = Http::withHeaders($headers)->post($this->baseUrl . $endpoint, [
                        "number" => $phone,
                        "text" => $message
                    ]);

                    $logInfo = [
                        'endpoint' => $endpoint,
                        'auth_header' => key($authHeader),
                        'status' => $response->status(),
                        'body' => $response->body()
                    ];
                    Log::info("Evolution API response", $logInfo);

                    // If we get a response other than 401 or 404, consider it for processing
                    if ($response->status() !== 401 && $response->status() !== 404) {
                        $success = true;
                    }
                }
            }

            if ($response->successful()) {
                $data = $response->json();
                return [
                    "success" => true,
                    "data" => $data
                ];
            } else {
                Log::error("Evolution API error: " . $response->body());
                return [
                    "success" => false,
                    "message" => "Failed to send message: " . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error("Evolution API exception: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Exception occurred: " . $e->getMessage()
            ];
        }
    }

    /**
     * Check if Evolution API is configured properly
     *
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->baseUrl) && !empty($this->apiKey) && !empty($this->instance);
    }
}
