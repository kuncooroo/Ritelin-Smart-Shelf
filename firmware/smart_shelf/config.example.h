#pragma once

// Salin berkas ini menjadi config.h. Token ini hanya contoh untuk satu perangkat demo.
// Setiap perangkat wajib punya token sendiri dari dashboard admin.
#define DEVICE_ID "SHELF-001"
#define DEVICE_TOKEN "local-shelf-001-token"
#define DEVICE_FIRMWARE "0.7.0"

#define WIFI_SSID ""
#define WIFI_PASSWORD ""

#define API_BASE_URL "http://127.0.0.1:8000"
#define API_TLS_INSECURE 0

#define HX711_DOUT_PIN 16
#define HX711_SCK_PIN 4

#define DEFAULT_THRESHOLD_GRAMS 15.0f
#define DEFAULT_VERIFY_MS 1500
#define SAMPLE_INTERVAL_MS 200
#define STATUS_INTERVAL_MS 500
#define UPLOAD_INTERVAL_MS 2000
#define CONFIG_INTERVAL_MS 30000
#define WIFI_RETRY_MS 10000

#define POWER_SAVE 0
