#pragma once

// Token demo hanya untuk perangkat SHELF-001 di mesin pengembangan.
// Setiap perangkat lain wajib memakai token baru dari dashboard admin.
#define DEVICE_ID "SHELF-001"
#define DEVICE_TOKEN "local-shelf-001-token"
#define DEVICE_FIRMWARE "0.7.0"

#define WIFI_SSID "KOS LT2 PUSAT"
#define WIFI_PASSWORD "kostlt2165"

// Prototipe LAN boleh http. Jaringan publik wajib https:// dengan sertifikat valid.
#define API_BASE_URL "http://192.168.1.5:8000"
#define API_TLS_INSECURE 0

#define HX711_DOUT_PIN 4
#define HX711_SCK_PIN 5

#define DEFAULT_THRESHOLD_GRAMS 15.0f
#define DEFAULT_VERIFY_MS 1500
#define SAMPLE_INTERVAL_MS 200
#define STATUS_INTERVAL_MS 500
#define UPLOAD_INTERVAL_MS 2000
#define CONFIG_INTERVAL_MS 30000
#define WIFI_RETRY_MS 10000

// Biarkan 0 selama uji USB. Nilai 1 mengaktifkan modem sleep dan light sleep.
#define POWER_SAVE 0
