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
     * Send a message with a button (URL button) via Evolution API
     *
     * @param string $phone Phone number to send message to
     * @param string $message Message content
     * @param string $buttonText Button text
     * @param string $buttonUrl Button URL
     * @return array
     */
    public function sendMessageWithButton($phone, $message, $buttonText, $buttonUrl)
    {
        if (!$this->baseUrl || !$this->apiKey || !$this->instance) {
            $missing = [];
            if (!$this->baseUrl) $missing[] = 'EVOLUTION_API_URL';
            if (!$this->apiKey) $missing[] = 'EVOLUTION_API_KEY';
            if (!$this->instance) $missing[] = 'EVOLUTION_INSTANCE';
            
            Log::error("Evolution API configuration is missing", [
                'missing_variables' => $missing,
            ]);
            
            return [
                "success" => false,
                "message" => "إعدادات Evolution API غير موجودة. يرجى إضافة المتغيرات التالية في ملف .env: " . implode(', ', $missing)
            ];
        }

        try {
            Log::info("Attempting to send message with button via Evolution API", [
                'phone' => $phone,
                'button_text' => $buttonText,
                'button_url' => $buttonUrl
            ]);

            // Evolution API endpoints for buttons/interactive messages
            $endpoints = [
                "/message/sendButtons/" . $this->instance,
                "/message/sendInteractive/" . $this->instance,
                "/wa/sendButtons/" . $this->instance,
                "/api/" . $this->instance . "/sendButtons",
                "/" . $this->instance . "/sendButtons"
            ];

            // Different authorization header formats to try
            $authHeaders = [
                ["Authorization" => "Bearer " . $this->apiKey],
                ["Authorization" => $this->apiKey],
                ["apikey" => $this->apiKey],
                ["X-API-Key" => $this->apiKey]
            ];

            $success = false;
            $response = null;

            foreach ($endpoints as $endpoint) {
                if ($success) break;

                foreach ($authHeaders as $authHeader) {
                    if ($success) break;

                    $headers = array_merge($authHeader, ["Content-Type" => "application/json"]);

                    // Try different payload formats for Evolution API
                    $payloads = [
                        // Format 1: Standard buttons format
                        [
                            "number" => $phone,
                            "text" => $message,
                            "buttons" => [
                                [
                                    "buttonId" => "btn1",
                                    "buttonText" => ["displayText" => $buttonText],
                                    "type" => 1, // URL button
                                    "url" => $buttonUrl
                                ]
                            ]
                        ],
                        // Format 2: Interactive message format
                        [
                            "number" => $phone,
                            "text" => $message,
                            "buttons" => [
                                [
                                    "id" => "btn1",
                                    "displayText" => $buttonText,
                                    "url" => $buttonUrl
                                ]
                            ]
                        ],
                        // Format 3: Simple format
                        [
                            "number" => $phone,
                            "text" => $message,
                            "buttonText" => $buttonText,
                            "buttonUrl" => $buttonUrl
                        ]
                    ];

                    foreach ($payloads as $payload) {
                        $response = Http::withHeaders($headers)->post($this->baseUrl . $endpoint, $payload);

                        $logInfo = [
                            'endpoint' => $endpoint,
                            'auth_header' => key($authHeader),
                            'payload_format' => array_keys($payload)[0],
                            'status' => $response->status(),
                            'body' => $response->body()
                        ];
                        Log::info("Evolution API button response", $logInfo);

                        // If we get a successful response, break
                        if ($response->successful()) {
                            $success = true;
                            break;
                        }

                        // If we get a response other than 401 or 404, consider it for processing
                        if ($response->status() !== 401 && $response->status() !== 404) {
                            $success = true;
                            break;
                        }
                    }
                }
            }

            if ($response && $response->successful()) {
                $data = $response->json();
                return [
                    "success" => true,
                    "data" => $data
                ];
            } else {
                // If button sending fails, fallback to regular text message
                Log::warning("Failed to send message with button, falling back to text message", [
                    'error' => $response ? $response->body() : 'No response'
                ]);
                
                // Send as regular text message with URL in text
                $messageWithUrl = $message . "\n\n" . $buttonText . ": " . $buttonUrl;
                return $this->sendMessage($phone, $messageWithUrl);
            }
        } catch (\Exception $e) {
            Log::error("Evolution API exception when sending button: " . $e->getMessage());
            
            // Fallback to regular text message
            $messageWithUrl = $message . "\n\n" . $buttonText . ": " . $buttonUrl;
            return $this->sendMessage($phone, $messageWithUrl);
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
